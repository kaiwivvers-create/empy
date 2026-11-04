@extends('layouts.app')

@section('title', __('app.goals.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.goals.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $employee->name }} · {{ $employee->position?->name ?? '-' }} · {{ $employee->department?->name ?? '-' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ auth()->user()->isEmployee() ? route('kpi.index') : route('employees.show', $employee) }}" class="btn-secondary">{{ __('app.actions.back') }}</a>
            @if ($canManage)
                <a href="{{ route('evaluations.create', ['employee' => $employee->id]) }}" class="btn-secondary">{{ __('app.evaluations.add') }}</a>
                <button type="button" class="btn-primary" data-modal-open="goal-create">
                    <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                    {{ __('app.goals.add') }}
                </button>
            @endif
        </div>
    </div>

    <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        {{ __('app.goals.hint') }}
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-slate-900">{{ __('app.goals.title') }}</h3>
            <form method="GET" class="flex items-center gap-2">
                <select name="period" class="input !py-1.5 text-xs" data-searchable onchange="this.form.submit()">
                    <option value="">{{ __('app.common.all') }} · {{ __('app.goals.period') }}</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected($selectedPeriod === $period->id)>{{ $period->name }}</option>
                    @endforeach
                </select>
            </form>
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
@endsection
