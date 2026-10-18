<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $showTrashed = $user->isAdmin() && $request->boolean('trashed');

        $employees = Employee::query()
            ->with(['department', 'position', 'manager', 'user'])
            ->withCount('evaluations')
            ->when($showTrashed, fn ($query) => $query->onlyTrashed())
            ->when($user->isManager(), fn ($query) => $query->where('manager_id', $user->employee?->id))
            ->when($user->isEmployee(), fn ($query) => $query->where('id', $user->employee?->id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $search)
                    ->orWhere('nip', 'like', $search));
            })
            ->when($request->filled('department'), fn ($query) => $query->where('department_id', $request->integer('department')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        [$openModal, $editItem] = $this->modalState($request);

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'managers' => Employee::active()->with('position')->orderBy('name')->get(),
            'isManagerView' => $user->isManager(),
            'showTrashed' => $showTrashed,
            'filters' => $request->only('search', 'department', 'status'),
            'openModal' => $openModal,
            'editItem' => $editItem,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('employees.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEmployee($request);

        $created = DB::transaction(function () use ($data, $request) {
            $account = $this->syncAccount($data);

            return Employee::create([
                ...$this->employeePayload($data),
                'photo_path' => $this->handlePhoto($request),
                'user_id' => $account['user_id'],
            ]);
        });

        ActivityLogger::record('created', 'Employee created: '.$created->name, $created);

        return redirect()
            ->route('employees.show', $created)
            ->with('success', __('app.employees.success_created'));
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->authorizeView($request, $employee);

        $employee->load([
            'department.head',
            'position',
            'manager',
            'user',
            'goals.period',
            'improvementPlans.period',
        ]);

        $evaluations = $employee->evaluations()
            ->with('period')
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->get();

        return view('employees.show', [
            'employee' => $employee,
            'evaluations' => $evaluations,
            'latest' => $evaluations->first(),
            'goals' => $employee->goals->sortByDesc('period_id')->take(6),
            'plans' => $employee->improvementPlans->sortByDesc('created_at')->take(5),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'managers' => Employee::active()->whereKeyNot($employee->id)->with('position')->orderBy('name')->get(),
        ]);
    }

    public function edit(Employee $employee): RedirectResponse
    {
        return redirect()->route('employees.index', ['open' => 'edit-'.$employee->id]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validateEmployee($request, $employee);
        $before = $employee->getAttributes();

        DB::transaction(function () use ($data, $employee, $request) {
            $account = $this->syncAccount($data, $employee);

            $employee->update([
                ...$this->employeePayload($data),
                'photo_path' => $this->handlePhoto($request, $employee),
                'user_id' => $account['user_id'],
            ]);
        });

        ActivityLogger::record(
            'updated',
            'Employee updated: '.$employee->name,
            $employee,
            ActivityLogger::changes($employee, $before),
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', __('app.employees.success_updated'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->evaluations()->exists()) {
            return back()->with('error', __('app.employees.delete_blocked'));
        }

        ActivityLogger::record('deleted', 'Employee archived: '.$employee->name, $employee);

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', __('app.employees.success_deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        $employee->restore();

        ActivityLogger::record('restored', 'Employee restored: '.$employee->name, $employee);

        return redirect()->route('employees.index', ['trashed' => 1])
            ->with('success', __('app.employees.success_restored'));
    }

    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $managerRule = ['nullable', 'exists:employees,id'];

        if ($employee) {
            $managerRule[] = Rule::notIn([$employee->id]);
        }

        $data = $request->validate([
            'nip' => ['required', 'string', 'max:40', Rule::unique('employees', 'nip')->ignore($employee)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_id' => $managerRule,
            'hire_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'account_email' => ['nullable', 'email', 'max:255'],
            'account_role' => ['required', 'in:employee,manager'],
            'account_password' => ['nullable', 'string', 'min:6'],
            'user_id' => ['nullable', 'exists:users,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if (filled($data['account_email'] ?? null)) {
            $linkedUser = $employee?->user;

            $exists = User::where('email', $data['account_email'])
                ->when($linkedUser, fn ($query) => $query->whereKeyNot($linkedUser->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'account_email' => __('validation.unique', ['attribute' => 'email']),
                ]);
            }
        }

        return $data;
    }

    private function employeePayload(array $data): array
    {
        return [
            'nip' => $data['nip'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_id' => $data['position_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
            'status' => $data['status'],
        ];
    }

    /**
     * Link an existing account or create a new one for the employee.
     *
     * @return array{user_id: ?int}
     */
    private function syncAccount(array $data, ?Employee $employee = null): array
    {
        $email = $data['account_email'] ?? null;
        $linked = $employee?->user;

        if (! $email) {
            return ['user_id' => $employee?->user_id];
        }

        // Never let a regular admin change a hidden super admin account.
        if ($linked?->isSuperAdmin()) {
            return ['user_id' => $linked->id];
        }

        if ($linked) {
            $before = $linked->getAttributes();

            $linked->update([
                'name' => $data['name'],
                'email' => $email,
                'role' => $data['account_role'],
            ]);

            if (filled($data['account_password'] ?? null)) {
                $linked->update(['password' => $data['account_password']]);
            }

            ActivityLogger::record(
                'updated',
                'Linked user account updated: '.$linked->email,
                $linked,
                ActivityLogger::changes($linked, $before),
            );

            return ['user_id' => $linked->id];
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'role' => $data['account_role'],
            'is_active' => true,
            'password' => $data['account_password'] ?: 'password',
        ]);

        ActivityLogger::record('created', 'Linked user account created: '.$user->email, $user);

        return ['user_id' => $user->id];
    }

    private function handlePhoto(Request $request, ?Employee $employee = null): ?string
    {
        if ($request->boolean('remove_photo') && $employee?->photo_path) {
            Storage::disk('public')->delete($employee->photo_path);

            return null;
        }

        if ($request->hasFile('photo')) {
            if ($employee?->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }

            return $request->file('photo')->store('employees', 'public');
        }

        return $employee?->photo_path;
    }

    private function authorizeView(Request $request, Employee $employee): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $employee->user_id === $user->id) {
            return;
        }

        if ($user->isManager() && $employee->manager_id === $user->employee?->id) {
            return;
        }

        abort(403, __('app.common.no_access'));
    }

    /**
     * @return array{0: ?string, 1: ?Employee}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = Employee::with('user')->find($matches[1]);
            $open = $editItem ? 'employee-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'employee-create';
        }

        return [$open ?: null, $editItem];
    }
}
