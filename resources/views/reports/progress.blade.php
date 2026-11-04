@extends('layouts.app')

@section('title', __('app.progress.title'))

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.progress.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.progress.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('reports.index', ['period' => $period?->id]) }}" class="btn-secondary">{{ __('app.nav.reports') }}</a>
            @if ($canRemind && $period)
                <form method="POST" action="{{ route('periods.remind', $period) }}">
                    @csrf
                    <button type="submit" class="btn-primary">
                        <svg class="h-4 w-4"><use href="#i-star"/></svg>
                        {{ __('app.reminders.send') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="label" for="period">{{ __('app.progress.period') }}</label>
                    <select id="period" name="period" class="input" data-searchable onchange="this.form.submit()">
                        @foreach ($periods as $item)
                            <option value="{{ $item->id }}" @selected($period?->id === $item->id)>
                                {{ $item->name }} · {{ __('app.periods.statuses.'.$item->status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="department">{{ __('app.common.department') }}</label>
                    <select id="department" name="department" class="input" data-searchable onchange="this.form.submit()">
                        <option value="">{{ __('app.reports.all_departments') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($departmentId === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-secondary">{{ __('app.actions.filter') }}</button>
                    <a href="{{ route('reports.progress') }}" class="btn-ghost">{{ __('app.actions.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    @if ($period)
        <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.progress.completion') }}</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($summary['completion'], 1) }}%</p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-indigo-500" style="width: {{ min($summary['completion'], 100) }}%"></div>
                </div>
            </div>
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.reports.evaluated') }}</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ $summary['submitted'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.progress.of_total', ['total' => $summary['total']]) }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.progress.in_progress') }}</p>
                <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ $summary['draft'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('app.progress.pending', ['count' => $summary['pending']]) }}</p>
            </div>
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">{{ __('app.common.average') }}</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($summary['average'], 1) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $period->name }}</p>
            </div>
        </div>

        <div class="mb-5 grid gap-5 lg:grid-cols-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.progress.schedule') }}</h3>
                    <span class="badge-indigo">{{ __('app.periods.statuses.'.$period->status) }}</span>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ __('app.periods.range') }}</span>
                        <span class="font-semibold text-slate-800">
                            {{ $period->start_date?->format('d M Y') }} — {{ $period->end_date?->format('d M Y') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ __('app.progress.days_left') }}</span>
                        <span class="font-semibold {{ $period->end_date?->isPast() ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ $period->end_date?->isPast() ? __('app.progress.closed') : max(0, (int) now()->diffInDays($period->end_date, false)).' '.__('app.progress.days') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ __('app.progress.managers_pending') }}</span>
                        <span class="font-semibold text-slate-800">{{ $managerGroups->sum('pending') }}</span>
                    </div>
                    @if (auth()->user()->isAdmin() && \App\Support\PageAccess::allows(auth()->user(), 'periods'))
                        <a href="{{ route('periods.index') }}" class="btn-secondary btn-sm w-full">{{ __('app.progress.manage_schedule') }}</a>
                    @endif
                </div>
            </div>

            <div class="card lg:col-span-2">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.progress.by_department') }}</h3>
                </div>
                <div class="space-y-3 p-5">
                    @forelse ($byDepartment as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $row['department'] }}</span>
                                <span class="text-xs font-bold text-slate-900">{{ $row['submitted'] }}/{{ $row['total'] }} · {{ number_format($row['completion'], 0) }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ min($row['completion'], 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">{{ __('app.reports.empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="empty-state">{{ __('app.periods.empty') }}</div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.progress.tracking') }}</h3>
                <span class="text-xs text-slate-400">{{ $period?->name }}</span>
            </div>
            <div class="max-h-[32rem] overflow-y-auto">
                <table class="table-stable w-full min-w-[52rem]">
                    <colgroup>
                        <col>
                        <col class="w-[11rem]">
                        <col class="w-[8rem]">
                        <col class="w-[7rem]">
                        <col class="w-[9rem]">
                    </colgroup>
                    <thead class="table-head sticky top-0 z-10">
                        <tr>
                            <th>{{ __('app.common.employee') }}</th>
                            <th>{{ __('app.common.manager') }}</th>
                            <th class="text-center">{{ __('app.common.status') }}</th>
                            <th class="text-center">{{ __('app.common.score') }}</th>
                            <th class="text-right">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="table-row">
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $row['employee']->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $row['employee']->department?->name ?? '-' }}</p>
                                </td>
                                <td class="text-slate-600">{{ $row['employee']->manager?->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if ($row['state'] === 'submitted')
                                        <span class="badge-green">{{ __('app.progress.submitted') }}</span>
                                    @elseif ($row['state'] === 'draft')
                                        <span class="badge-amber">{{ __('app.progress.draft') }}</span>
                                    @else
                                        <span class="badge-slate">{{ __('app.progress.not_started') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($row['evaluation']?->isSubmitted())
                                        <span class="font-bold text-slate-900">{{ number_format((float) $row['evaluation']->final_score, 1) }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($period)
                                        <a href="{{ route('evaluations.team', ['employee' => $row['employee']->id, 'period' => $period->id]) }}" class="btn-ghost">
                                            {{ $row['state'] === 'submitted' ? __('app.actions.view') : __('app.actions.score') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">{{ __('app.reports.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.progress.reminder_queue') }}</h3>
                <span class="badge-amber">{{ $managerGroups->count() }}</span>
            </div>
            <div class="max-h-[32rem] divide-y divide-slate-100 overflow-y-auto">
                @forelse ($managerGroups as $group)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $group['manager'] }}</p>
                            <span class="badge-amber">{{ $group['pending'] }}</span>
                        </div>
                        <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ implode(', ', $group['employees']) }}</p>
                    </div>
                @empty
                    <div class="empty-state">{{ __('app.reminders.none_pending') }}</div>
                @endforelse
            </div>
            @if ($canRemind && $period)
                <div class="border-t border-slate-100 px-5 py-3">
                    <p class="text-xs leading-relaxed text-slate-500">{{ __('app.progress.reminder_hint') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
