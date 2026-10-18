@extends('layouts.app')

@section('title', __('app.feedback.title'))

@php
    $pending = $feedback->filter(fn ($item) => ! $item->isSubmitted());
    $completed = $feedback->filter(fn ($item) => $item->isSubmitted());
@endphp

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.feedback.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.feedback.subtitle') }}</p>
    </div>

    <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        {{ __('app.feedback.combined_hint') }} · {{ __('app.feedback.score_hint') }}
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('app.feedback.pending') }} ({{ $pending->count() }})</h3>

            @forelse ($pending as $item)
                <form method="POST" action="{{ route('feedback.submit', $item) }}" class="card">
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
@endsection
