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
    public function index(Request $request, Employee $employee): View
    {
        $this->authorizeView($request, $employee);

        $periods = EvaluationPeriod::orderByDesc('start_date')->get();

        $goals = $employee->goals()
            ->with('period')
            ->when($request->filled('period'), fn ($query) => $query->where('period_id', $request->integer('period')))
            ->orderByDesc('period_id')
            ->orderBy('title')
            ->get();

        return view('goals.index', [
            'employee' => $employee->load(['department', 'position']),
            'goals' => $goals,
            'periods' => $periods,
            'selectedPeriod' => $request->integer('period') ?: null,
            'canManage' => $this->canManage($request, $employee),
        ]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeManage($request, $employee);

        $data = $this->validated($request);
        $goal = $employee->goals()->create($data);

        ActivityLogger::record('created', 'KPI target added for '.$employee->name.': '.$data['title'], $goal);

        return redirect()->route('employees.goals.index', $employee)
            ->with('success', __('app.goals.success_created'));
    }

    public function update(Request $request, Employee $employee, EmployeeGoal $goal): RedirectResponse
    {
        $this->authorizeManage($request, $employee);
        abort_unless($goal->employee_id === $employee->id, 404);

        $before = $goal->getAttributes();

        $goal->update($this->validated($request));

        ActivityLogger::record(
            'updated',
            'KPI target updated for '.$employee->name.': '.$goal->title,
            $goal,
            ActivityLogger::changes($goal, $before),
        );

        return redirect()->route('employees.goals.index', $employee)
            ->with('success', __('app.goals.success_updated'));
    }

    public function destroy(Request $request, Employee $employee, EmployeeGoal $goal): RedirectResponse
    {
        $this->authorizeManage($request, $employee);
        abort_unless($goal->employee_id === $employee->id, 404);

        ActivityLogger::record('deleted', 'KPI target deleted for '.$employee->name.': '.$goal->title, $goal);

        $goal->delete();

        return redirect()->route('employees.goals.index', $employee)
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
