@extends('layouts.app')

@section('title', __('app.feedback.title'))

@php
    $pending = $feedback->filter(fn ($item) => ! $item->isSubmitted());
    $completed = $feedback->filter(fn ($item) => $item->isSubmitted());
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.feedback.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.feedback.subtitle') }}</p>
        </div>

        @if ($canSend && $evaluations->isNotEmpty())
            <button type="button" class="btn-primary" data-modal-open="feedback-request-modal">
                <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                {{ __('app.feedback.send_title') }}
            </button>
        @endif
    </div>

    <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        {{ __('app.feedback.combined_hint') }} · {{ __('app.feedback.score_hint') }}
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('app.feedback.pending') }} ({{ $pending->count() }})</h3>

            @forelse ($pending as $item)
                <div class="card">
                    <form method="POST" action="{{ route('feedback.submit', $item) }}">
                    @csrf
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h4 class="font-bold text-slate-900">{{ $item->evaluation?->employee?->name }}</h4>
                                <p class="text-xs text-slate-500">
                                    {{ $item->evaluation?->employee?->department?->name ?? '-' }} · {{ $item->evaluation?->period?->name }}
                                    · {{ __('app.feedback.relationships.'.$item->relationship) }}
                                </p>
                            </div>
                            <span class="badge-amber">{{ __('app.feedback.pending') }}</span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @for ($score = 1; $score <= 5; $score++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="score" value="{{ $score }}" class="peer sr-only" required>
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-500 transition hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white">
                                        {{ $score }}
                                    </span>
                                </label>
                            @endfor
                        </div>
                        @error('score') <p class="form-error">{{ $message }}</p> @enderror

                        <div class="mt-3">
                            <label class="label" for="comment-{{ $item->id }}">{{ __('app.common.comment') }} ({{ __('app.common.optional') }})</label>
                            <textarea id="comment-{{ $item->id }}" name="comment" rows="2" class="input" placeholder="{{ __('app.common.comment') }}..."></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                        <button type="submit" class="btn-primary">{{ __('app.feedback.submit') }}</button>
                    </div>
                    </form>
                    <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                        <form method="POST" action="{{ route('evaluations.reviewers.destroy', [$item->evaluation, $item]) }}" onsubmit="return confirm('{{ __('app.feedback.cancel_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ghost text-rose-600 hover:bg-rose-50">
                                {{ __('app.feedback.cancel') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="empty-state">{{ __('app.feedback.no_pending') }}</div>
                </div>
            @endforelse
        </div>

        <div class="card h-fit">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.feedback.completed') }}</h3>
                <span class="badge-green">{{ $completed->count() }}</span>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($completed as $item)
                    <div class="px-5 py-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $item->evaluation?->employee?->name }}</p>
                            <span class="badge-indigo">{{ $item->score }}/5</span>
                        </div>
                        <p class="text-xs text-slate-400">{{ $item->evaluation?->period?->name }}</p>
                        @if ($item->comment)
                            <p class="mt-1 text-xs text-slate-500">{{ $item->comment }}</p>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">{{ __('app.feedback.empty') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($canSend && $evaluations->isNotEmpty())
        <x-modal-form
            id="feedback-request-modal"
            :title="__('app.feedback.send_title')"
            :subtitle="__('app.feedback.send_hint')"
            :action="route('feedback.requests.store')"
            :submit-label="__('app.feedback.invite')">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="feedback-period-filter">{{ __('app.feedback.period_filter') }}</label>
                    <select id="feedback-period-filter" class="input" data-feedback-period-filter>
                        <option value="">{{ __('app.feedback.all_periods') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="feedback-evaluation-id">{{ __('app.feedback.evaluation') }}</label>
                    <select id="feedback-evaluation-id" name="evaluation_id" class="input" required data-feedback-evaluation>
                        <option value="">— {{ __('app.employees.choose') }} —</option>
                        @foreach ($evaluations as $evaluation)
                            <option value="{{ $evaluation->id }}"
                                    data-period="{{ $evaluation->period_id }}"
                                    @selected(old('evaluation_id') == $evaluation->id)>
                                {{ $evaluation->employee?->name }} · {{ $evaluation->period?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="feedback-reviewer-id">{{ __('app.feedback.reviewer') }}</label>
                    <select id="feedback-reviewer-id"
                            class="input cursor-not-allowed bg-slate-100 text-slate-500 disabled:opacity-80"
                            data-feedback-reviewer-self
                            disabled>
                        <option selected>{{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})</option>
                    </select>
                    <input type="hidden" name="reviewer_id" value="{{ auth()->user()->id }}">
                </div>

                <div>
                    <label class="label" for="feedback-relationship">{{ __('app.feedback.relationship') }}</label>
                    <select id="feedback-relationship" name="relationship" class="input">
                        @foreach (['peer', 'subordinate', 'other'] as $relationship)
                            <option value="{{ $relationship }}" @selected(old('relationship', 'peer') === $relationship)>
                                {{ __('app.feedback.relationships.'.$relationship) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-modal-form>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const periodFilter = document.querySelector('[data-feedback-period-filter]');
            const evaluationSelect = document.querySelector('[data-feedback-evaluation]');

            if (! periodFilter || ! evaluationSelect) {
                return;
            }

            const evaluationOptions = Array.from(evaluationSelect.querySelectorAll('option[data-period]'));

            const applyFilter = () => {
                const period = periodFilter.value;
                let firstVisible = null;

                evaluationOptions.forEach((option) => {
                    const matches = ! period || option.dataset.period === period;
                    option.hidden = ! matches;
                    option.disabled = ! matches;

                    if (matches && ! firstVisible) {
                        firstVisible = option;
                    }
                });

                if (evaluationSelect.selectedOptions[0]?.disabled) {
                    evaluationSelect.value = firstVisible?.value ?? '';
                }
            };

            periodFilter.addEventListener('change', applyFilter);
            applyFilter();
        });
    </script>
@endpush
