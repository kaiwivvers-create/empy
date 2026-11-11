@extends('layouts.app')

@section('title', __('app.employees.detail_title'))

@php
    $viewer = auth()->user();
    $canViewKpi = \App\Support\PageAccess::allows($viewer, 'kpi');
    $canManageKpi = $canViewKpi && ($viewer->isAdmin() || $employee->isManagedBy($viewer));
    $canViewEvaluations = \App\Support\PageAccess::allows($viewer, 'evaluations');
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        {{-- Employees reach their own profile from the sidebar, so there is
             nowhere useful for Back to go. --}}
        @unless ($viewer->isEmployee())
            <a href="{{ route('employees.index') }}" class="btn-secondary">
                <svg class="h-4 w-4 rotate-180"><use href="#i-arrow"/></svg>
                {{ __('app.actions.back') }}
            </a>
        @endunless
        <div class="flex flex-wrap items-center gap-2 {{ $viewer->isEmployee() ? 'ml-auto' : '' }}">
            @if ($canManageKpi)
                <a href="{{ route('kpi.index', ['employee' => $employee->id]) }}" class="btn-secondary">
                    <svg class="h-4 w-4"><use href="#i-chart"/></svg>
                    {{ __('app.employees.view_goals') }}
                </a>
            @endif
            @if ($latest && $canViewEvaluations)
                <a href="{{ route('evaluations.pdf', $latest) }}" class="btn-secondary">
                    <svg class="h-4 w-4"><use href="#i-download"/></svg>
                    {{ __('app.evaluations.pdf') }}
                </a>
            @endif
            @if ($viewer->isAdmin() && ! $employee->trashed())
                <button type="button" class="btn-primary" data-modal-open="employee-show-edit">
                    <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                    {{ __('app.actions.edit') }}
                </button>
            @endif
        </div>
    </div>

    @if ($employee->trashed())
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">{{ __('app.employees.archived_notice') }}</p>
            @if ($viewer->isAdmin())
                <form method="POST" action="{{ route('employees.restore', $employee->id) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-secondary">{{ __('app.common.restore') }}</button>
                </form>
            @endif
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-xl font-extrabold text-white shadow-lg shadow-indigo-200">
                            @if ($employee->photoUrl())
                                <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}" class="h-full w-full object-cover">
                            @else
                                {{ $employee->initials() }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-extrabold text-slate-900">{{ $employee->name }}</h2>
                            <p class="text-sm text-slate-500">
                                {{ $employee->position?->name ?? '-' }} · {{ $employee->department?->name ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('app.employees.nip') }}: {{ $employee->nip }}</p>
                        </div>
                        <span class="{{ $employee->status === 'active' ? 'badge-green' : 'badge-slate' }}">
                            {{ $employee->status === 'active' ? __('app.common.active') : __('app.common.inactive') }}
                        </span>
                    </div>

                    <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.common.email') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $employee->email ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.common.phone') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $employee->phone ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.common.manager') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $employee->manager?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.employees.hire_date') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $employee->hire_date?->translatedFormat('d F Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.employees.account') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $employee->user?->email ?? __('app.employees.no_account') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.common.status') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">{{ $employee->status === 'active' ? __('app.common.active') : __('app.common.inactive') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.employees.history') }}</h3>
                    <span class="text-xs text-slate-400">{{ $evaluations->count() }} {{ __('app.nav.evaluations') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-stable w-full min-w-[38rem]">
                        <colgroup>
                            <col>
                            <col class="w-[7rem]">
                            <col class="w-[7rem]">
                            <col class="w-[9rem]">
                        </colgroup>
                        <thead class="table-head">
                            <tr>
                                <th>{{ __('app.common.period') }}</th>
                                <th class="text-center">{{ __('app.common.score') }}</th>
                                <th class="text-center">{{ __('app.common.grade') }}</th>
                                <th class="text-right">{{ __('app.common.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($evaluations as $evaluation)
                                <tr class="table-row">
                                    <td>
                                        @if ($canViewEvaluations)
                                            <a href="{{ route('evaluations.show', $evaluation) }}" class="font-semibold text-slate-700 hover:text-indigo-600">
                                                {{ $evaluation->period?->name }}
                                            </a>
                                        @else
                                            <span class="font-semibold text-slate-700">{{ $evaluation->period?->name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-bold text-slate-900">{{ number_format((float) $evaluation->final_score, 1) }}</td>
                                    <td class="text-center">
                                        <span class="badge-indigo">{{ \App\Models\Evaluation::gradeFor((float) $evaluation->final_score) }}</span>
                                        <span class="ml-1 text-xs text-slate-400">{{ __('app.grades.'.\App\Models\Evaluation::gradeFor((float) $evaluation->final_score)) }}</span>
                                    </td>
                                    <td class="text-right text-xs text-slate-400">{{ $evaluation->submitted_at?->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state">{{ __('app.evaluations.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.employees.goals') }}</h3>
                    @if ($canManageKpi)
                        <a href="{{ route('kpi.index', ['employee' => $employee->id]) }}" class="btn-ghost">{{ __('app.employees.view_goals') }}</a>
                    @endif
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($goals as $goal)
                        @php $percent = $goal->achievementPercent(); @endphp
                        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $goal->title }}
                                    @if ($goal->isCompleted())
                                        <span class="badge-green ml-1">{{ __('app.goals.completed') }}</span>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ $goal->period?->name }} · {{ __('app.goals.target') }}
                                    {{ rtrim(rtrim(number_format((float) $goal->target_value, 2), '0'), '.') }} {{ $goal->unit }}
                                </p>
                            </div>
                            @if ($percent === null)
                                <span class="badge-slate">{{ __('app.common.not_yet') }}</span>
                            @else
                                <span class="{{ $percent >= 100 ? 'badge-green' : ($percent >= 75 ? 'badge-indigo' : 'badge-amber') }}">
                                    {{ number_format($percent, 1) }}%
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="empty-state">{{ __('app.goals.empty') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.plans.title') }}</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($plans as $plan)
                        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $plan->title }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $plan->period?->name }} · {{ __('app.plans.due_date') }}:
                                    {{ $plan->due_date?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                            <span class="badge-amber">{{ __('app.plans.statuses.'.$plan->status) }}</span>
                        </div>
                    @empty
                        <div class="empty-state">{{ __('app.plans.empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card overflow-hidden bg-slate-900 text-white">
                <div class="p-6">
                    <p class="text-sm text-slate-400">{{ __('app.dashboard.latest_score') }}</p>
                    <p class="mt-2 text-5xl font-extrabold">
                        {{ $latest ? number_format((float) $latest->final_score, 1) : '—' }}
                    </p>
                    @if ($latest)
                        <p class="mt-2 text-sm text-slate-300">
                            {{ __('app.grades.'.\App\Models\Evaluation::gradeFor((float) $latest->final_score)) }}
                            · {{ $latest->period?->name }}
                        </p>
                    @else
                        <p class="mt-2 text-sm text-slate-400">{{ __('app.common.not_yet') }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.employees.score_history') }}</h3>
                </div>
                <div class="card-body">
                    @if ($evaluations->isEmpty())
                        <div class="empty-state">{{ __('app.dashboard.no_data') }}</div>
                    @else
                        <div class="h-48"><canvas id="employeeTrend"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('employeeTrend');
                const data = {!! $evaluations->reverse()->values()->map(fn ($e) => ['label' => $e->period?->name, 'value' => round((float) $e->final_score, 1)])->toJson() !!};

                if (el && data.length) {
                    new Chart(el, {
                        type: 'line',
                        data: {
                            labels: data.map(row => row.label),
                            datasets: [{
                                data: data.map(row => row.value),
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.12)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                borderWidth: 3,
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

    @if (auth()->user()->isAdmin() && ! $employee->trashed())
        <x-modal-form id="employee-show-edit" :title="__('app.employees.edit_title')" :subtitle="__('app.employees.subtitle')" :action="route('employees.update', $employee)" method="PUT" enctype="multipart/form-data" wide :open="old('_modal') === 'employee-show-edit'">
            @include('employees._fields', [
                'employee' => $employee,
                'departments' => $departments,
                'positions' => $positions,
                'managers' => $managers,
                'accountRoles' => $accountRoles,
                'linkableUsers' => $linkableUsers,
                'prefix' => 'employee-show-edit',
            ])
        </x-modal-form>
    @endif
@endsection
