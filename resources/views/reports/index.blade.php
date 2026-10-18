@extends('layouts.app')

@section('title', __('app.reports.title'))

@section('content')
    <div class="hidden print:mb-6 print:block">
        <h1 class="text-2xl font-extrabold text-slate-900">{{ __('app.reports.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $period?->name ?? '-' }} · {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.reports.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.reports.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 no-print">
            <button type="button" class="btn-secondary" onclick="window.print()">
                <svg class="h-4 w-4"><use href="#i-print"/></svg>
                {{ __('app.reports.print_report') }}
            </button>
            <a href="{{ route('reports.calibration', ['period' => $period?->id]) }}" class="btn-secondary">
                <svg class="h-4 w-4"><use href="#i-chart"/></svg>
                {{ __('app.reports.calibration') }}
            </a>
            <a href="{{ route('reports.pdf', ['period' => $period?->id, 'department' => $departmentId]) }}" class="btn-secondary">
                <svg class="h-4 w-4"><use href="#i-download"/></svg>
                {{ __('app.actions.export_pdf') }}
            </a>
            <a href="{{ route('reports.excel', ['period' => $period?->id, 'department' => $departmentId]) }}" class="btn-primary">
                <svg class="h-4 w-4"><use href="#i-download"/></svg>
                {{ __('app.actions.export_excel') }}
            </a>
        </div>
    </div>

    <div class="card mb-5 no-print">
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="label" for="period">{{ __('app.common.period') }}</label>
                    <select id="period" name="period" class="input">
                        @foreach ($periods as $item)
                            <option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="department">{{ __('app.common.department') }}</label>
                    <select id="department" name="department" class="input">
                        <option value="">{{ __('app.reports.all_departments') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($departmentId === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-secondary">{{ __('app.actions.filter') }}</button>
                    <a href="{{ route('reports.index') }}" class="btn-ghost">{{ __('app.actions.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.total_employees') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $summary['totalEmployees'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $period?->name ?? '-' }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.reports.evaluated') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ $summary['evaluated'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ __('app.reports.not_evaluated') }}: {{ $summary['notEvaluated'] }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.common.average') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($summary['average'], 1) }}</p>
            <p class="mt-1 text-xs text-slate-400">
                {{ $summary['average'] > 0 ? \App\Models\Evaluation::gradeFor($summary['average']).' · '.__('app.grades.'.\App\Models\Evaluation::gradeFor($summary['average'])) : '-' }}
            </p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.reports.completion') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($summary['completion'], 1) }}%</p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-indigo-500" style="width: {{ min($summary['completion'], 100) }}%"></div>
            </div>
        </div>
    </div>

    <div class="card mt-5">
        <div class="card-header">
            <div>
                <h3 class="font-bold text-slate-900">{{ __('app.reports.chart') }}</h3>
                <p class="mt-0.5 text-xs text-slate-400">{{ __('app.reports.chart_hint') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 no-print">
                <select class="input !py-1.5 text-xs" data-chart-dataset>
                    <option value="departments">{{ __('app.reports.chart_departments') }}</option>
                    <option value="grades">{{ __('app.reports.chart_grades') }}</option>
                    <option value="criteria">{{ __('app.reports.chart_criteria') }}</option>
                </select>
                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
                    <button type="button" class="rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-bold text-white transition" data-chart-type="bar">{{ __('app.reports.bar') }}</button>
                    <button type="button" class="rounded-lg px-2.5 py-1 text-xs font-bold text-slate-600 transition" data-chart-type="pie">{{ __('app.reports.pie') }}</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="report-chart"
                 class="relative h-80"
                 data-departments="{{ json_encode($byDepartment) }}"
                 data-grades="{{ json_encode($distribution) }}"
                 data-criteria="{{ json_encode($byCriteria) }}"
                 data-departments-label="{{ __('app.reports.chart_departments') }}"
                 data-grades-label="{{ __('app.reports.chart_grades') }}"
                 data-criteria-label="{{ __('app.reports.chart_criteria') }}">
                <canvas></canvas>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.reports.ranking') }}</h3>
                <span class="text-xs text-slate-400">{{ $period?->name }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="table-head">
                        <tr>
                            <th class="w-14 text-center">#</th>
                            <th>{{ __('app.common.employee') }}</th>
                            <th>{{ __('app.common.department') }}</th>
                            <th class="text-center">{{ __('app.common.score') }}</th>
                            <th class="text-center">{{ __('app.common.grade') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ranking as $index => $evaluation)
                            <tr class="table-row">
                                <td class="text-center">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $index < 3 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }} mx-auto text-xs font-extrabold">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('evaluations.show', $evaluation) }}" class="font-semibold text-slate-800 hover:text-indigo-600">
                                        {{ $evaluation->employee?->name }}
                                    </a>
                                    <p class="text-xs text-slate-400">{{ $evaluation->employee?->position?->name ?? '-' }}</p>
                                </td>
                                <td class="text-slate-500">{{ $evaluation->employee?->department?->name ?? '-' }}</td>
                                <td class="text-center font-bold text-slate-900">{{ number_format((float) $evaluation->final_score, 1) }}</td>
                                <td class="text-center">
                                    <span class="{{ (float) $evaluation->final_score >= 80 ? 'badge-green' : ((float) $evaluation->final_score >= 60 ? 'badge-amber' : 'badge-red') }}">
                                        {{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">{{ __('app.reports.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.reports.score_distribution') }}</h3>
                </div>
                <div class="space-y-3 p-5">
                    @forelse ($distribution as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $row['grade'] }} · {{ $row['label'] }}</span>
                                <span class="font-bold text-slate-900">{{ $row['count'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ $summary['evaluated'] > 0 ? ($row['count'] / $summary['evaluated']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">{{ __('app.reports.empty') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.reports.by_criteria') }}</h3>
                </div>
                <div class="space-y-3 p-5">
                    @forelse ($byCriteria as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                                <span class="text-xs font-bold text-slate-900">{{ number_format($row['average'], 2) }}/5</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-violet-500" style="width: {{ ($row['average'] / 5) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">{{ __('app.reports.empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.reports.by_department') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="table-head">
                        <tr>
                            <th>{{ __('app.common.department') }}</th>
                            <th class="text-center">{{ __('app.reports.evaluated') }}</th>
                            <th class="text-center">{{ __('app.common.average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byDepartment as $row)
                            <tr class="table-row">
                                <td class="font-semibold text-slate-700">{{ $row['label'] }}</td>
                                <td class="text-center text-slate-500">{{ $row['count'] }}</td>
                                <td class="text-center font-bold text-slate-900">{{ number_format($row['average'], 1) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">{{ __('app.reports.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.reports.not_evaluated_list') }}</h3>
                <span class="badge-amber">{{ $notEvaluated->count() }}</span>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <table class="w-full">
                    <thead class="table-head">
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('app.common.department') }}</th>
                            <th class="text-right">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notEvaluated as $employee)
                            <tr class="table-row">
                                <td>
                                    <p class="font-semibold text-slate-700">{{ $employee->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $employee->nip }}</p>
                                </td>
                                <td class="text-slate-500">{{ $employee->department?->name ?? '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('evaluations.create', ['employee' => $employee->id, 'period' => $period?->id]) }}" class="btn-ghost">{{ __('app.actions.score') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">{{ __('app.reports.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
