<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\ImprovementPlan;
use App\Models\PeerFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $periods = EvaluationPeriod::orderByDesc('start_date')->get();

        $activePeriod = $request->filled('period')
            ? $periods->firstWhere('id', $request->integer('period'))
            : ($periods->firstWhere('status', 'active') ?? $periods->first());

        $data = [
            'activePeriod' => $activePeriod,
            'periods' => $periods,
            'periodFilterEnabled' => ! $user->isEmployee(),
            'greeting' => __('app.dashboard.greeting', ['name' => $user->name]),
            'role' => $user->role,
            'pendingReviews' => PeerFeedback::where('reviewer_id', $user->id)
                ->whereNull('submitted_at')
                ->count(),
        ];

        if ($user->isAdmin()) {
            $data += $this->adminData($activePeriod);
        } elseif ($user->isManager()) {
            $data += $this->managerData($user->employee, $activePeriod);
        } else {
            $data += $this->employeeData($user->employee);
        }

        return view('dashboard', $data);
    }

    private function adminData(?EvaluationPeriod $period): array
    {
        $totalEmployees = Employee::active()->count();

        $submitted = $period
            ? Evaluation::where('period_id', $period->id)->where('status', 'submitted')
            : Evaluation::whereRaw('1 = 0');

        $evaluated = (clone $submitted)->count();
        $average = (float) (clone $submitted)->avg('final_score');

        $departmentScores = Department::query()
            ->withCount('employees')
            ->get()
            ->map(function (Department $department) use ($period) {
                $scores = Evaluation::query()
                    ->where('status', 'submitted')
                    ->when($period, fn ($query) => $query->where('period_id', $period->id))
                    ->whereHas('employee', fn ($query) => $query->where('department_id', $department->id))
                    ->pluck('final_score');

                return [
                    'label' => $department->name,
                    'value' => round((float) $scores->avg(), 1),
                ];
            })
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values();

        $trend = EvaluationPeriod::query()
            ->orderBy('start_date')
            ->get()
            ->map(fn (EvaluationPeriod $item) => [
                'label' => $item->name,
                'value' => round((float) Evaluation::where('period_id', $item->id)
                    ->where('status', 'submitted')->avg('final_score'), 1),
            ]);

        $recent = Evaluation::with(['employee.department', 'period'])
            ->where('status', 'submitted')
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->latest('submitted_at')
            ->limit(6)
            ->get();

        $topPerformers = Evaluation::with(['employee.department', 'period'])
            ->where('status', 'submitted')
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->orderByDesc('final_score')
            ->limit(5)
            ->get();

        return [
            'totalEmployees' => $totalEmployees,
            'evaluated' => $evaluated,
            'pending' => max($totalEmployees - $evaluated, 0),
            'averageScore' => round($average, 1),
            'departmentScores' => $departmentScores,
            'trend' => $trend,
            'recent' => $recent,
            'topPerformers' => $topPerformers,
            'openPlans' => ImprovementPlan::open()
                ->with('employee')
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
        ];
    }

    private function managerData(?Employee $manager, ?EvaluationPeriod $period): array
    {
        $team = $manager
            ? Employee::active()->where('manager_id', $manager->id)->get()
            : collect();

        $evaluations = $period
            ? Evaluation::with(['employee.department'])
                ->where('period_id', $period->id)
                ->whereIn('employee_id', $team->pluck('id'))
                ->get()
            : collect();

        $submitted = $evaluations->where('status', 'submitted');
        $teamScores = $submitted->map(fn (Evaluation $evaluation) => [
            'label' => $evaluation->employee->name,
            'value' => round((float) $evaluation->final_score, 1),
        ])->values();

        $pending = $team->count() - $submitted->count();

        $recent = Evaluation::with(['employee.department', 'period'])
            ->whereIn('employee_id', $team->pluck('id'))
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $myTeam = $team->map(fn (Employee $employee) => [
            'employee' => $employee,
            'evaluation' => $evaluations->firstWhere('employee_id', $employee->id),
        ]);

        return [
            'teamMembers' => $team->count(),
            'teamScores' => $teamScores,
            'pending' => max($pending, 0),
            'evaluated' => $submitted->count(),
            'averageScore' => round((float) $submitted->avg('final_score'), 1),
            'recent' => $recent,
            'myTeam' => $myTeam,
            'teamTrend' => $this->teamTrend($team),
            'openPlans' => ImprovementPlan::open()
                ->with('employee')
                ->whereIn('employee_id', $team->pluck('id'))
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
        ];
    }

    private function teamTrend(Collection $team): Collection
    {
        $trend = EvaluationPeriod::query()
            ->where('status', '!=', 'draft')
            ->orderBy('start_date')
            ->get()
            ->map(fn (EvaluationPeriod $item) => [
                'label' => $item->name,
                'value' => round((float) Evaluation::where('period_id', $item->id)
                    ->where('status', 'submitted')
                    ->whereIn('employee_id', $team->pluck('id'))
                    ->avg('final_score'), 1),
            ])
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values();

        return $trend;
    }

    private function employeeData(?Employee $employee): array
    {
        $evaluations = $employee
            ? Evaluation::with('period')
                ->where('employee_id', $employee->id)
                ->where('status', 'submitted')
                ->orderByDesc('submitted_at')
                ->get()
            : collect();

        $latest = $evaluations->first();

        $history = $evaluations->reverse()->values()->map(fn (Evaluation $evaluation) => [
            'label' => $evaluation->period->name,
            'value' => round((float) $evaluation->final_score, 1),
        ]);

        $criteriaScores = $latest
            ? $latest->scores()->with('criterion')->get()->map(fn ($score) => [
                'label' => $score->criterion->name,
                'value' => $score->score,
                'weight' => (float) $score->criterion->weight,
            ])
            : collect();

        return [
            'employee' => $employee,
            'myEvaluations' => $evaluations,
            'latestEvaluation' => $latest,
            'myTrend' => $history,
            'criteriaScores' => $criteriaScores,
            'criteriaTotal' => Criterion::active()->count(),
            'myPlans' => $employee
                ? ImprovementPlan::with('period')->where('employee_id', $employee->id)->latest()->get()
                : collect(),
        ];
    }
}
