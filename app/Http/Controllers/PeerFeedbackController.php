<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\PeerFeedback;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PeerFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $feedback = PeerFeedback::with(['evaluation.employee', 'evaluation.period'])
            ->whereHas('evaluation')
            ->where('reviewer_id', $user->id)
            ->orderByRaw('submitted_at is not null')
            ->latest()
            ->get();

        // Employees receive feedback invitations, but cannot send requests.
        $canSend = ! $user->isEmployee();
        $evaluations = collect();
        $periods = collect();

        if ($canSend) {
            $evaluations = Evaluation::query()
                ->with(['employee.department', 'employee.position', 'period'])
                ->when(! $user->isAdmin(), function ($query) use ($user) {
                    $query->where(function ($inner) use ($user) {
                        $inner->where('evaluator_id', $user->id);

                        if ($user->employee) {
                            $inner->orWhereHas('employee', fn ($employee) => $employee->where('manager_id', $user->employee->id));
                        }
                    });
                })
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->filter(fn (Evaluation $evaluation) => $evaluation->userCanManageFeedback($user))
                ->values();

            $periods = $evaluations->pluck('period')
                ->filter()
                ->unique('id')
                ->sortByDesc('start_date')
                ->values();
        }

        $criteria = Criterion::active()->get();

        // Kept to login accounts only so hidden super admins are never offered.
        $reviewerOptions = $canSend
            ? User::query()
                ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('role', '!=', User::ROLE_SUPER_ADMIN))
                ->orderBy('name')
                ->get()
            : collect();

        return view('feedback.index', compact('feedback', 'canSend', 'evaluations', 'periods', 'criteria', 'reviewerOptions'));
    }

    public function store(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeManage($request, $evaluation);

        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'relationship' => ['required', 'in:peer,subordinate,other'],
        ]);

        return $this->createRequest($request, $evaluation, $data);
    }

    public function sendRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'reviewer_id' => ['required', 'exists:users,id'],
            'relationship' => ['required', 'in:peer,subordinate,other'],
        ]);

        $evaluation = Evaluation::findOrFail($data['evaluation_id']);

        abort_unless(
            $evaluation->userCanManageFeedback($request->user()),
            403,
            __('app.common.no_access'),
        );

        return $this->createRequest($request, $evaluation, $data);
    }

    private function createRequest(Request $request, Evaluation $evaluation, array $data): RedirectResponse
    {
        $reviewer = User::findOrFail($data['reviewer_id']);

        if ($reviewer->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(404);
        }

        if ($evaluation->employee?->user_id === $reviewer->id) {
            return back()->with('error', __('app.feedback.cannot_self'));
        }

        if ($evaluation->evaluator_id === $reviewer->id && $reviewer->id !== $request->user()->id) {
            return back()->with('error', __('app.feedback.cannot_evaluator'));
        }

        if ($evaluation->peerFeedbacks()->where('reviewer_id', $reviewer->id)->exists()) {
            return back()->with('error', __('app.feedback.already_invited'));
        }

        $feedback = $evaluation->peerFeedbacks()->create([
            'reviewer_id' => $reviewer->id,
            'relationship' => $data['relationship'],
        ]);

        ActivityLogger::record(
            'created',
            '360 feedback requested from '.$reviewer->name.' for '.$evaluation->employee?->name,
            $feedback,
        );

        return back()->with('success', __('app.feedback.invited'));
    }

    public function destroy(Request $request, Evaluation $evaluation, PeerFeedback $feedback): RedirectResponse
    {
        abort_unless($feedback->evaluation_id === $evaluation->id, 404);

        if ($feedback->isSubmitted()) {
            return back()->with('error', __('app.feedback.cannot_remove_submitted'));
        }

        $isReviewerCancelling = $feedback->reviewer_id === $request->user()->id;

        if (! $isReviewerCancelling) {
            $this->authorizeManage($request, $evaluation);
        }

        ActivityLogger::record(
            'deleted',
            '360 feedback request removed for '.$evaluation->employee?->name,
            $feedback,
        );

        $feedback->delete();

        return back()->with('success', __('app.feedback.removed'));
    }

    public function submit(Request $request, PeerFeedback $feedback): RedirectResponse
    {
        abort_unless($feedback->reviewer_id === $request->user()->id, 403, __('app.common.no_access'));

        if ($feedback->isSubmitted()) {
            return redirect()->route('feedback.index')->with('error', __('app.feedback.already_submitted'));
        }

        $criteria = Criterion::active()->get();

        $rules = [
            'score' => ['nullable', 'integer', 'min:1', 'max:5', 'required_without:scores'],
            'scores' => ['nullable', 'array', 'required_without:score'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'comments' => ['nullable', 'array'],
        ];

        foreach ($criteria as $criterion) {
            $rules["scores.{$criterion->id}"] = [$request->has('scores') ? 'required' : 'nullable', 'integer', 'min:1', 'max:5'];
            $rules["comments.{$criterion->id}"] = ['nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);
        $before = $feedback->getAttributes();

        $weighted = 0.0;
        $totalWeight = 0.0;

        foreach ($criteria as $criterion) {
            $rating = $data['scores'][$criterion->id] ?? null;

            if ($rating === null) {
                continue;
            }

            $weight = (float) $criterion->weight;
            $weighted += (float) $rating * $weight;
            $totalWeight += $weight;
        }

        // The stored score keeps the legacy 1-5 shape; the weighted value is
        // derived from the criterion ratings whenever they are supplied.
        $overallScore = $totalWeight > 0
            ? (int) round($weighted / $totalWeight)
            : (int) ($data['score'] ?? 0);

        DB::transaction(function () use ($feedback, $criteria, $data, $overallScore) {
            $feedback->update([
                'score' => $overallScore,
                'comment' => $data['comment'] ?? null,
                'submitted_at' => now(),
            ]);

            foreach ($criteria as $criterion) {
                $rating = $data['scores'][$criterion->id] ?? null;

                if ($rating === null) {
                    continue;
                }

                $feedback->scores()->updateOrCreate(
                    ['criteria_id' => $criterion->id],
                    ['score' => $rating, 'comment' => $data['comments'][$criterion->id] ?? null],
                );
            }
        });

        $evaluation = $feedback->evaluation;

        if ($evaluation && $evaluation->isSubmitted()) {
            $evaluation->load('peerFeedbacks.scores.criterion');
            $evaluation->update(['final_score' => $evaluation->recomputeFinalScore()]);
        }

        ActivityLogger::record(
            'submitted',
            '360 feedback submitted for '.$evaluation?->employee?->name,
            $feedback,
            ActivityLogger::changes($feedback, $before),
        );

        return redirect()->route('feedback.index')->with('success', __('app.feedback.success_submitted'));
    }

    private function authorizeManage(Request $request, Evaluation $evaluation): void
    {
        abort_unless(
            $evaluation->userCanManageFeedback($request->user()),
            403,
            __('app.common.no_access'),
        );
    }
}
