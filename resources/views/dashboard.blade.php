@extends('layouts.app')

@section('title', __('app.dashboard.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $greeting }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.dashboard.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($activePeriod)
                <span class="badge-indigo">
                    <svg class="h-3.5 w-3.5"><use href="#i-calendar"/></svg>
                    {{ $activePeriod->name }}
                </span>
            @else
                <span class="badge-amber">{{ __('app.dashboard.no_period') }}</span>
            @endif

            @if ($periodFilterEnabled)
                <form method="GET" class="flex items-center gap-1">
                    <select name="period" class="input !py-1.5 text-xs" onchange="this.form.submit()" title="{{ __('app.dashboard.filter_period') }}">
                        @foreach ($periods as $periodOption)
                            <option value="{{ $periodOption->id }}" @selected($activePeriod?->id === $periodOption->id)>{{ $periodOption->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    @if ($pendingReviews > 0)
        <a href="{{ route('feedback.index') }}" class="mb-5 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 transition hover:bg-amber-100">
            <svg class="h-5 w-5 shrink-0"><use href="#i-star"/></svg>
            <span class="flex-1">{{ __('app.dashboard.pending_reviews') }}: {{ $pendingReviews }}</span>
            <span class="text-xs font-bold">{{ __('app.dashboard.review_feedback') }} →</span>
        </a>
    @endif

    @if ($role === 'admin')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.total_employees') }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-5 w-5"><use href="#i-users"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ number_format($totalEmployees) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard.active_employees') }}</p>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.evaluations_done') }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5"><use href="#i-check"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ number_format($evaluated) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard.evaluated_of', ['done' => $evaluated, 'total' => $totalEmployees]) }}</p>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.pending') }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5"><use href="#i-clipboard"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ number_format($pending) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard.need_action') }}</p>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.average_score') }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <svg class="h-5 w-5"><use href="#i-chart"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ number_format($averageScore, 1) }}<span class="text-base font-semibold text-slate-400">/100</span></p>
                <p class="mt-1 text-xs text-slate-400">{{ \App\Models\Evaluation::gradeFor($averageScore) }} · {{ __('app.grades.'.\App\Models\Evaluation::gradeFor($averageScore)) }}</p>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-5">
            <div class="card lg:col-span-3">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.dashboard.by_department') }}</h3>
                    <span class="text-xs text-slate-400">{{ $activePeriod?->name ?? '-' }}</span>
                </div>
                <div class="card-body">
                    @if ($departmentScores->isEmpty())
                        <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                    @else
                        <div class="h-64"><canvas id="deptChart"></canvas></div>
                    @endif
                </div>
            </div>

            <div class="card lg:col-span-2">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.dashboard.trend') }}</h3>
                </div>
                <div class="card-body">
                    @if ($trend->filter(fn ($row) => $row['value'] > 0)->isEmpty())
                        <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                    @else
                        <div class="h-64"><canvas id="trendChart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-5">
            <div class="card lg:col-span-2">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.dashboard.top_performers') }}</h3>
                    <svg class="h-5 w-5 text-amber-400"><use href="#i-star"/></svg>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($topPerformers as $index => $evaluation)
                        <a href="{{ route('evaluations.show', $evaluation) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-slate-50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $index === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }} text-xs font-extrabold">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $evaluation->employee->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $evaluation->employee->department?->name ?? '-' }}</p>
                            </div>
                            <span class="text-sm font-extrabold text-indigo-600">{{ number_format((float) $evaluation->final_score, 1) }}</span>
                        </a>
                    @empty
                        <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="card lg:col-span-3">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.dashboard.recent') }}</h3>
                    <a href="{{ route('reports.index') }}" class="btn-ghost">{{ __('app.dashboard.view_report') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="table-head">
                            <tr>
                                <th>{{ __('app.common.employee') }}</th>
                                <th>{{ __('app.common.period') }}</th>
                                <th class="text-center">{{ __('app.common.score') }}</th>
                                <th class="text-right">{{ __('app.common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $evaluation)
                                <tr class="table-row">
                                    <td>
                                        <a href="{{ route('evaluations.show', $evaluation) }}" class="font-semibold text-slate-800 hover:text-indigo-600">
                                            {{ $evaluation->employee->name }}
                                        </a>
                                        <p class="text-xs text-slate-400">{{ $evaluation->employee->department?->name ?? '-' }}</p>
                                    </td>
                                    <td class="text-slate-500">{{ $evaluation->period?->name }}</td>
                                    <td class="text-center font-bold text-slate-800">{{ number_format((float) $evaluation->final_score, 1) }}</td>
                                    <td class="text-right">
                                        <span class="{{ (float) $evaluation->final_score >= 80 ? 'badge-green' : ((float) $evaluation->final_score >= 60 ? 'badge-amber' : 'badge-red') }}">
                                            {{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state">{{ __('app.dashboard.no_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const dept = {!! $departmentScores->toJson() !!};
                    const trend = {!! $trend->toJson() !!};

                    const deptEl = document.getElementById('deptChart');
                    if (deptEl && dept.length) {
                        new Chart(deptEl, {
                            type: 'bar',
                            data: {
                                labels: dept.map(row => row.label),
                                datasets: [{
                                    data: dept.map(row => row.value),
                                    backgroundColor: '#6366f1',
                                    hoverBackgroundColor: '#4f46e5',
                                    borderRadius: 8,
                                    maxBarThickness: 42,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
                                },
                            },
                        });
                    }

                    const trendEl = document.getElementById('trendChart');
                    if (trendEl && trend.length) {
                        new Chart(trendEl, {
                            type: 'line',
                            data: {
                                labels: trend.map(row => row.label),
                                datasets: [{
                                    data: trend.map(row => row.value),
                                    borderColor: '#8b5cf6',
                                    backgroundColor: 'rgba(139, 92, 246, 0.12)',
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#8b5cf6',
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    borderWidth: 3,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
                                },
                            },
                        });
                    }
                });
            </script>
        @endpush
    @endif

    @if ($role === 'manager')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.team_members') }}</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $teamMembers }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard.my_team') }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.completed') }}</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $evaluated }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard.evaluated_of', ['done' => $evaluated, 'total' => $teamMembers]) }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.pending') }}</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $pending }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.dashboard.need_action') }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.average_score') }}</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ number_format($averageScore, 1) }}<span class="text-base font-semibold text-slate-400">/100</span></p>
                <p class="mt-1 text-xs text-slate-400">{{ $activePeriod?->name ?? '-' }}</p>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-5">
            <div class="card lg:col-span-3">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.dashboard.team_members') }}</h3>
                    <a href="{{ route('evaluations.create') }}" class="btn-primary btn-sm">
                        <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                        {{ __('app.evaluations.add') }}
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="table-head">
                            <tr>
                                <th>{{ __('app.common.name') }}</th>
                                <th>{{ __('app.common.department') }}</th>
                                <th class="text-center">{{ __('app.common.status') }}</th>
                                <th class="text-right">{{ __('app.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myTeam as $row)
                                <tr class="table-row">
                                    <td>
                                        <p class="font-semibold text-slate-800">{{ $row['employee']->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $row['employee']->nip }}</p>
                                    </td>
                                    <td class="text-slate-500">{{ $row['employee']->department?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if ($row['evaluation']?->isSubmitted())
                                            <span class="badge-green">{{ number_format((float) $row['evaluation']->final_score, 1) }}</span>
                                        @elseif ($row['evaluation'])
                                            <span class="badge-amber">{{ __('app.evaluations.statuses.draft') }}</span>
                                        @else
                                            <span class="badge-slate">{{ __('app.common.not_yet') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if ($row['evaluation'])
                                            <a href="{{ route('evaluations.show', $row['evaluation']) }}" class="btn-ghost">{{ __('app.actions.detail') }}</a>
                                        @elseif ($activePeriod)
                                            <a href="{{ route('evaluations.create', ['employee' => $row['employee']->id, 'period' => $activePeriod->id]) }}" class="btn-ghost">{{ __('app.actions.score') }}</a>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state">{{ __('app.evaluations.no_team') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-5 lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-bold text-slate-900">{{ __('app.dashboard.my_team') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($teamScores->isEmpty())
                            <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                        @else
                            <div class="h-56"><canvas id="teamChart"></canvas></div>
                        @endif
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-bold text-slate-900">{{ __('app.dashboard.trend') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($teamTrend->isEmpty())
                            <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                        @else
                            <div class="h-40"><canvas id="teamTrendChart"></canvas></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const scores = {!! $teamScores->toJson() !!};
                    const trend = {!! $teamTrend->toJson() !!};

                    const teamEl = document.getElementById('teamChart');
                    if (teamEl && scores.length) {
                        new Chart(teamEl, {
                            type: 'bar',
                            data: {
                                labels: scores.map(row => row.label),
                                datasets: [{
                                    data: scores.map(row => row.value),
                                    backgroundColor: '#6366f1',
                                    borderRadius: 8,
                                    maxBarThickness: 28,
                                }],
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                                    y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } } },
                                },
                            },
                        });
                    }

                    const trendEl = document.getElementById('teamTrendChart');
                    if (trendEl && trend.length) {
                        new Chart(trendEl, {
                            type: 'line',
                            data: {
                                labels: trend.map(row => row.label),
                                datasets: [{
                                    data: trend.map(row => row.value),
                                    borderColor: '#8b5cf6',
                                    backgroundColor: 'rgba(139, 92, 246, 0.12)',
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 3,
                                    borderWidth: 2.5,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } },
                                },
                            },
                        });
                    }
                });
            </script>
        @endpush
    @endif

    @if ($role === 'employee')
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="card overflow-hidden bg-slate-900 text-white lg:col-span-1">
                <div class="p-6">
                    <p class="text-sm font-medium text-slate-400">{{ __('app.dashboard.latest_score') }}</p>
                    @if ($latestEvaluation)
                        <p class="mt-3 text-6xl font-extrabold">{{ number_format((float) $latestEvaluation->final_score, 1) }}</p>
                        <div class="mt-3 flex items-center gap-2">
                            <span class="badge bg-white/10 text-white ring-1 ring-white/20">
                                {{ \App\Models\Evaluation::gradeFor((float) $latestEvaluation->final_score) }}
                            </span>
                            <span class="text-sm text-slate-300">{{ __('app.grades.'.\App\Models\Evaluation::gradeFor((float) $latestEvaluation->final_score)) }}</span>
                        </div>
                        <p class="mt-4 text-xs text-slate-400">{{ $latestEvaluation->period?->name }}</p>
                        <p class="text-xs text-slate-500">{{ $latestEvaluation->submitted_at?->translatedFormat('d F Y') }}</p>
                    @else
                        <p class="mt-3 text-4xl font-extrabold text-slate-600">—</p>
                        <p class="mt-3 text-sm text-slate-400">{{ __('app.dashboard.no_data') }}</p>
                    @endif
                </div>
                <div class="border-t border-white/10 bg-white/5 px-6 py-4">
                    <a href="{{ route('evaluations.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-300 hover:text-white">
                        {{ __('app.evaluations.title') }}
                        <svg class="h-4 w-4"><use href="#i-arrow"/></svg>
                    </a>
                </div>
            </div>

            <div class="card lg:col-span-2">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.employees.score_history') }}</h3>
                </div>
                <div class="card-body">
                    @if ($myTrend->isEmpty())
                        <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                    @else
                        <div class="h-64"><canvas id="myTrendChart"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.evaluations.scores') }}</h3>
                    <span class="text-xs text-slate-400">{{ $latestEvaluation?->period?->name }}</span>
                </div>
                <div class="card-body space-y-4">
                    @forelse ($criteriaScores as $score)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $score['label'] }}</span>
                                <span class="font-bold text-slate-900">{{ $score['value'] }}/5</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ ($score['value'] / 5) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.employees.history') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="table-head">
                            <tr>
                                <th>{{ __('app.common.period') }}</th>
                                <th class="text-center">{{ __('app.common.score') }}</th>
                                <th class="text-center">{{ __('app.common.grade') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myEvaluations as $evaluation)
                                <tr class="table-row">
                                    <td class="font-medium text-slate-700">{{ $evaluation->period?->name }}</td>
                                    <td class="text-center font-bold">{{ number_format((float) $evaluation->final_score, 1) }}</td>
                                    <td class="text-center">
                                        <span class="badge-indigo">{{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state">{{ __('app.dashboard.no_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const trend = {!! $myTrend->toJson() !!};
                    const el = document.getElementById('myTrendChart');

                    if (el && trend.length) {
                        new Chart(el, {
                            type: 'line',
                            data: {
                                labels: trend.map(row => row.label),
                                datasets: [{
                                    data: trend.map(row => row.value),
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.12)',
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#6366f1',
                                    pointRadius: 4,
                                    borderWidth: 3,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
                                },
                            },
                        });
                    }
                });
            </script>
        @endpush
    @endif

    @if ($role === 'employee')
        <div class="card mt-5">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.plans.title') }}</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($myPlans as $plan)
                    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $plan->title }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $plan->period?->name }} · {{ __('app.plans.due_date') }}: {{ $plan->due_date?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                        <span class="badge-amber">{{ __('app.plans.statuses.'.$plan->status) }}</span>
                    </div>
                @empty
                    <div class="empty-state">{{ __('app.plans.empty') }}</div>
                @endforelse
            </div>
        </div>
    @elseif (($openPlans ?? collect())->isNotEmpty())
        <div class="card mt-5">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.dashboard.open_plans') }}</h3>
                <span class="badge-amber">{{ $openPlans->count() }}</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($openPlans as $plan)
                    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $plan->employee?->name }} — {{ $plan->title }}</p>
                            <p class="text-xs text-slate-400">
                                {{ __('app.plans.due_date') }}: {{ $plan->due_date?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                        <a href="{{ route('employees.show', $plan->employee) }}" class="btn-ghost">{{ __('app.actions.detail') }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
