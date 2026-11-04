@extends('layouts.app')

@section('title', $isManagerView ? __('app.employees.team') : __('app.employees.title'))

@section('content')
    @php
        $hasFilters = collect($filters ?? [])->contains(fn ($value) => filled($value));
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">
                {{ $isManagerView ? __('app.employees.team') : __('app.employees.title') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $isManagerView ? __('app.employees.team_subtitle') : __('app.employees.subtitle') }}
            </p>
        </div>
        @if (auth()->user()->isAdmin())
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('employees.index', ['trashed' => $showTrashed ? 0 : 1]) }}" class="btn-secondary">
                    {{ $showTrashed ? __('app.common.hide_archived') : __('app.common.show_archived') }}
                </a>
                <a href="{{ route('employees.import') }}" class="btn-secondary">
                    <svg class="h-4 w-4"><use href="#i-download"/></svg>
                    {{ __('app.import.title') }}
                </a>
                <button type="button" class="btn-primary" data-modal-open="employee-create">
                    <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                    {{ __('app.employees.add') }}
                </button>
            </div>
        @endif
    </div>

    <div class="card">
        {{-- An employee only ever sees their own row, so filters cannot narrow
             the list any further. --}}
        @unless (auth()->user()->isEmployee())
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @if ($showTrashed)
                    <input type="hidden" name="trashed" value="1">
                @endif
                <div class="lg:col-span-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('app.common.search_placeholder') }}" class="input pl-9">
                    </div>
                </div>
                <select name="department" class="input" data-searchable>
                    <option value="">{{ __('app.common.all') }} · {{ __('app.common.department') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(($filters['department'] ?? '') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <select name="status" class="input" data-searchable>
                        <option value="">{{ __('app.common.all') }} · {{ __('app.common.status') }}</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('app.common.active') }}</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('app.common.inactive') }}</option>
                    </select>
                    <button type="submit" class="btn-secondary shrink-0">{{ __('app.actions.filter') }}</button>
                </div>
            </form>
        </div>
        @endunless

        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[68rem]">
                <colgroup>
                    <col>
                    <col class="w-[11rem]">
                    <col class="w-[11rem]">
                    <col class="w-[11rem]">
                    <col class="w-[8rem]">
                    <col class="w-[17rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.employee') }}</th>
                        <th>{{ __('app.common.department') }}</th>
                        <th>{{ __('app.common.position') }}</th>
                        <th>{{ __('app.common.manager') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr class="table-row">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">
                                        @if ($employee->photoUrl())
                                            <img src="{{ $employee->photoUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ $employee->initials() }}
                                        @endif
                                    </span>
                                    <div>
                                        @if ($showTrashed)
                                            <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                                        @else
                                            <a href="{{ route('employees.show', $employee) }}" class="font-semibold text-slate-800 hover:text-indigo-600">
                                                {{ $employee->name }}
                                            </a>
                                        @endif
                                        <p class="text-xs text-slate-400">{{ $employee->nip }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-600">{{ $employee->department?->name ?? '-' }}</td>
                            <td class="text-slate-600">{{ $employee->position?->name ?? '-' }}</td>
                            <td class="text-slate-600">{{ $employee->manager?->name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="{{ $employee->status === 'active' ? 'badge-green' : 'badge-slate' }}">
                                    {{ $employee->status === 'active' ? __('app.common.active') : __('app.common.inactive') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('employees.show', $employee) }}" class="btn-ghost">{{ __('app.actions.detail') }}</a>
                                    @if (auth()->user()->isAdmin())
                                        @if ($showTrashed)
                                            <form method="POST" action="{{ route('employees.restore', $employee->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-ghost text-emerald-600 hover:bg-emerald-50">{{ __('app.common.restore') }}</button>
                                            </form>
                                        @else
                                            @if (\App\Support\PageAccess::allows(auth()->user(), 'kpi'))
                                                <a href="{{ route('kpi.index', ['employee' => $employee->id]) }}" class="btn-ghost">{{ __('app.employees.view_goals') }}</a>
                                            @endif
                                            <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                                    data-modal-open="employee-edit-{{ $employee->id }}">
                                                <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('{{ __('app.common.archive') }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="{{ __('app.common.archive') }}">
                                                    <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <p>{{ $hasFilters ? __('app.common.no_results') : __('app.employees.empty') }}</p>
                                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                                    @if ($hasFilters)
                                        <a href="{{ route('employees.index', $showTrashed ? ['trashed' => 1] : []) }}" class="btn-secondary">{{ __('app.actions.reset') }}</a>
                                    @endif
                                    @if (auth()->user()->isAdmin() && ! $showTrashed)
                                        <button type="button" class="btn-primary" data-modal-open="employee-create">
                                            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                                            {{ __('app.employees.add') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $employees->links() }}</div>
        @endif
    </div>

    @if (auth()->user()->isAdmin())
        @php $blankEmployee = new \App\Models\Employee; @endphp

        <x-modal-form id="employee-create" :title="__('app.employees.create_title')" :subtitle="__('app.employees.subtitle')" :action="route('employees.store')" enctype="multipart/form-data" wide :open="$openModal === 'employee-create'">
            @include('employees._fields', [
                'employee' => $blankEmployee,
                'departments' => $departments,
                'positions' => $positions,
                'managers' => $managers,
                'accountRoles' => $accountRoles,
                'prefix' => 'employee-create',
            ])
        </x-modal-form>

        @unless ($showTrashed)
            @foreach ($employees as $employee)
                <x-modal-form id="employee-edit-{{ $employee->id }}" :title="__('app.employees.edit_title')" :subtitle="__('app.employees.subtitle')" :action="route('employees.update', $employee)" method="PUT" enctype="multipart/form-data" wide :open="$openModal === 'employee-edit-'.$employee->id">
                    @include('employees._fields', [
                        'employee' => $employee,
                        'departments' => $departments,
                        'positions' => $positions,
                        'managers' => $managers,
                        'accountRoles' => $accountRoles,
                        'prefix' => 'employee-edit-'.$employee->id,
                    ])
                </x-modal-form>
            @endforeach
        @endunless

        @if ($editItem && ! $showTrashed && ! $employees->getCollection()->contains('id', $editItem->id))
            <x-modal-form id="employee-edit-{{ $editItem->id }}" :title="__('app.employees.edit_title')" :subtitle="__('app.employees.subtitle')" :action="route('employees.update', $editItem)" method="PUT" enctype="multipart/form-data" wide :open="$openModal === 'employee-edit-'.$editItem->id">
                @include('employees._fields', [
                    'employee' => $editItem,
                    'departments' => $departments,
                    'positions' => $positions,
                    'managers' => $managers,
                    'accountRoles' => $accountRoles,
                    'prefix' => 'employee-edit-'.$editItem->id,
                ])
            </x-modal-form>
        @endif
    @endif
@endsection
