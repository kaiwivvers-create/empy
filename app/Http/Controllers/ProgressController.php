<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403, __('app.common.no_access'));

        $periods = EvaluationPeriod::orderByDesc('start_date')->get();

        $period = $request->filled('period')
            ? $periods->firstWhere('id', $request->integer('period'))
            : ($periods->firstWhere('status', 'active') ?? $periods->first());

        $departmentId = $request->filled('department') ? $request->integer('department') : null;

        $employees = Employee::query()
            ->active()
            ->evaluable()
            ->with(['department', 'position', 'manager'])
            ->when(! $user->isAdmin(), fn ($query) => $query->where('manager_id', $user->employee?->id))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('name')
            ->get();

        $evaluations = $period
            ? Evaluation::query()
                ->with('evaluator')
                ->where('period_id', $period->id)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->keyBy('employee_id')
            : collect();

        $rows = $employees->map(function (Employee $employee) use ($evaluations) {
            /** @var Evaluation|null $evaluation */
            $evaluation = $evaluations->get($employee->id);

            return [
                'employee' => $employee,
                'evaluation' => $evaluation,
                'state' => $evaluation?->isSubmitted() ? 'submitted' : ($evaluation ? 'draft' : 'pending'),
            ];
        });

        $summary = [
            'total' => $rows->count(),
            'submitted' => $rows->where('state', 'submitted')->count(),
            'draft' => $rows->where('state', 'draft')->count(),
            'pending' => $rows->where('state', 'pending')->count(),
            'average' => round((float) $rows->filter(fn ($row) => $row['evaluation']?->isSubmitted())->avg(fn ($row) => (float) $row['evaluation']->final_score), 1),
        ];

        $summary['completion'] = $summary['total'] > 0
            ? round($summary['submitted'] / $summary['total'] * 100, 1)
            : 0.0;

        $managerGroups = $rows
            ->filter(fn ($row) => $row['state'] !== 'submitted')
            ->groupBy(fn ($row) => $row['employee']->manager?->name ?? __('app.progress.no_manager'))
            ->map(fn (Collection $group, string $manager) => [
                'manager' => $manager,
                'pending' => $group->count(),
                'employees' => $group->pluck('employee.name')->all(),
            ])
            ->sortByDesc('pending')
            ->values();

        $byDepartment = $rows
            ->groupBy(fn ($row) => $row['employee']->department?->name ?? '-')
            ->map(fn (Collection $group, string $name) => [
                'department' => $name,
                'total' => $group->count(),
                'submitted' => $group->where('state', 'submitted')->count(),
                'completion' => $group->count() > 0
                    ? round($group->where('state', 'submitted')->count() / $group->count() * 100, 1)
                    : 0.0,
            ])
            ->values();

        return view('reports.progress', [
            'periods' => $periods,
            'period' => $period,
            'departments' => Department::orderBy('name')->get(),
            'departmentId' => $departmentId,
            'rows' => $rows,
            'summary' => $summary,
            'managerGroups' => $managerGroups,
            'byDepartment' => $byDepartment,
            'canRemind' => $user->isAdmin(),
        ]);
    }
}
