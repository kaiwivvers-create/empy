<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\PeerFeedback;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeerFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $feedback = PeerFeedback::with(['evaluation.employee', 'evaluation.period'])
            ->where('reviewer_id', $request->user()->id)
            ->orderByRaw('submitted_at is not null')
            ->latest()
            ->get();

        return view('feedback.index', compact('feedback'));
    }

    public function store(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeManage($request, $evaluation);

        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'relationship' => ['required', 'in:peer,subordinate,other'],
        ]);

        $reviewer = User::findOrFail($data['reviewer_id']);

        if ($reviewer->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(404);
        }

        if ($evaluation->employee?->user_id === $reviewer->id) {
            return back()->with('error', __('app.feedback.cannot_self'));
        }

        if ($evaluation->evaluator_id === $reviewer->id) {
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
        $this->authorizeManage($request, $evaluation);
        abort_unless($feedback->evaluation_id === $evaluation->id, 404);

        if ($feedback->isSubmitted()) {
            return back()->with('error', __('app.feedback.cannot_remove_submitted'));
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

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $feedback->getAttributes();

        $feedback->update([
            'score' => $data['score'],
            'comment' => $data['comment'] ?? null,
            'submitted_at' => now(),
        ]);

        $evaluation = $feedback->evaluation;

        if ($evaluation && $evaluation->isSubmitted()) {
            $evaluation->load('peerFeedbacks');
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
        $user = $request->user();

        if ($user->isAdmin() || $evaluation->evaluator_id === $user->id) {
            return;
        }

        abort(403, __('app.common.no_access'));
    }
}
