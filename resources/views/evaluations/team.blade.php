@extends('layouts.app')

@section('title', __('app.team.title'))

@php
    $scoreExisting = $drawerEvaluation?->scores?->keyBy('criteria_id') ?? collect();
    $purposeLabels = __('app.evaluations.purposes');
@endphp

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.team.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.team.subtitle') }}</p>
        </div>
        <a href="{{ route('evaluations.index') }}" class="btn-secondary">{{ __('app.team.all_evaluations') }}</a>
    </div>

    <div class="card mb-5">
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="label" for="team-period">{{ __('app.team.when') }}</label>
                    <select id="team-period" name="period" class="input" onchange="this.form.submit()">
                        @foreach ($periods as $item)
                            <option value="{{ $item->id }}" @selected($period?->id === $item->id)>
                                {{ $item->name }} · {{ __('app.periods.statuses.'.$item->status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="team-department">{{ __('app.team.where') }}</label>
                    <select id="team-department" name="department" class="input" onchange="this.form.submit()">
                        <option value="">{{ __('app.team.all_departments') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(($filters['department'] ?? '') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="team-search">{{ __('app.team.who') }}</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                        <input id="team-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                               placeholder="{{ __('app.team.search_placeholder') }}" class="input pl-9">
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-secondary">{{ __('app.actions.filter') }}</button>
                    <a href="{{ route('evaluations.team') }}" class="btn-ghost">{{ __('app.actions.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        <span class="font-semibold">{{ __('app.team.what') }}:</span>
        <span>
            @forelse ($criteria as $criterion)
                {{ $criterion->name }} ({{ rtrim(rtrim(number_format((float) $criterion->weight, 2), '0'), '.') }}%){{ ! $loop->last ? ' · ' : '' }}
            @empty
                {{ __('app.team.no_criteria') }}
            @endforelse
        </span>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[62rem]">
                <colgroup>
                    <col>
                    <col class="w-[11rem]">
                    <col class="w-[11rem]">
                    <col class="w-[8rem]">
                    <col class="w-[7rem]">
                    <col class="w-[10rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.employee') }}</th>
                        <th>{{ __('app.common.department') }}</th>
                        <th>{{ __('app.common.manager') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-center">{{ __('app.common.score') }}</th>
                        <th class="text-right">{{ __('app.team.evaluate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $evaluation = $evaluations->get($employee->id);
                            $state = $evaluation?->isSubmitted() ? 'submitted' : ($evaluation ? 'draft' : 'pending');
                            $href = route('evaluations.team', array_filter([
                                'employee' => $employee->id,
                                'period' => $period?->id,
                                'department' => $filters['department'] ?? null,
                                'search' => $filters['search'] ?? null,
                            ]));
                        @endphp
                        <tr class="table-row cursor-pointer" data-href="{{ $href }}" onclick="window.location=this.dataset.href">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">
                                        @if ($employee->photoUrl())
                                            <img src="{{ $employee->photoUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ $employee->initials() }}
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $employee->nip }} · {{ $employee->position?->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-600">{{ $employee->department?->name ?? '-' }}</td>
                            <td class="text-slate-600">{{ $employee->manager?->name ?? '-' }}</td>
                            <td class="text-center">
                                @if ($state === 'submitted')
                                    <span class="badge-green">{{ __('app.progress.submitted') }}</span>
                                @elseif ($state === 'draft')
                                    <span class="badge-amber">{{ __('app.progress.draft') }}</span>
                                @else
                                    <span class="badge-slate">{{ __('app.progress.not_started') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($evaluation?->isSubmitted())
                                    <span class="font-bold text-slate-900">{{ number_format((float) $evaluation->final_score, 1) }}</span>
                                    <span class="ml-1 text-xs text-slate-400">{{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ $href }}" class="btn-ghost" onclick="event.stopPropagation()">
                                    {{ $state === 'submitted' ? __('app.actions.view') : __('app.team.evaluate') }}
                                    <svg class="h-3.5 w-3.5"><use href="#i-chevron"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('app.team.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($drawerEmployee)
        <div id="evaluation-drawer" class="drawer" data-drawer data-autoopen data-open="false" aria-hidden="true">
            <div class="drawer-backdrop" data-drawer-close></div>

            <aside class="drawer-panel" role="dialog" aria-modal="true" aria-labelledby="evaluation-drawer-title">
                <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-indigo-50 text-sm font-bold text-indigo-600">
                            @if ($drawerEmployee->photoUrl())
                                <img src="{{ $drawerEmployee->photoUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ $drawerEmployee->initials() }}
                            @endif
                        </span>
                        <div class="min-w-0">
                            <h2 id="evaluation-drawer-title" class="truncate text-lg font-extrabold text-slate-900">{{ $drawerEmployee->name }}</h2>
                            <p class="truncate text-xs text-slate-500">
                                {{ $drawerEmployee->position?->name ?? '-' }} · {{ $drawerEmployee->department?->name ?? '-' }} · {{ $period?->name }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-drawer-close aria-label="{{ __('app.actions.close') }}">
                        <svg class="h-5 w-5"><use href="#i-x"/></svg>
                    </button>
                </header>

                @if ($errors->any())
                    <div class="mx-5 mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($canScore)
                    <form method="POST" action="{{ route('evaluations.update', $drawerEvaluation) }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="return_to" value="team">

                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">
                            <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-500">
                                {{ __('app.evaluations.scale_hint') }} · {{ __('app.evaluations.weighted_note') }}
                            </div>

                            <div>
                                <label class="label" for="drawer-purpose">{{ __('app.evaluations.purpose') }}</label>
                                <select id="drawer-purpose" name="purpose" class="input">
                                    <option value="">{{ __('app.evaluations.purpose_none') }}</option>
                                    @foreach ($purposes as $purpose)
                                        <option value="{{ $purpose }}" @selected(old('purpose', $drawerEvaluation->purpose) === $purpose)>
                                            {{ $purposeLabels[$purpose] ?? $purpose }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @forelse ($criteria as $criterion)
                                @php
                                    $current = old('scores.'.$criterion->id, $scoreExisting->get($criterion->id)?->score);
                                    $comment = old('comments.'.$criterion->id, $scoreExisting->get($criterion->id)?->comment ?? '');
                                @endphp
                                <div class="rounded-xl border border-slate-200 p-4" data-criterion data-weight="{{ (float) $criterion->weight }}">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-900">{{ $criterion->name }}</h3>
                                            @if ($criterion->description)
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $criterion->description }}</p>
                                            @endif
                                        </div>
                                        <span class="badge-indigo">{{ rtrim(rtrim(number_format((float) $criterion->weight, 2), '0'), '.') }}%</span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @for ($score = 1; $score <= 5; $score++)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="scores[{{ $criterion->id }}]" value="{{ $score }}" class="peer sr-only"
                                                       @checked((string) $current === (string) $score) required>
                                                <span class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-bold text-slate-500 transition hover:border-indigo-300 hover:text-indigo-600 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white">
                                                    {{ $score }}
                                                </span>
                                            </label>
                                        @endfor
                                    </div>
                                    @error('scores.'.$criterion->id) <p class="form-error">{{ $message }}</p> @enderror

                                    <div class="mt-3">
                                        <input name="comments[{{ $criterion->id }}]" value="{{ $comment }}" class="input !py-2 text-xs"
                                               placeholder="{{ __('app.common.comment') }} ({{ __('app.common.optional') }})">
                                        @error('comments.'.$criterion->id) <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">{{ __('app.criteria.empty') }}</div>
                            @endforelse

                            <div>
                                <label class="label" for="drawer-notes">{{ __('app.common.notes') }}</label>
                                <textarea id="drawer-notes" name="notes" rows="3" class="input">{{ old('notes', $drawerEvaluation->notes) }}</textarea>
                                @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <footer class="shrink-0 border-t border-slate-100 bg-white px-5 py-4">
                            <div class="mb-3 flex items-center justify-between gap-3 text-sm">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('app.evaluations.final_score') }}</p>
                                    <p class="text-2xl font-extrabold text-slate-900">
                                        <span id="drawerScore">0.0</span>
                                        <span id="drawerGrade" class="text-sm text-slate-400">E</span>
                                    </p>
                                </div>
                                <div class="text-right text-xs text-slate-500">
                                    <span id="drawerCount">0</span>/{{ $criteria->count() }} {{ __('app.evaluations.scores') }}
                                    <div class="mt-1 h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                                        <div id="drawerBar" class="h-full rounded-full bg-indigo-500 transition-all" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" class="btn-secondary" data-drawer-close>{{ __('app.actions.cancel') }}</button>
                                <button type="submit" class="btn-secondary">{{ __('app.actions.save') }}</button>
                                <button type="submit" name="submit" value="1" class="btn-primary">{{ __('app.actions.save_submit') }}</button>
                            </div>
                        </footer>
                    </form>
                @elseif ($drawerEvaluation)
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">
                        <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            {{ $drawerEvaluation->isSubmitted() ? __('app.evaluations.submitted_locked') : __('app.evaluations.period_not_active') }}
                        </div>

                        @if ($drawerEvaluation->purpose)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">{{ __('app.evaluations.purpose') }}</span>
                                <span class="font-semibold text-slate-800">{{ $purposeLabels[$drawerEvaluation->purpose] ?? $drawerEvaluation->purpose }}</span>
                            </div>
                        @endif

                        <div class="divide-y divide-slate-100 rounded-xl border border-slate-100">
                            @foreach ($drawerEvaluation->scores as $score)
                                <div class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                    <span class="text-slate-600">{{ $score->criterion?->name }}</span>
                                    <span class="badge-indigo">{{ $score->score }} / 5</span>
                                </div>
                            @endforeach
                        </div>

                        <a href="{{ route('evaluations.show', $drawerEvaluation) }}" class="btn-primary w-full">{{ __('app.actions.detail') }}</a>
                    </div>
                @elseif ($canStart)
                    <form method="POST" action="{{ route('evaluations.store') }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $drawerEmployee->id }}">
                        <input type="hidden" name="period_id" value="{{ $period?->id }}">

                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">
                            <div class="rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                                {{ __('app.team.start_hint', ['period' => $period?->name, 'criteria' => $criteria->count()]) }}
                            </div>

                            <div>
                                <label class="label" for="drawer-purpose">{{ __('app.evaluations.purpose') }}</label>
                                <select id="drawer-purpose" name="purpose" class="input">
                                    @foreach ($purposes as $purpose)
                                        <option value="{{ $purpose }}" @selected($purpose === 'annual')>{{ $purposeLabels[$purpose] ?? $purpose }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="label" for="drawer-start-notes">{{ __('app.common.notes') }}</label>
                                <textarea id="drawer-start-notes" name="notes" rows="3" class="input" placeholder="{{ __('app.common.optional') }}">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <footer class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-white px-5 py-4">
                            <button type="button" class="btn-secondary" data-drawer-close>{{ __('app.actions.cancel') }}</button>
                            <button type="submit" class="btn-primary">{{ __('app.team.start') }}</button>
                        </footer>
                    </form>
                @else
                    <div class="flex-1 px-5 py-5">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ __('app.evaluations.period_not_active') }}
                        </div>
                        @unless ($period?->isActive())
                            <a href="{{ route('evaluations.index', ['open' => 'evaluation-create']) }}" class="btn-secondary mt-3">
                                <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                                {{ __('app.evaluations.create_period') }}
                            </a>
                        @endunless
                    </div>
                @endif
            </aside>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const group = document.querySelectorAll('#evaluation-drawer [data-criterion]');
            const scoreEl = document.getElementById('drawerScore');
            const gradeEl = document.getElementById('drawerGrade');
            const countEl = document.getElementById('drawerCount');
            const barEl = document.getElementById('drawerBar');

            if (!scoreEl || !group.length) {
                return;
            }

            const gradeFor = (score) => score >= 90 ? 'A' : score >= 80 ? 'B' : score >= 70 ? 'C' : score >= 60 ? 'D' : 'E';

            const recalc = () => {
                let weighted = 0, total = 0, filled = 0;

                group.forEach((item) => {
                    const weight = parseFloat(item.dataset.weight) || 0;
                    const checked = item.querySelector('input[type="radio"]:checked');
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
                barEl.style.width = Math.round((filled / Math.max(group.length, 1)) * 100) + '%';
            };

            group.forEach((item) => item.querySelectorAll('input[type="radio"]').forEach((input) => {
                input.addEventListener('change', recalc);
            }));

            recalc();
        });
    </script>
@endpush
