@extends('layouts.app')

@section('title', __('app.evaluations.title'))

@php
    $user = auth()->user();
    $canCreate = $user->isAdmin() || $user->isManager();
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.evaluations.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.evaluations.subtitle') }}</p>
        </div>
        @if ($canCreate)
            <button type="button" class="btn-primary" data-modal-open="evaluation-create">
                <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                {{ __('app.evaluations.add') }}
            </button>
        @endif
    </div>

    <div class="card">
        @if (! $user->isEmployee())
            <div class="card-body">
                <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('app.common.search_placeholder') }}" class="input pl-9">
                        </div>
                    </div>
                    <select name="period" class="input">
                        <option value="">{{ __('app.common.all') }} · {{ __('app.common.period') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected(($filters['period'] ?? '') == $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                    <select name="department" class="input">
                        <option value="">{{ __('app.common.all') }} · {{ __('app.common.department') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(($filters['department'] ?? '') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="status" class="input">
                            <option value="">{{ __('app.common.all') }} · {{ __('app.common.status') }}</option>
                            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>{{ __('app.evaluations.statuses.draft') }}</option>
                            <option value="submitted" @selected(($filters['status'] ?? '') === 'submitted')>{{ __('app.evaluations.statuses.submitted') }}</option>
                        </select>
                        <button type="submit" class="btn-secondary shrink-0">{{ __('app.actions.filter') }}</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.employee') }}</th>
                        <th>{{ __('app.common.period') }}</th>
                        @if (! $user->isEmployee())
                            <th>{{ __('app.common.evaluator') }}</th>
                        @endif
                        <th class="text-center">{{ __('app.evaluations.final_score') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($evaluations as $evaluation)
                        <tr class="table-row">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">
                                        {{ $evaluation->employee?->initials() ?? '?' }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $evaluation->employee?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $evaluation->employee?->department?->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-600">{{ $evaluation->period?->name }}</td>
                            @if (! $user->isEmployee())
                                <td class="text-slate-600">{{ $evaluation->evaluator?->name ?? '-' }}</td>
                            @endif
                            <td class="text-center">
                                @if ($evaluation->isSubmitted())
                                    <span class="font-bold text-slate-900">{{ number_format((float) $evaluation->final_score, 1) }}</span>
                                    <span class="ml-1 text-xs text-slate-400">{{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="{{ $evaluation->isSubmitted() ? 'badge-green' : 'badge-amber' }}">
                                    {{ __('app.evaluations.statuses.'.$evaluation->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('evaluations.show', $evaluation) }}" class="btn-ghost">{{ __('app.actions.detail') }}</a>
                                    @if (! $evaluation->isSubmitted() && ($user->isAdmin() || $evaluation->evaluator_id === $user->id))
                                        <a href="{{ route('evaluations.edit', $evaluation) }}" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}">
                                            <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                        </a>
                                    @endif
                                    @if ($user->isAdmin() && ! $evaluation->isSubmitted())
                                        <form method="POST" action="{{ route('evaluations.destroy', $evaluation) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="{{ __('app.actions.delete') }}">
                                                <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $user->isEmployee() ? 5 : 6 }}" class="empty-state">{{ __('app.evaluations.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($evaluations->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $evaluations->links() }}</div>
        @endif
    </div>

    @if ($canCreate)
        <x-modal-form id="evaluation-create" :title="__('app.evaluations.create_title')" :subtitle="__('app.evaluations.create_hint')" :action="route('evaluations.store')" :submit-label="__('app.actions.create')" :open="$openModal === 'evaluation-create'">
            @include('evaluations._create_fields', [
                'employees' => $evaluableEmployees,
                'periods' => $activePeriods,
                'selectedEmployee' => $selectedEmployee,
                'selectedPeriod' => $selectedPeriod,
                'prefix' => 'evaluation-create',
            ])
        </x-modal-form>
    @endif
@endsection
