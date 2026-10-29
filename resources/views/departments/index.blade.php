@extends('layouts.app')

@section('title', __('app.departments.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.departments.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.departments.subtitle') }}</p>
        </div>
        <button type="button" class="btn-primary" data-modal-open="department-create">
            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
            {{ __('app.departments.add') }}
        </button>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[60rem]">
                <colgroup>
                    <col class="w-[7rem]">
                    <col>
                    <col class="w-[18rem]">
                    <col class="w-[12rem]">
                    <col class="w-[7rem]">
                    <col class="w-[9rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.code') }}</th>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.common.description') }}</th>
                        <th>{{ __('app.organization.head') }}</th>
                        <th class="text-center">{{ __('app.departments.employees_count') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr class="table-row">
                            <td><span class="badge-slate font-mono">{{ $department->code }}</span></td>
                            <td class="font-semibold text-slate-800">{{ $department->name }}</td>
                            <td class="max-w-xs truncate text-slate-500">{{ $department->description ?: '-' }}</td>
                            <td class="text-slate-600">{{ $department->head?->name ?? '-' }}</td>
                            <td class="text-center font-semibold text-slate-700">{{ $department->employees_count }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                            data-modal-open="department-edit-{{ $department->id }}">
                                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="{{ __('app.actions.delete') }}">
                                            <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('app.common.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departments->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $departments->links() }}</div>
        @endif
    </div>

    @php $blankDepartment = new \App\Models\Department; @endphp

    <x-modal-form id="department-create" :title="__('app.departments.create_title')" :subtitle="__('app.departments.subtitle')" :action="route('departments.store')" :open="$openModal === 'department-create'">
        @include('departments._fields', ['department' => $blankDepartment, 'employees' => $employees, 'prefix' => 'department-create'])
    </x-modal-form>

    @foreach ($departments as $department)
        <x-modal-form id="department-edit-{{ $department->id }}" :title="__('app.departments.edit_title')" :subtitle="__('app.departments.subtitle')" :action="route('departments.update', $department)" method="PUT" :open="$openModal === 'department-edit-'.$department->id">
            @include('departments._fields', ['department' => $department, 'employees' => $employees, 'prefix' => 'department-edit-'.$department->id])
        </x-modal-form>
    @endforeach

    @if ($editItem && ! $departments->getCollection()->contains('id', $editItem->id))
        <x-modal-form id="department-edit-{{ $editItem->id }}" :title="__('app.departments.edit_title')" :subtitle="__('app.departments.subtitle')" :action="route('departments.update', $editItem)" method="PUT" :open="$openModal === 'department-edit-'.$editItem->id">
            @include('departments._fields', ['department' => $editItem, 'employees' => $employees, 'prefix' => 'department-edit-'.$editItem->id])
        </x-modal-form>
    @endif
@endsection
