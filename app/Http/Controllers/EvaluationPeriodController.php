<?php

namespace App\Http\Controllers;

use App\Models\EvaluationPeriod;
use App\Support\ActivityLogger;
use App\Support\ReminderSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $showTrashed = $request->user()->isAdmin() && $request->boolean('trashed');

        $periods = EvaluationPeriod::withCount('evaluations')
            ->when($showTrashed, fn ($query) => $query->onlyTrashed())
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        [$openModal, $editItem] = $this->modalState($request);

        return view('periods.index', compact('periods', 'showTrashed', 'openModal', 'editItem'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('periods.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $period = EvaluationPeriod::create($this->validated($request));

        ActivityLogger::record('created', 'Evaluation period created: '.$period->name, $period);

        return redirect()->route('periods.index')->with('success', __('app.periods.success_created'));
    }

    public function edit(EvaluationPeriod $period): RedirectResponse
    {
        return redirect()->route('periods.index', ['open' => 'edit-'.$period->id]);
    }

    public function update(Request $request, EvaluationPeriod $period): RedirectResponse
    {
        $before = $period->getAttributes();

        $period->update($this->validated($request));

        ActivityLogger::record(
            'updated',
            'Evaluation period updated: '.$period->name,
            $period,
            ActivityLogger::changes($period, $before),
        );

        // Back to wherever the form was opened (periods page or an
        // evaluation detail page with the inline period editor).
        return back()->with('success', __('app.periods.success_updated'));
    }

    public function destroy(EvaluationPeriod $period): RedirectResponse
    {
        if ($period->evaluations()->exists()) {
            return back()->with('error', __('app.periods.delete_blocked'));
        }

        ActivityLogger::record('deleted', 'Evaluation period archived: '.$period->name, $period);

        $period->delete();

        return redirect()->route('periods.index')->with('success', __('app.periods.success_deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $period = EvaluationPeriod::onlyTrashed()->findOrFail($id);
        $period->restore();

        ActivityLogger::record('restored', 'Evaluation period restored: '.$period->name, $period);

        return redirect()->route('periods.index', ['trashed' => 1])
            ->with('success', __('app.periods.success_restored'));
    }

    public function activate(EvaluationPeriod $period): RedirectResponse
    {
        EvaluationPeriod::where('status', 'active')
            ->whereKeyNot($period->id)
            ->update(['status' => 'draft']);

        $before = $period->getAttributes();

        $period->update(['status' => 'active']);

        ActivityLogger::record(
            'activated',
            'Evaluation period activated: '.$period->name,
            $period,
            ActivityLogger::changes($period, $before),
        );

        return redirect()->route('periods.index')->with('success', __('app.periods.success_activated'));
    }

    public function close(EvaluationPeriod $period): RedirectResponse
    {
        $before = $period->getAttributes();

        $period->update(['status' => 'closed']);

        ActivityLogger::record(
            'closed',
            'Evaluation period closed: '.$period->name,
            $period,
            ActivityLogger::changes($period, $before),
        );

        return redirect()->route('periods.index')->with('success', __('app.periods.success_closed'));
    }

    public function remind(EvaluationPeriod $period): RedirectResponse
    {
        $sent = ReminderSender::send($period);

        ActivityLogger::record('reminded', 'Evaluation reminders sent for '.$period->name.' to '.$sent->count().' manager(s)', $period);

        return redirect()->route('periods.index')->with(
            $sent->isEmpty() ? 'error' : 'success',
            $sent->isEmpty()
                ? __('app.reminders.none_pending')
                : __('app.reminders.sent', ['count' => $sent->count(), 'period' => $period->name]),
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:monthly,quarterly,semester,annual'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed'],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?EvaluationPeriod}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = EvaluationPeriod::find($matches[1]);
            $open = $editItem ? 'period-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'period-create';
        }

        return [$open ?: null, $editItem];
    }
}
