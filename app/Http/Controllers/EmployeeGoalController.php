<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeGoal;
use App\Models\EvaluationPeriod;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeGoalController extends Controller
{
    /**
     * Sidebar KPI workspace: pick an employee, then see their targets.
     */
    public function overview(Request $request): View
    {
        $user = $request->user();

        $employees = Employee::query()
            ->active()
            ->with(['department', 'position'])
            ->withCount('goals')
            ->when($user->isManager(), function ($query) use ($user) {
                $employeeId = $user->employee?->id;

                $query->where(fn ($inner) => $inner
                    ->where('manager_id', $employeeId)
                    ->orWhere('id', $employeeId ?? 0));
            })
            ->when($user->isEmployee(), fn ($query) => $query->whereKey($user->employee?->id ?? 0))
            ->orderBy('name')
            ->get();

        $employee = null;

        if ($request->filled('employee')) {
            $employee = $employees->firstWhere('id', $request->integer('employee'));

            abort_unless($employee, 404);
        } elseif ($employees->count() === 1) {
            $employee = $employees->first();
        }

        $goals = $employee
            ? $employee->goals()
                ->with('period')
                ->when($request->filled('period'), fn ($query) => $query->where('period_id', $request->integer('period')))
                ->orderByDesc('period_id')
                ->orderBy('title')
                ->get()
            : collect();

        return view('goals.overview', [
            'employees' => $employees,
            'employee' => $employee,
            'goals' => $goals,
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'selectedPeriod' => $request->integer('period') ?: null,
            'canManage' => $employee ? $this->canManage($request, $employee) : false,
        ]);
    }

    /**
     * The old per-employee goal page now lives inside the KPI workspace.
     */
    public function index(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeView($request, $employee);

        return redirect()->route('kpi.index', array_filter([
            'employee' => $employee->id,
            'period' => $request->integer('period') ?: null,
        ]));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeManage($request, $employee);

        $data = $this->validated($request);

        if ($this->periodLocked($request, (int) $data['period_id'])) {
            return back()->with('error', __('app.goals.period_locked'));
        }

        $goal = $employee->goals()->create($data);

        ActivityLogger::record('created', 'KPI target added for '.$employee->name.': '.$data['title'], $goal);

        return redirect()->route('kpi.index', ['employee' => $employee->id, 'period' => $data['period_id']])
            ->with('success', __('app.goals.success_created'));
    }

    public function update(Request $request, Employee $employee, EmployeeGoal $goal): RedirectResponse
    {
        $this->authorizeManage($request, $employee);
        abort_unless($goal->employee_id === $employee->id, 404);

        $before = $goal->getAttributes();
        $data = $this->validated($request);

        if ($this->periodLocked($request, (int) $data['period_id'])) {
            return back()->with('error', __('app.goals.period_locked'));
        }

        $goal->update($data);

        ActivityLogger::record(
            'updated',
            'KPI target updated for '.$employee->name.': '.$goal->title,
            $goal,
            ActivityLogger::changes($goal, $before),
        );

        return redirect()->route('kpi.index', ['employee' => $employee->id, 'period' => $data['period_id']])
            ->with('success', __('app.goals.success_updated'));
    }

    public function destroy(Request $request, Employee $employee, EmployeeGoal $goal): RedirectResponse
    {
        $this->authorizeManage($request, $employee);
        abort_unless($goal->employee_id === $employee->id, 404);

        if ($this->periodLocked($request, (int) $goal->period_id)) {
            return back()->with('error', __('app.goals.period_locked'));
        }

        ActivityLogger::record('deleted', 'KPI target deleted for '.$employee->name.': '.$goal->title, $goal);

        $goal->delete();

        return redirect()->route('kpi.index', ['employee' => $employee->id])
            ->with('success', __('app.goals.success_deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'period_id' => ['required', 'exists:evaluation_periods,id'],
            'title' => ['required', 'string', 'max:255'],
            'target_value' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'achievement_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $data;
    }

    private function authorizeView(Request $request, Employee $employee): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $employee->user_id === $user->id || $employee->isManagedBy($user)) {
            return;
        }

        abort(403, __('app.common.no_access'));
    }

    /**
     * KPIs belong to a period, so they can only be changed while that period
     * is inside its date range (admins can always correct history).
     */
    private function periodLocked(Request $request, int $periodId): bool
    {
        if ($request->user()->isAdmin()) {
            return false;
        }

        $period = EvaluationPeriod::find($periodId);

        return $period !== null && ! $period->isOpenForEditing();
    }

    private function canManage(Request $request, Employee $employee): bool
    {
        return $employee->isManagedBy($request->user());
    }

    private function authorizeManage(Request $request, Employee $employee): void
    {
        if (! $this->canManage($request, $employee)) {
            abort(403, __('app.common.no_access'));
        }
    }
}
