@extends('layouts.app')

@section('title', __('app.evaluations.detail_title'))

@php
    $submitted = $evaluation->isSubmitted();
    $grade = $submitted ? \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) : null;
@endphp

@php
    $canManage = (auth()->user()->isAdmin() || $evaluation->evaluator_id === auth()->id())
        && (auth()->user()->isAdmin() || $evaluation->period?->isOpenForEditing());
    $canRequestFeedback = $canRequestFeedback ?? $canManage;
    $canEditPeriod = auth()->user()->isAdmin()
        && \App\Support\PageAccess::allows(auth()->user(), 'periods')
        && $evaluation->period;
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ auth()->user()->isEmployee() ? route('evaluations.index') : route('evaluations.team') }}" class="btn-secondary">
            <svg class="h-4 w-4 rotate-180"><use href="#i-arrow"/></svg>
            {{ __('app.actions.back') }}
        </a>

        <div class="flex flex-wrap items-center gap-2">
            @if ($submitted)
                <a href="{{ route('evaluations.pdf', $evaluation) }}" class="btn-secondary">
                    <svg class="h-4 w-4"><use href="#i-download"/></svg>
                    {{ __('app.evaluations.pdf') }}
                </a>
            @endif
            @if ($submitted && auth()->user()->isAdmin())
                <form method="POST" action="{{ route('evaluations.reopen', $evaluation) }}"
                      onsubmit="return confirm('{{ __('app.evaluations.confirm_reopen') }}')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-secondary" title="{{ __('app.evaluations.reopen_hint') }}">
                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                        {{ __('app.evaluations.reopen') }}
                    </button>
                </form>
            @endif
            @if (! $submitted && $canManage)
                <a href="{{ route('evaluations.edit', $evaluation) }}" class="btn-primary">
                    <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                    {{ __('app.actions.edit') }}
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl bg-indigo-50 text-base font-extrabold text-indigo-600">
                            @if ($evaluation->employee?->photoUrl())
                                <img src="{{ $evaluation->employee->photoUrl() }}" alt="{{ $evaluation->employee->name }}" class="h-full w-full object-cover">
                            @else
                                {{ $evaluation->employee?->initials() ?? '?' }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-extrabold text-slate-900">{{ $evaluation->employee?->name }}</h2>
                            <p class="text-sm text-slate-500">
                                {{ $evaluation->employee?->position?->name ?? '-' }} · {{ $evaluation->employee?->department?->name ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-400">{{ $evaluation->period?->name }}</p>
                        </div>
                        <span class="{{ $submitted ? 'badge-green' : 'badge-amber' }}">
                            {{ __('app.evaluations.statuses.'.$evaluation->status) }}
                        </span>
                    </div>

                    <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.common.evaluator') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $evaluation->evaluator?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.evaluations.submitted_at') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">
                                {{ $evaluation->submitted_at?->translatedFormat('d F Y H:i') ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.common.grade') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">
                                {{ $grade ? $grade.' · '.__('app.grades.'.$grade) : '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.evaluations.scores') }}</h3>
                    <span class="text-xs text-slate-400">{{ __('app.evaluations.scale_hint') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-stable w-full min-w-[50rem]">
                        <colgroup>
                            <col>
                            <col class="w-[7rem]">
                            <col class="w-[7rem]">
                            <col class="w-[18rem]">
                        </colgroup>
                        <thead class="table-head">
                            <tr>
                                <th>{{ __('app.criteria.title') }}</th>
                                <th class="text-center">{{ __('app.common.weight') }}</th>
                                <th class="text-center">{{ __('app.common.score') }}</th>
                                <th>{{ __('app.common.comment') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($scores as $score)
                                <tr class="table-row">
                                    <td>
                                        <p class="font-semibold text-slate-800">{{ $score->criterion?->name }}</p>
                                        @if ($score->criterion?->description)
                                            <p class="text-xs text-slate-400">{{ $score->criterion->description }}</p>
                                        @endif
                                    </td>
                                    <td class="text-center text-slate-500">{{ rtrim(rtrim(number_format((float) $score->criterion?->weight, 2), '0'), '.') }}%</td>
                                    <td class="text-center">
                                        <span class="badge-indigo">{{ $score->score }} / 5</span>
                                    </td>
                                    <td class="max-w-xs text-sm text-slate-500">{{ $score->comment ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state">{{ __('app.common.not_yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @php
                $periodGoals = $evaluation->employee?->goals?->where('period_id', $evaluation->period_id) ?? collect();
            @endphp
            @if ($periodGoals->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-bold text-slate-900">{{ __('app.evaluations.kpi') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($periodGoals as $goal)
                            @php $percent = $goal->achievementPercent(); @endphp
                            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $goal->title }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ __('app.goals.target') }}:
                                        {{ rtrim(rtrim(number_format((float) $goal->target_value, 2), '0'), '.') }} {{ $goal->unit }}
                                    </p>
                                </div>
                                <span class="{{ $percent !== null && $percent >= 100 ? 'badge-green' : 'badge-amber' }}">
                                    {{ $percent !== null ? number_format($percent, 1).'%' : __('app.common.not_yet') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-100 px-5 py-2 text-xs text-slate-400">{{ __('app.evaluations.kpi_hint') }}</div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.feedback.request_360') }}</h3>
                    <span class="badge-indigo">{{ $evaluation->peerFeedbacks->count() }} {{ __('app.feedback.reviewers') }}</span>
                </div>
                <div class="card-body space-y-4">
                    @if ($submitted && $evaluation->peerScore() !== null)
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('app.feedback.manager_score') }}</p>
                                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format((float) $evaluation->manager_score, 1) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('app.feedback.peer_score') }}</p>
                                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format((float) $evaluation->peerScore(), 1) }}</p>
                            </div>
                            <div class="rounded-xl bg-indigo-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-indigo-500">{{ __('app.evaluations.final_score') }}</p>
                                <p class="mt-1 text-2xl font-extrabold text-indigo-700">{{ number_format((float) $evaluation->final_score, 1) }}</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">{{ __('app.feedback.combined_hint') }}</p>
                    @endif

                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-100">
                        @forelse ($evaluation->peerFeedbacks as $feedback)
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $canRequestFeedback ? ($feedback->reviewer?->name ?? '-') : __('app.feedback.relationships.'.$feedback->relationship) }}
                                        </p>
                                        <p class="text-xs text-slate-400">{{ __('app.feedback.relationships.'.$feedback->relationship) }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($feedback->isSubmitted())
                                            <span class="badge-green">{{ $feedback->score }}/5</span>
                                        @else
                                            <span class="badge-amber">{{ __('app.feedback.pending') }}</span>
                                        @endif
                                        @if ($canRequestFeedback && ! $feedback->isSubmitted())
                                            <form method="POST" action="{{ route('evaluations.reviewers.destroy', [$evaluation, $feedback]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="h-4 w-4"><use href="#i-x"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                @if ($feedback->comment)
                                    <p class="mt-1 text-xs text-slate-500">{{ $feedback->comment }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state">{{ __('app.feedback.empty') }}</div>
                        @endforelse
                    </div>

                    @if ($canRequestFeedback && $reviewerOptions->isNotEmpty())
                        <form method="POST" action="{{ route('evaluations.reviewers.store', $evaluation) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div class="min-w-[180px] flex-1">
                                <label class="label" for="reviewer_id">{{ __('app.feedback.reviewer') }}</label>
                                <select id="reviewer_id" name="reviewer_id" class="input" required>
                                    <option value="">— {{ __('app.employees.choose') }} —</option>
                                    @foreach ($reviewerOptions as $reviewer)
                                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }} ({{ $reviewer->roleLabel() }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="relationship">{{ __('app.feedback.relationship') }}</label>
                                <select id="relationship" name="relationship" class="input">
                                    @foreach (['peer', 'subordinate', 'other'] as $relationship)
                                        <option value="{{ $relationship }}">{{ __('app.feedback.relationships.'.$relationship) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn-secondary">{{ __('app.feedback.invite') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.plans.title') }}</h3>
                    <span class="badge-slate">{{ $evaluation->improvementPlans->count() }}</span>
                </div>
                <div class="card-body space-y-4">
                    @if ($submitted && (float) $evaluation->final_score < 60)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ __('app.plans.hint') }}
                        </div>
                    @endif

                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-100">
                        @forelse ($evaluation->improvementPlans as $plan)
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ $plan->title }}</p>
                                        <p class="text-xs text-slate-400">
                                            {{ __('app.plans.due_date') }}: {{ $plan->due_date?->format('d M Y') ?? '-' }}
                                        </p>
                                    </div>
                                    <span class="{{ $plan->status === 'completed' ? 'badge-green' : ($plan->status === 'cancelled' ? 'badge-slate' : 'badge-amber') }}">
                                        {{ __('app.plans.statuses.'.$plan->status) }}
                                    </span>
                                </div>
                                @if ($plan->actions)
                                    <p class="mt-1 text-xs text-slate-500">{{ $plan->actions }}</p>
                                @endif

                                @if ($canManage)
                                    <div class="mt-2 flex flex-wrap items-end gap-2">
                                        <form method="POST" action="{{ route('evaluations.plans.update', [$evaluation, $plan]) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="label !mb-1 text-xs">{{ __('app.plans.status') }}</label>
                                                <select name="status" class="input !py-1.5 text-xs" onchange="this.form.submit()">
                                                    @foreach (['open', 'in_progress', 'completed', 'cancelled'] as $status)
                                                        <option value="{{ $status }}" @selected($plan->status === $status)>{{ __('app.plans.statuses.'.$status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="label !mb-1 text-xs">{{ __('app.plans.due_date') }}</label>
                                                <input type="date" name="due_date" value="{{ $plan->due_date?->format('Y-m-d') }}" class="input !py-1.5 text-xs">
                                            </div>
                                            <input type="hidden" name="actions" value="{{ $plan->actions }}">
                                            <button type="submit" class="btn-secondary btn-sm">{{ __('app.actions.save') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('evaluations.plans.destroy', [$evaluation, $plan]) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state">{{ __('app.plans.empty') }}</div>
                        @endforelse
                    </div>

                    @if ($canManage)
                        <form method="POST" action="{{ route('evaluations.plans.store', $evaluation) }}" class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="plan-title">{{ __('app.common.name') }}</label>
                                    <input id="plan-title" name="title" value="{{ __('app.plans.title') }}" class="input" required>
                                </div>
                                <div>
                                    <label class="label" for="plan-due">{{ __('app.plans.due_date') }}</label>
                                    <input id="plan-due" type="date" name="due_date" class="input">
                                </div>
                            </div>
                            <div>
                                <label class="label" for="plan-actions">{{ __('app.plans.actions') }}</label>
                                <textarea id="plan-actions" name="actions" rows="2" class="input"></textarea>
                            </div>
                            <button type="submit" class="btn-secondary">{{ __('app.plans.add') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($evaluation->notes)
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-bold text-slate-900">{{ __('app.common.notes') }}</h3>
                    </div>
                    <div class="card-body text-sm leading-relaxed text-slate-600">{{ $evaluation->notes }}</div>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card overflow-hidden bg-slate-900 text-white">
                <div class="p-6">
                    <p class="text-sm text-slate-400">{{ __('app.evaluations.final_score') }}</p>
                    <p class="mt-2 text-6xl font-extrabold">{{ $submitted ? number_format((float) $evaluation->final_score, 1) : '—' }}</p>
                    <div class="mt-3 flex items-center gap-2">
                        @if ($submitted)
                            <span class="badge bg-white/10 text-white ring-1 ring-white/20">{{ $grade }}</span>
                            <span class="text-sm text-slate-300">{{ __('app.grades.'.$grade) }}</span>
                        @else
                            <span class="text-sm text-slate-400">{{ __('app.common.not_yet') }}</span>
                        @endif
                    </div>
                </div>
                <div class="border-t border-white/10 bg-white/5 px-6 py-3 text-xs text-slate-400">
                    {{ __('app.evaluations.weighted_note') }}
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.common.grade') }}</h3>
                </div>
                <div class="space-y-2 p-5 text-sm">
                    @foreach (['A' => 90, 'B' => 80, 'C' => 70, 'D' => 60, 'E' => 0] as $letter => $min)
                        <div class="flex items-center justify-between rounded-lg px-3 py-1.5 {{ $grade === $letter ? 'bg-indigo-50 font-bold text-indigo-700' : 'text-slate-500' }}">
                            <span>{{ $letter }} · {{ __('app.grades.'.$letter) }}</span>
                            <span class="text-xs">{{ $min > 0 ? '≥ '.$min : '< 60' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($evaluation->period)
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-bold text-slate-900">{{ __('app.evaluations.period_section') }}</h3>
                        @if ($canEditPeriod)
                            <button type="button" class="btn-ghost" data-modal-open="evaluation-period-edit">
                                <svg class="h-3.5 w-3.5"><use href="#i-pencil"/></svg>
                                {{ __('app.actions.edit') }}
                            </button>
                        @endif
                    </div>
                    <dl class="space-y-2.5 p-5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">{{ __('app.common.name') }}</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ $evaluation->period->name }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">{{ __('app.periods.type') }}</dt>
                            <dd class="text-right font-medium text-slate-700">{{ __('app.periods.types.'.$evaluation->period->type) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">{{ __('app.periods.range') }}</dt>
                            <dd class="text-right font-medium text-slate-700">
                                {{ $evaluation->period->start_date?->format('d M Y') }}<br>{{ $evaluation->period->end_date?->format('d M Y') }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">{{ __('app.common.status') }}</dt>
                            <dd>
                                @if ($evaluation->period->status === 'active')
                                    <span class="badge-green">{{ __('app.periods.statuses.active') }}</span>
                                @elseif ($evaluation->period->status === 'closed')
                                    <span class="badge-slate">{{ __('app.periods.statuses.closed') }}</span>
                                @else
                                    <span class="badge-amber">{{ __('app.periods.statuses.draft') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>
    </div>

    @if ($canEditPeriod)
        <x-modal-form id="evaluation-period-edit" :title="__('app.periods.edit_title')" :subtitle="__('app.evaluations.period_section')" :action="route('periods.update', $evaluation->period)" method="PUT">
            @include('periods._fields', ['period' => $evaluation->period, 'prefix' => 'evaluation-period-edit'])
        </x-modal-form>
    @endif
@endsection
