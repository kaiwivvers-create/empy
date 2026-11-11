<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with(['employee.department', 'employee.position'])
            ->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->where('role', '!=', User::ROLE_SUPER_ADMIN))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $search)->orWhere('email', 'like', $search));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        [$openModal, $editItem] = $this->modalState($request);

        $roles = Role::accountRoles();

        return view('users.index', [
            'users' => $users,
            'filters' => $request->only('search', 'role'),
            'roleOptions' => $roles->mapWithKeys(fn (Role $role) => [$role->name => $role->displayLabel()]),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::when(! $request->user()->isSuperAdmin(), fn ($query) => $query->where('is_visible', true))->orderBy('name')->get(),
            'managers' => $this->managerEmployees(),
            'employeeOptions' => $this->unlinkedEmployees(),
            'openModal' => $openModal,
            'editItem' => $editItem,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('users.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);
        $this->assertEmployeeDetails($data, null);

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => $data['password'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            $employee = $this->syncEmployee($request, $user, $data);

            if (! $employee && filled($data['photo_data'] ?? null)) {
                $user->update(['avatar_path' => $this->storeCroppedImage($data['photo_data'], 'avatars')]);
            }

            return $user;
        });

        ActivityLogger::record('created', 'User account created: '.$user->email, $user);

        return $this->redirectToIndex('users.index', ['search', 'role', 'page'])
            ->with('success', __('app.users.success_created'));
    }

    public function edit(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        return redirect()->route('users.index', ['open' => 'edit-'.$user->id]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        $data = $this->validateUser($request, $user);
        $this->assertEmployeeDetails($data, $user->employee);

        $isSelf = $user->id === $request->user()->id;

        if ($isSelf && (! in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true) || ! $request->boolean('is_active'))) {
            throw ValidationException::withMessages([
                'role' => __('app.users.cannot_lock_self'),
            ]);
        }

        if ($user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN) {
            throw ValidationException::withMessages([
                'role' => __('app.users.cannot_demote_super_admin'),
            ]);
        }

        $before = $user->getAttributes();

        DB::transaction(function () use ($request, $user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'is_active' => $request->boolean('is_active'),
                ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
            ]);

            $employee = $this->syncEmployee($request, $user, $data);

            if (! $employee && filled($data['photo_data'] ?? null)) {
                if ($user->avatar_path) {
                    Storage::disk('public')->delete($user->avatar_path);
                }

                $user->update(['avatar_path' => $this->storeCroppedImage($data['photo_data'], 'avatars')]);
            } elseif (! $employee && $request->boolean('remove_photo') && $user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
                $user->update(['avatar_path' => null]);
            }
        });

        ActivityLogger::record(
            'updated',
            'User account updated: '.$user->email,
            $user,
            ActivityLogger::changes($user, $before),
        );

        return $this->redirectToIndex('users.index', ['search', 'role', 'page'])
            ->with('success', __('app.users.success_updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        if ($user->id === $request->user()->id) {
            return back()->with('error', __('app.users.cannot_delete_self'));
        }

        if ($user->isSuperAdmin() && $this->isLastSuperAdmin($user)) {
            return back()->with('error', __('app.users.cannot_remove_last_super_admin'));
        }

        ActivityLogger::record('deleted', 'User account deleted: '.$user->email, $user);

        $user->delete();

        return $this->redirectToIndex('users.index', ['search', 'role', 'page'])
            ->with('success', __('app.users.success_deleted'));
    }

    /**
     * Keep the employee record behind a login in sync with the user form.
     * The employee row is only created when an employee ID is supplied.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncEmployee(Request $request, User $user, array $data): ?Employee
    {
        $previous = $user->employee;
        $employee = $previous;
        $selectedId = $data['employee_id'] ?? null;

        if (filled($selectedId)) {
            $selected = Employee::query()->find($selectedId);

            if ($selected && (int) $selected->id !== (int) $previous?->id) {
                // A login owns at most one employee record.
                $previous?->update(['user_id' => null]);

                $employee = $selected;
            }
        }

        $nip = $data['nip'] ?? null;

        if (! $employee && blank($nip)) {
            return null;
        }

        $justAttached = $employee !== null && (! $previous || (int) $previous->id !== (int) $employee->id);

        $payload = [
            'user_id' => $user->id,
            'nip' => filled($nip) ? $nip : $employee?->nip,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $this->valueOrExisting($data, 'phone', $employee, $justAttached),
            'department_id' => $this->valueOrExisting($data, 'department_id', $employee, $justAttached),
            'position_id' => $this->valueOrExisting($data, 'position_id', $employee, $justAttached),
            'manager_id' => $this->valueOrExisting($data, 'manager_id', $employee, $justAttached),
            'hire_date' => $this->valueOrExisting($data, 'hire_date', $employee, $justAttached),
            'status' => filled($data['status'] ?? null) ? $data['status'] : ($employee?->status ?? 'active'),
        ];

        $photo = $this->resolvePhoto($request, $user, $data, $employee);

        if ($photo !== false) {
            $payload['photo_path'] = $photo;
        }

        if ($employee) {
            $employee->update($payload);
            Organization::syncManagerWithDepartmentHead($employee);

            return $employee;
        }

        $employee = Employee::create($payload);

        Organization::syncManagerWithDepartmentHead($employee);

        return $employee;
    }

    /**
     * Keep the selected employee's stored value when a freshly linked employee
     * is saved while the matching form fields are still blank.
     *
     * @param  array<string, mixed>  $data
     */
    private function valueOrExisting(array $data, string $key, ?Employee $employee, bool $justAttached): mixed
    {
        if (filled($data[$key] ?? null) || ! $justAttached) {
            return $data[$key] ?? null;
        }

        return $employee?->{$key};
    }

    /**
     * Resolve the linked employee photo. Returns false when nothing changed.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolvePhoto(Request $request, User $user, array $data, ?Employee $employee): string|false|null
    {
        $current = $employee?->photo_path;

        if ($request->boolean('remove_photo')) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }

            if ($user->avatar_path && $user->avatar_path !== $current) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->update(['avatar_path' => null]);

            return null;
        }

        if (filled($data['photo_data'] ?? null)) {
            $path = $this->storeCroppedImage($data['photo_data'], 'employees');

            if ($current) {
                Storage::disk('public')->delete($current);
            }

            if ($user->avatar_path && $user->avatar_path !== $current) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->update(['avatar_path' => null]);

            return $path;
        }

        // Adopt a photo that was previously uploaded as the account avatar.
        if (! $current && $user->avatar_path) {
            $path = $user->avatar_path;
            $user->update(['avatar_path' => null]);

            return $path;
        }

        return false;
    }

    private function storeCroppedImage(string $dataUrl, string $directory): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'photo_data' => __('app.profile.invalid_image'),
            ]);
        }

        $binary = base64_decode(substr($dataUrl, strlen($matches[0])), true);

        if ($binary === false || strlen($binary) > 3 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'photo_data' => __('app.profile.invalid_image'),
            ]);
        }

        $size = @getimagesizefromstring($binary);

        if ($size === false || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages([
                'photo_data' => __('app.profile.invalid_image'),
            ]);
        }

        $path = trim($directory, '/').'/'.Str::random(40).'.png';

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertEmployeeDetails(array $data, ?Employee $employee): void
    {
        $hasDetails = filled($data['phone'] ?? null)
            || filled($data['department_id'] ?? null)
            || filled($data['position_id'] ?? null)
            || filled($data['manager_id'] ?? null)
            || filled($data['hire_date'] ?? null);

        if (blank($data['nip'] ?? null) && blank($data['employee_id'] ?? null) && ($employee || $hasDetails)) {
            throw ValidationException::withMessages([
                'nip' => __('app.users.employee_nip_required'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $employee = $user?->employee;
        $targetEmployee = $request->filled('employee_id')
            ? Employee::query()->find($request->integer('employee_id'))
            : $employee;

        $managerRule = [
            'nullable',
            Rule::exists('employees', 'id')->whereIn(
                'user_id',
                User::query()->whereIn('role', Role::managerNames())->select('id'),
            )->where('status', 'active'),
        ];

        if ($targetEmployee) {
            $managerRule[] = Rule::notIn([$targetEmployee->id]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', ...$this->roleRule($request)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'nip' => ['nullable', 'string', 'max:40', Rule::unique('employees', 'nip')->ignore($targetEmployee?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'manager_id' => $managerRule,
            'hire_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,inactive'],
            'photo_data' => ['nullable', 'string'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if (filled($data['employee_id'] ?? null)) {
            $selected = Employee::query()->find($data['employee_id']);

            if ($selected && $selected->user_id && (int) $selected->user_id !== (int) $user?->id) {
                throw ValidationException::withMessages([
                    'employee_id' => __('app.users.employee_already_linked'),
                ]);
            }
        }

        return $data;
    }

    /**
     * Employee records that do not have a login yet.
     */
    private function unlinkedEmployees()
    {
        return Employee::query()
            ->whereNull('user_id')
            ->with(['department', 'position'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function roleRule(Request $request): array
    {
        $rules = ['required', Rule::exists('roles', 'name')];

        if (! $request->user()->isSuperAdmin()) {
            $rules[] = 'not_in:'.User::ROLE_SUPER_ADMIN;
        }

        return $rules;
    }

    private function managerEmployees()
    {
        return Employee::active()
            ->whereHas('user', fn ($query) => $query->whereIn('role', Role::managerNames()))
            ->with('position')
            ->orderBy('name')
            ->get();
    }

    private function authorizeUser(Request $request, User $user): void
    {
        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(404);
        }
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return User::where('role', User::ROLE_SUPER_ADMIN)->whereKeyNot($user->id)->doesntExist();
    }

    /**
     * @return array{0: ?string, 1: ?User}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = User::with('employee')->find($matches[1]);

            if ($editItem?->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
                $editItem = null;
            }

            $open = $editItem ? 'user-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'user-create';
        }

        return [$open ?: null, $editItem];
    }
}
