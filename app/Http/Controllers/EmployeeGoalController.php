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
            'goalPeriods' => $this->goalPeriods($user),
            'unitOptions' => EmployeeGoal::UNITS,
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

        if ($this->periodLocked($request, (int) $data['period_id'], true)) {
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
        $data = $this->validated($request, $goal);

        if ($this->periodLocked($request, (int) $goal->period_id)) {
            // Once the window closes only progress and notes stay editable; the
            // agreed target itself is kept as it was.
            $data['period_id'] = $goal->period_id;
            $data['title'] = $goal->title;
            $data['target_value'] = $goal->target_value;
            $data['unit'] = $goal->unit;
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

    /**
     * Toggle a KPI between completed and incomplete. Completion is only
     * possible once the achievement has reached the target, and it can always
     * be reopened, including for previous periods.
     */
    public function complete(Request $request, Employee $employee, EmployeeGoal $goal): RedirectResponse
    {
        $this->authorizeManage($request, $employee);
        abort_unless($goal->employee_id === $employee->id, 404);

        if ($goal->isCompleted()) {
            $goal->update(['completed_at' => null]);

            ActivityLogger::record('updated', 'KPI reopened for '.$employee->name.': '.$goal->title, $goal);

            return back()->with('success', __('app.goals.success_reopened'));
        }

        if (($goal->achievementPercent() ?? 0) < 100) {
            return back()->with('error', __('app.goals.complete_blocked'));
        }

        $goal->update(['completed_at' => now()]);

        ActivityLogger::record('updated', 'KPI completed for '.$employee->name.': '.$goal->title, $goal);

        return back()->with('success', __('app.goals.success_completed'));
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

    private function validated(Request $request, ?EmployeeGoal $goal = null): array
    {
        $unit = $request->string('unit')->toString();
        $valueRules = EmployeeGoal::unitAllowsDecimals($unit)
            ? ['numeric', 'decimal:0,2']
            : ['integer'];

        $data = $request->validate([
            'period_id' => ['required', 'exists:evaluation_periods,id'],
            'title' => ['required', 'string', 'max:255'],
            'target_value' => ['required', ...$valueRules, 'min:'.(EmployeeGoal::unitAllowsDecimals($unit) ? '0.01' : '1'), 'max:9999999999.99'],
            'unit' => ['required', 'string', 'max:30', 'in:'.implode(',', array_unique(array_merge(EmployeeGoal::UNITS, $goal?->unit ? [$goal->unit] : [])))],
            'achievement_value' => ['nullable', ...$valueRules, 'min:0', 'max:9999999999.99'],
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
    private function periodLocked(Request $request, int $periodId, bool $creating = false): bool
    {
        if ($request->user()->isAdmin()) {
            return false;
        }

        $period = EvaluationPeriod::find($periodId);

        if (! $period) {
            return true;
        }

        return $creating
            ? ! $period->acceptsEntries()
            : ! $period->isOpenForEditing();
    }

    private function goalPeriods($user)
    {
        $query = EvaluationPeriod::query()->orderByDesc('start_date');

        if (! $user->isAdmin()) {
            $query
                ->where('status', 'active')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today());
        }

        return $query->get();
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
