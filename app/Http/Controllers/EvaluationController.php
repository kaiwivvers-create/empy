<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\User;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $evaluations = Evaluation::query()
            ->with(['employee.department', 'employee.position', 'period', 'evaluator'])
            ->when($user->isManager(), function ($query) use ($user) {
                $query->where(fn ($inner) => $inner
                    ->where('evaluator_id', $user->id)
                    ->orWhereHas('employee', fn ($employee) => $employee->where('manager_id', $user->employee?->id)));
            })
            ->when($user->isEmployee(), fn ($query) => $query
                ->whereHas('employee', fn ($employee) => $employee->where('user_id', $user->id)))
            ->when($request->filled('period'), fn ($query) => $query->where('period_id', $request->integer('period')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('department'), fn ($query) => $query
                ->whereHas('employee', fn ($employee) => $employee->where('department_id', $request->integer('department'))))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->whereHas('employee', fn ($employee) => $employee->where('name', 'like', $search));
            })
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $canCreate = $user->isAdmin() || $user->isManager();
        $activePeriods = EvaluationPeriod::where('status', 'active')->orderByDesc('start_date')->get();
        $evaluableEmployees = $canCreate ? $this->evaluableEmployees($user) : collect();

        return view('evaluations.index', [
            'evaluations' => $evaluations,
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only('period', 'status', 'department', 'search'),
            'canCreate' => $canCreate,
            'evaluableEmployees' => $evaluableEmployees,
            'activePeriods' => $activePeriods,
            'openModal' => $request->string('open')->toString() === 'evaluation-create' ? 'evaluation-create' : null,
            'selectedEmployee' => $request->integer('employee'),
            'selectedPeriod' => $request->integer('period'),
        ]);
    }

    /**
     * Manager workspace: pick an employee and score inside a slide-in drawer.
     */
    public function team(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403, __('app.common.no_access'));

        $periods = EvaluationPeriod::withCount('evaluations')->orderByDesc('start_date')->get();

        $period = $request->filled('period')
            ? $periods->firstWhere('id', $request->integer('period'))
            : ($periods->firstWhere('status', 'active') ?? $periods->first());

        $employees = $this->evaluableEmployees($user)
            ->when($request->filled('department'), fn ($collection) => $collection
                ->where('department_id', $request->integer('department')))
            ->when($request->filled('search'), function ($collection) use ($request) {
                $search = mb_strtolower($request->string('search')->toString());

                return $collection->filter(fn (Employee $employee) => str_contains(mb_strtolower($employee->name), $search)
                    || str_contains(mb_strtolower((string) $employee->nip), $search));
            })
            ->values();

        $evaluations = $period
            ? Evaluation::where('period_id', $period->id)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->with('scores')
                ->get()
                ->keyBy('employee_id')
            : collect();

        $drawerEmployee = $request->filled('employee')
            ? $employees->firstWhere('id', $request->integer('employee'))
            : null;

        $drawerEvaluation = $drawerEmployee ? ($evaluations[$drawerEmployee->id] ?? null) : null;
        $criteria = Criterion::active()->get();

        $canScore = $drawerEvaluation
            ? ! $drawerEvaluation->isSubmitted()
                && ($user->isAdmin() || $drawerEvaluation->evaluator_id === $user->id)
                && ($period?->isActive() || $user->isAdmin())
            : false;

        $canStart = ! $drawerEvaluation && $period?->isActive();

        return view('evaluations.team', [
            'periods' => $periods,
            'period' => $period,
            'departments' => Department::orderBy('name')->get(),
            'employees' => $employees,
            'evaluations' => $evaluations,
            'criteria' => $criteria,
            'drawerEmployee' => $drawerEmployee,
            'drawerEvaluation' => $drawerEvaluation,
            'canScore' => $canScore,
            'canStart' => $canStart,
            'filters' => $request->only('period', 'department', 'search'),
            'purposes' => array_keys(__('app.evaluations.purposes')),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $user = $request->user();
        $activePeriods = EvaluationPeriod::where('status', 'active')->orderByDesc('start_date')->get();

        $employees = $this->evaluableEmployees($user);

        if ($employees->isEmpty()) {
            return redirect()->route('evaluations.index')->with('error', __('app.evaluations.no_team'));
        }

        // Opening the form is still useful without an active period: the modal
        // offers a compact period form so one can be created on the spot.
        return redirect()
            ->route('evaluations.index', [
                'open' => 'evaluation-create',
                'employee' => $request->integer('employee') ?: null,
                'period' => $request->integer('period') ?: null,
            ])
            ->with('error', __('app.evaluations.no_active_period'));
    }

    public function store(Request $request): RedirectResponse
    {
        // The inline period fields only exist in the form when no active
        // period is available, so their presence drives the branch.
        $creatingPeriod = $request->has('period_name')
            || $request->has('period_type')
            || $request->has('period_start');

        $rules = [
            'employee_id' => ['required', 'exists:employees,id'],
            'period_id' => [$creatingPeriod ? 'nullable' : 'required', 'exists:evaluation_periods,id'],
            'purpose' => ['nullable', 'in:annual,promotion,coaching,probation,other'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($creatingPeriod) {
            $rules['period_name'] = ['required', 'string', 'max:255'];
            $rules['period_type'] = ['required', 'in:monthly,quarterly,semester,annual'];
            $rules['period_start'] = ['required', 'date'];
            $rules['period_end'] = ['required', 'date', 'after_or_equal:period_start'];
        }

        $data = $request->validate($rules);

        $user = $request->user();
        $employee = Employee::findOrFail($data['employee_id']);

        if ($creatingPeriod) {
            if (EvaluationPeriod::where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'period_id' => __('app.evaluations.active_period_exists'),
                ]);
            }

            $period = EvaluationPeriod::create([
                'name' => $data['period_name'],
                'type' => $data['period_type'],
                'start_date' => $data['period_start'],
                'end_date' => $data['period_end'],
                'status' => 'active',
            ]);

            ActivityLogger::record('created', 'Evaluation period created: '.$period->name, $period);
        } else {
            $period = EvaluationPeriod::findOrFail($data['period_id']);
        }

        if (! $user->isAdmin() && $employee->manager_id !== $user->employee?->id) {
            throw ValidationException::withMessages(['employee_id' => __('app.evaluations.not_managed')]);
        }

        if (! $period->isActive()) {
            throw ValidationException::withMessages(['period_id' => __('app.evaluations.period_not_active')]);
        }

        if (Evaluation::where('period_id', $period->id)->where('employee_id', $employee->id)->exists()) {
            throw ValidationException::withMessages(['employee_id' => __('app.evaluations.already_exists')]);
        }

        $evaluation = Evaluation::create([
            'period_id' => $period->id,
            'employee_id' => $employee->id,
            'evaluator_id' => $user->id,
            'status' => 'draft',
            'purpose' => $data['purpose'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        ActivityLogger::record('created', 'Evaluation created for '.$employee->name.' ('.$period->name.')', $evaluation);

        return redirect()
            ->route('evaluations.team', ['employee' => $employee->id, 'period' => $period->id])
            ->with('success', __('app.evaluations.success_created'));
    }

    public function show(Evaluation $evaluation): View
    {
        $this->authorizeView(auth()->user(), $evaluation);

        $evaluation->load([
            'employee.department',
            'employee.position',
            'employee.manager',
            'employee.goals',
            'period',
            'evaluator',
            'peerFeedbacks.reviewer',
            'improvementPlans.creator',
        ]);

        $invitedIds = $evaluation->peerFeedbacks->pluck('reviewer_id')->all();

        $reviewerOptions = User::query()
            ->when(! auth()->user()->isSuperAdmin(), fn ($query) => $query->where('role', '!=', User::ROLE_SUPER_ADMIN))
            ->whereNotIn('id', array_filter([
                $evaluation->evaluator_id,
                $evaluation->employee?->user_id,
                ...$invitedIds,
            ]))
            ->orderBy('name')
            ->get();

        return view('evaluations.show', [
            'evaluation' => $evaluation,
            'scores' => $evaluation->scores()->with('criterion')->get(),
            'reviewerOptions' => $reviewerOptions,
        ]);
    }

    public function pdf(Evaluation $evaluation)
    {
        $this->authorizeView(auth()->user(), $evaluation);

        $evaluation->load([
            'employee.department',
            'employee.position',
            'employee.manager',
            'period',
            'evaluator',
            'peerFeedbacks',
        ]);

        $pdf = Pdf::loadView('evaluations.pdf', [
            'evaluation' => $evaluation,
            'scores' => $evaluation->scores()->with('criterion')->get(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('evaluation-'.$evaluation->employee?->nip.'-'.now()->format('Ymd').'.pdf');
    }

    public function edit(Evaluation $evaluation): RedirectResponse
    {
        $user = auth()->user();
        $this->authorizeScoring($user, $evaluation);

        if ($evaluation->isSubmitted()) {
            return redirect()->route('evaluations.show', $evaluation)
                ->with('error', __('app.evaluations.submitted_locked'));
        }

        return redirect()->route('evaluations.team', [
            'employee' => $evaluation->employee_id,
            'period' => $evaluation->period_id,
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeScoring($request->user(), $evaluation);

        if ($evaluation->isSubmitted()) {
            return back()->with('error', __('app.evaluations.submitted_locked'));
        }

        if (! $evaluation->period->isActive() && ! $request->user()->isAdmin()) {
            return back()->with('error', __('app.evaluations.period_not_active'));
        }

        $criteria = Criterion::active()->get();
        $rules = [
            'notes' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['nullable', 'in:annual,promotion,coaching,probation,other'],
            'return_to' => ['nullable', 'in:team'],
        ];

        foreach ($criteria as $criterion) {
            $rules["scores.{$criterion->id}"] = ['required', 'integer', 'min:1', 'max:5'];
            $rules["comments.{$criterion->id}"] = ['nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);
        $submit = $request->boolean('submit');
        $before = $evaluation->getAttributes();

        DB::transaction(function () use ($evaluation, $criteria, $data, $submit) {
            foreach ($criteria as $criterion) {
                $evaluation->scores()->updateOrCreate(
                    ['criteria_id' => $criterion->id],
                    [
                        'score' => $data['scores'][$criterion->id],
                        'comment' => $data['comments'][$criterion->id] ?? null,
                    ]
                );
            }

            $evaluation->notes = $data['notes'] ?? null;
            $evaluation->purpose = $data['purpose'] ?? null;

            if ($submit) {
                $managerScore = $evaluation->computeFinalScore();
                $evaluation->manager_score = $managerScore;
                $evaluation->final_score = $managerScore;
                $evaluation->status = 'submitted';
                $evaluation->submitted_at = now();
            }

            $evaluation->save();
        });

        ActivityLogger::record(
            $submit ? 'submitted' : 'updated',
            ($submit ? 'Evaluation submitted for ' : 'Evaluation saved for ').$evaluation->employee?->name,
            $evaluation,
            ActivityLogger::changes($evaluation, $before),
        );

        $returnToTeam = $request->input('return_to') === 'team';

        if ($submit && ! $returnToTeam) {
            return redirect()->route('evaluations.show', $evaluation)
                ->with('success', __('app.evaluations.success_submitted'));
        }

        if ($returnToTeam) {
            return redirect()
                ->route('evaluations.team', [
                    'employee' => $evaluation->employee_id,
                    'period' => $evaluation->period_id,
                ])
                ->with('success', $submit
                    ? __('app.evaluations.success_submitted')
                    : __('app.evaluations.success_updated'));
        }

        return redirect()->route('evaluations.show', $evaluation)
            ->with('success', __('app.evaluations.success_updated'));
    }

    /**
     * Put a submitted evaluation back into draft so it can be corrected.
     */
    public function reopen(Evaluation $evaluation): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, __('app.common.no_access'));

        if (! $evaluation->isSubmitted()) {
            return back()->with('error', __('app.evaluations.not_submitted'));
        }

        $before = $evaluation->getAttributes();

        $evaluation->update([
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        ActivityLogger::record(
            'reopened',
            'Evaluation reopened for '.$evaluation->employee?->name,
            $evaluation,
            ActivityLogger::changes($evaluation, $before),
        );

        return back()->with('success', __('app.evaluations.success_reopened'));
    }

    public function destroy(Evaluation $evaluation): RedirectResponse
    {
        if ($evaluation->isSubmitted()) {
            return back()->with('error', __('app.evaluations.delete_draft_only'));
        }

        ActivityLogger::record('deleted', 'Evaluation deleted for '.$evaluation->employee?->name, $evaluation);

        $evaluation->delete();

        return redirect()->route('evaluations.index')
            ->with('success', __('app.evaluations.success_deleted'));
    }

    private function evaluableEmployees(User $user)
    {
        return Employee::query()
            ->active()
            ->with(['department', 'position', 'manager'])
            ->when(! $user->isAdmin(), fn ($query) => $query->where('manager_id', $user->employee?->id))
            ->orderBy('name')
            ->get();
    }

    private function authorizeView(User $user, Evaluation $evaluation): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($evaluation->employee?->user_id === $user->id) {
            return;
        }

        if ($user->isManager() && ($evaluation->evaluator_id === $user->id
            || $evaluation->employee?->manager_id === $user->employee?->id)) {
            return;
        }

        abort(403, __('app.common.no_access'));
    }

    private function authorizeScoring(User $user, Evaluation $evaluation): void
    {
        if ($user->isAdmin() || $evaluation->evaluator_id === $user->id) {
            return;
        }

        abort(403, __('app.common.no_access'));
    }
}
