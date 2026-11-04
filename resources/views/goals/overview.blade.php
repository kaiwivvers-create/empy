@extends('layouts.app')

@section('title', __('app.kpi.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.kpi.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.kpi.subtitle') }}</p>
        </div>
        <span class="badge-indigo">{{ $employees->count() }} {{ __('app.common.employee') }}</span>
    </div>

    @unless (auth()->user()->isEmployee())
    <div class="card mb-5">
        <div class="card-header">
            <h3 class="font-bold text-slate-900">{{ __('app.kpi.choose') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[46rem]">
                <colgroup>
                    <col>
                    <col class="w-[12rem]">
                    <col class="w-[12rem]">
                    <col class="w-[8rem]">
                    <col class="w-[9rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.employee') }}</th>
                        <th>{{ __('app.common.department') }}</th>
                        <th>{{ __('app.common.position') }}</th>
                        <th class="text-center">{{ __('app.kpi.targets') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $row)
                        <tr class="table-row {{ $employee?->id === $row->id ? 'bg-indigo-50/60' : '' }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">
                                        @if ($row->photoUrl())
                                            <img src="{{ $row->photoUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ $row->initials() }}
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800">{{ $row->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $row->nip }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-500">{{ $row->department?->name ?? '-' }}</td>
                            <td class="text-slate-500">{{ $row->position?->name ?? '-' }}</td>
                            <td class="text-center font-bold text-slate-800">{{ $row->goals_count }}</td>
                            <td class="text-right">
                                <a href="{{ route('kpi.index', ['employee' => $row->id]) }}" class="btn-ghost">
                                    {{ __('app.kpi.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">{{ __('app.employees.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endunless

    @if ($employee)
        <div class="card">
            <div class="card-header">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-indigo-50 text-sm font-bold text-indigo-600">
                        @if ($employee->photoUrl())
                            <img src="{{ $employee->photoUrl() }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ $employee->initials() }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate font-bold text-slate-900">{{ $employee->name }}</h3>
                        <p class="truncate text-xs text-slate-400">
                            {{ $employee->position?->name ?? '-' }} · {{ $employee->department?->name ?? '-' }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="employee" value="{{ $employee->id }}">
                        <select name="period" class="input !py-1.5 text-xs" data-searchable onchange="this.form.submit()">
                            <option value="">{{ __('app.common.all') }} · {{ __('app.goals.period') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($canManage)
                        <button type="button" class="btn-primary btn-sm" data-modal-open="goal-create">
                            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                            {{ __('app.goals.add') }}
                        </button>
                    @endif
                </div>
            </div>

            @include('goals._table', ['employee' => $employee, 'goals' => $goals, 'canManage' => $canManage])
        </div>

        @if ($canManage)
            @include('goals._create-modal', [
                'employee' => $employee,
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
            ])
        @endif
    @else
        <div class="card">
            <div class="empty-state">
                {{ auth()->user()->isEmployee() ? __('app.employees.empty') : __('app.kpi.select_hint') }}
            </div>
        </div>
    @endif
@endsection
