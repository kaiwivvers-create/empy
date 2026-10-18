@extends('layouts.app')

@section('title', __('app.evaluations.edit_title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.evaluations.edit_title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $evaluation->employee?->name }} · {{ $evaluation->employee?->department?->name ?? '-' }} · {{ $evaluation->period?->name }}
            </p>
        </div>
        <a href="{{ route('evaluations.show', $evaluation) }}" class="btn-secondary">{{ __('app.actions.back') }}</a>
    </div>

    <form method="POST" action="{{ route('evaluations.update', $evaluation) }}">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                @foreach ($criteria as $criterion)
                    @php
                        $current = old('scores.'.$criterion->id, $existing[$criterion->id]->score ?? null);
                        $comment = old('comments.'.$criterion->id, $existing[$criterion->id]->comment ?? '');
                    @endphp

                    <div class="card" data-criterion data-weight="{{ (float) $criterion->weight }}">
                        <div class="card-body">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-slate-900">{{ $criterion->name }}</h3>
                                    @if ($criterion->description)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $criterion->description }}</p>
                                    @endif
                                </div>
                                <span class="badge-indigo">{{ __('app.criteria.weight') }} {{ rtrim(rtrim(number_format((float) $criterion->weight, 2), '0'), '.') }}%</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @for ($score = 1; $score <= 5; $score++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="scores[{{ $criterion->id }}]" value="{{ $score }}" class="peer sr-only"
                                               @checked((string) $current === (string) $score) required>
                                        <span class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-500 transition hover:border-indigo-300 hover:text-indigo-600 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-indigo-200">
                                            {{ $score }}
                                        </span>
                                    </label>
                                @endfor
                                <span class="self-center pl-1 text-xs text-slate-400">{{ __('app.evaluations.scale_hint') }}</span>
                            </div>
                            @error('scores.'.$criterion->id) <p class="form-error">{{ $message }}</p> @enderror

                            <div class="mt-4">
                                <label class="label" for="comment-{{ $criterion->id }}">
                                    {{ __('app.common.comment') }} <span class="font-normal text-slate-400">({{ __('app.common.optional') }})</span>
                                </label>
                                <input id="comment-{{ $criterion->id }}" name="comments[{{ $criterion->id }}]" value="{{ $comment }}" class="input" placeholder="{{ __('app.common.comment') }}...">
                                @error('comments.'.$criterion->id) <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="card">
                    <div class="card-body">
                        <label class="label" for="notes">{{ __('app.common.notes') }}</label>
                        <textarea id="notes" name="notes" rows="3" class="input" placeholder="{{ __('app.common.notes') }}...">{{ old('notes', $evaluation->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="card sticky top-24 overflow-hidden">
                    <div class="bg-slate-900 p-5 text-white">
                        <p class="text-sm text-slate-400">{{ __('app.evaluations.final_score') }}</p>
                        <p class="mt-1 text-5xl font-extrabold" id="liveScore">0.0</p>
                        <p class="mt-1 text-sm text-slate-300">
                            <span id="liveGrade" class="font-bold">E</span>
                            <span class="ml-1 text-slate-400">{{ $criteria->count() }} {{ __('app.evaluations.scores') }}</span>
                        </p>
                    </div>
                    <div class="space-y-3 p-5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">{{ __('app.evaluations.scores') }}</span>
                            <span class="font-bold text-slate-800"><span id="liveCount">0</span>/{{ $criteria->count() }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div id="liveBar" class="h-full rounded-full bg-indigo-500 transition-all" style="width: 0%"></div>
                        </div>
                        <p class="rounded-xl bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-500">
                            {{ __('app.evaluations.weighted_note') }}
                        </p>
                        <button type="submit" class="btn-secondary w-full">{{ __('app.actions.save') }}</button>
                        <button type="submit" name="submit" value="1" class="btn-primary w-full">{{ __('app.actions.save_submit') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const groups = Array.from(document.querySelectorAll('[data-criterion]'));
                const scoreEl = document.getElementById('liveScore');
                const gradeEl = document.getElementById('liveGrade');
                const countEl = document.getElementById('liveCount');
                const barEl = document.getElementById('liveBar');

                const gradeFor = (score) => score >= 90 ? 'A' : score >= 80 ? 'B' : score >= 70 ? 'C' : score >= 60 ? 'D' : 'E';

                const recalc = () => {
                    let weighted = 0, total = 0, filled = 0;

                    groups.forEach(group => {
                        const weight = parseFloat(group.dataset.weight) || 0;
                        const checked = group.querySelector('input[type="radio"]:checked');
                        total += weight;
                        if (checked) {
                            filled++;
                            weighted += (parseInt(checked.value, 10) / 5) * weight;
                        }
                    });

                    const score = total > 0 ? (weighted / total) * 100 : 0;
                    scoreEl.textContent = score.toFixed(1);
                    gradeEl.textContent = gradeFor(score);
                    countEl.textContent = filled;
                    barEl.style.width = Math.round((filled / Math.max(groups.length, 1)) * 100) + '%';
                };

                groups.forEach(group => group.querySelectorAll('input[type="radio"]').forEach(input => {
                    input.addEventListener('change', recalc);
                }));

                recalc();
            });
        </script>
    @endpush
@endsection
