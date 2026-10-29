@extends('layouts.app')

@section('title', __('app.positions.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.positions.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.positions.subtitle') }}</p>
        </div>
        <button type="button" class="btn-primary" data-modal-open="position-create">
            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
            {{ __('app.positions.add') }}
        </button>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[46rem]">
                <colgroup>
                    <col>
                    <col class="w-[10rem]">
                    <col class="w-[8rem]">
                    <col class="w-[9rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.positions.level') }}</th>
                        <th class="text-center">{{ __('app.departments.employees_count') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $position)
                        <tr class="table-row">
                            <td class="font-semibold text-slate-800">{{ $position->name }}</td>
                            <td>
                                @if ($position->level)
                                    <span class="badge-indigo">{{ $position->level }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="text-center font-semibold text-slate-700">{{ $position->employees_count }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                            data-modal-open="position-edit-{{ $position->id }}">
                                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('positions.destroy', $position) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
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
                        <tr><td colspan="4" class="empty-state">{{ __('app.common.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($positions->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $positions->links() }}</div>
        @endif
    </div>

    @php $blankPosition = new \App\Models\Position; @endphp

    <x-modal-form id="position-create" :title="__('app.positions.create_title')" :subtitle="__('app.positions.subtitle')" :action="route('positions.store')" :open="$openModal === 'position-create'">
        @include('positions._fields', ['position' => $blankPosition, 'prefix' => 'position-create'])
    </x-modal-form>

    @foreach ($positions as $position)
        <x-modal-form id="position-edit-{{ $position->id }}" :title="__('app.positions.edit_title')" :subtitle="__('app.positions.subtitle')" :action="route('positions.update', $position)" method="PUT" :open="$openModal === 'position-edit-'.$position->id">
            @include('positions._fields', ['position' => $position, 'prefix' => 'position-edit-'.$position->id])
        </x-modal-form>
    @endforeach

    @if ($editItem && ! $positions->getCollection()->contains('id', $editItem->id))
        <x-modal-form id="position-edit-{{ $editItem->id }}" :title="__('app.positions.edit_title')" :subtitle="__('app.positions.subtitle')" :action="route('positions.update', $editItem)" method="PUT" :open="$openModal === 'position-edit-'.$editItem->id">
            @include('positions._fields', ['position' => $editItem, 'prefix' => 'position-edit-'.$editItem->id])
        </x-modal-form>
    @endif
@endsection
