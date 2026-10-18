<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\ImprovementPlan;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImprovementPlanController extends Controller
{
    public function store(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorizeManage($request, $evaluation);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'actions' => ['nullable', 'string', 'max:4000'],
            'due_date' => ['nullable', 'date', 'after:today'],
        ]);

        $plan = $evaluation->improvementPlans()->create([
            ...$data,
            'employee_id' => $evaluation->employee_id,
            'period_id' => $evaluation->period_id,
            'created_by' => $request->user()->id,
            'status' => 'open',
        ]);

        ActivityLogger::record(
            'created',
            'Improvement plan created for '.$evaluation->employee?->name,
            $plan,
        );

        return redirect()->route('evaluations.show', $evaluation)
            ->with('success', __('app.plans.success_created'));
    }

    public function update(Request $request, Evaluation $evaluation, ImprovementPlan $plan): RedirectResponse
    {
        $this->authorizeManage($request, $evaluation);
        abort_unless($plan->evaluation_id === $evaluation->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,completed,cancelled'],
            'actions' => ['nullable', 'string', 'max:4000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $before = $plan->getAttributes();

        $plan->update([
            ...$data,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ]);

        ActivityLogger::record(
            'updated',
            'Improvement plan updated for '.$evaluation->employee?->name,
            $plan,
            ActivityLogger::changes($plan, $before),
        );

        return redirect()->route('evaluations.show', $evaluation)
            ->with('success', __('app.plans.success_updated'));
    }

    public function destroy(Request $request, Evaluation $evaluation, ImprovementPlan $plan): RedirectResponse
    {
        $this->authorizeManage($request, $evaluation);
        abort_unless($plan->evaluation_id === $evaluation->id, 404);

        ActivityLogger::record('deleted', 'Improvement plan deleted for '.$evaluation->employee?->name, $plan);

        $plan->delete();

        return redirect()->route('evaluations.show', $evaluation)
            ->with('success', __('app.plans.success_deleted'));
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
