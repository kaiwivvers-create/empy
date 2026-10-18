@extends('layouts.app')

@section('title', __('app.criteria.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.criteria.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.criteria.subtitle') }}</p>
        </div>
        <button type="button" class="btn-primary" data-modal-open="criterion-create">
            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
            {{ __('app.criteria.add') }}
        </button>
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm ring-1 {{ $totalWeight == 100 ? 'bg-emerald-50 text-emerald-800 ring-emerald-100' : 'bg-amber-50 text-amber-800 ring-amber-100' }}">
        <span>{{ __('app.criteria.weight_hint', ['total' => rtrim(rtrim(number_format($totalWeight, 2), '0'), '.')]) }}</span>
        <span class="font-extrabold">{{ number_format($totalWeight, 0) }}%</span>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-head">
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.common.description') }}</th>
                        <th class="text-center">{{ __('app.criteria.weight') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($criteria as $criterion)
                        <tr class="table-row">
                            <td class="text-center">
                                <span class="badge-slate font-mono">{{ $criterion->sort_order }}</span>
                            </td>
                            <td class="font-semibold text-slate-800">{{ $criterion->name }}</td>
                            <td class="max-w-sm truncate text-slate-500">{{ $criterion->description ?: '-' }}</td>
                            <td class="text-center">
                                <div class="mx-auto w-28">
                                    <p class="text-sm font-bold text-slate-800">{{ rtrim(rtrim(number_format((float) $criterion->weight, 2), '0'), '.') }}%</p>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ min((float) $criterion->weight, 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="{{ $criterion->is_active ? 'badge-green' : 'badge-slate' }}">
                                    {{ $criterion->is_active ? __('app.common.active') : __('app.common.inactive') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                            data-modal-open="criterion-edit-{{ $criterion->id }}">
                                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('criteria.destroy', $criterion) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
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
        @if ($criteria->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $criteria->links() }}</div>
        @endif
    </div>

    @php $blankCriterion = new \App\Models\Criterion; @endphp

    <x-modal-form id="criterion-create" :title="__('app.criteria.create_title')" :subtitle="__('app.criteria.subtitle')" :action="route('criteria.store')" :open="$openModal === 'criterion-create'">
        @include('criteria._fields', ['criterion' => $blankCriterion, 'prefix' => 'criterion-create'])
    </x-modal-form>

    @foreach ($criteria as $criterion)
        <x-modal-form id="criterion-edit-{{ $criterion->id }}" :title="__('app.criteria.edit_title')" :subtitle="__('app.criteria.subtitle')" :action="route('criteria.update', $criterion)" method="PUT" :open="$openModal === 'criterion-edit-'.$criterion->id">
            @include('criteria._fields', ['criterion' => $criterion, 'prefix' => 'criterion-edit-'.$criterion->id])
        </x-modal-form>
    @endforeach

    @if ($editItem && ! $criteria->getCollection()->contains('id', $editItem->id))
        <x-modal-form id="criterion-edit-{{ $editItem->id }}" :title="__('app.criteria.edit_title')" :subtitle="__('app.criteria.subtitle')" :action="route('criteria.update', $editItem)" method="PUT" :open="$openModal === 'criterion-edit-'.$editItem->id">
            @include('criteria._fields', ['criterion' => $editItem, 'prefix' => 'criterion-edit-'.$editItem->id])
        </x-modal-form>
    @endif
@endsection
