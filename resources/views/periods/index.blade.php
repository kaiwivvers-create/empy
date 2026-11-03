@extends('layouts.app')

@section('title', __('app.periods.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.periods.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.periods.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('periods.index', ['trashed' => $showTrashed ? 0 : 1]) }}" class="btn-secondary">
                {{ $showTrashed ? __('app.common.hide_archived') : __('app.common.show_archived') }}
            </a>
            <button type="button" class="btn-primary" data-modal-open="period-create">
                <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                {{ __('app.periods.add') }}
            </button>
        </div>
    </div>

    <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        {{ __('app.periods.activate_hint') }}
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[62rem]">
                <colgroup>
                    <col>
                    <col class="w-[8rem]">
                    <col class="w-[12rem]">
                    <col class="w-[9rem]">
                    <col class="w-[8rem]">
                    <col class="w-[17rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.period') }}</th>
                        <th>{{ __('app.periods.type') }}</th>
                        <th>{{ __('app.periods.range') }}</th>
                        <th class="text-center">{{ __('app.nav.evaluations') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periods as $period)
                        <tr class="table-row">
                            <td class="font-semibold text-slate-800">{{ $period->name }}</td>
                            <td class="text-slate-600">{{ __('app.periods.types.'.$period->type) }}</td>
                            <td class="text-sm text-slate-500">
                                {{ $period->start_date?->format('d M Y') }} — {{ $period->end_date?->format('d M Y') }}
                            </td>
                            <td class="text-center font-semibold text-slate-700">{{ $period->evaluations_count }}</td>
                            <td class="text-center">
                                @if ($period->status === 'active')
                                    <span class="badge-green">{{ __('app.periods.statuses.active') }}</span>
                                @elseif ($period->status === 'closed')
                                    <span class="badge-slate">{{ __('app.periods.statuses.closed') }}</span>
                                @else
                                    <span class="badge-amber">{{ __('app.periods.statuses.draft') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @if ($showTrashed)
                                        <form method="POST" action="{{ route('periods.restore', $period->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-ghost text-emerald-600 hover:bg-emerald-50">{{ __('app.common.restore') }}</button>
                                        </form>
                                    @elseif ($period->status !== 'active')
                                        <form method="POST" action="{{ route('periods.activate', $period) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-ghost text-emerald-600 hover:bg-emerald-50">{{ __('app.actions.activate') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('periods.close', $period) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-ghost text-amber-600 hover:bg-amber-50">{{ __('app.actions.close') }}</button>
                                        </form>
                                    @endif
                                    @unless ($showTrashed)
                                        <form method="POST" action="{{ route('periods.remind', $period) }}">
                                            @csrf
                                            <button type="submit" class="btn-ghost" title="{{ __('app.reminders.hint') }}">{{ __('app.reminders.send') }}</button>
                                        </form>
                                        <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                                data-modal-open="period-edit-{{ $period->id }}">
                                            <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('periods.destroy', $period) }}" onsubmit="return confirm('{{ __('app.common.archive') }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="{{ __('app.common.archive') }}">
                                                <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('app.common.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($periods->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $periods->links() }}</div>
        @endif
    </div>

    @php $blankPeriod = new \App\Models\EvaluationPeriod; @endphp

    <x-modal-form id="period-create" :title="__('app.periods.create_title')" :subtitle="__('app.periods.subtitle')" :action="route('periods.store')" :open="$openModal === 'period-create'">
        @include('periods._fields', ['period' => $blankPeriod, 'prefix' => 'period-create'])
    </x-modal-form>

    @unless ($showTrashed)
        @foreach ($periods as $period)
            <x-modal-form id="period-edit-{{ $period->id }}" :title="__('app.periods.edit_title')" :subtitle="__('app.periods.subtitle')" :action="route('periods.update', $period)" method="PUT" :open="$openModal === 'period-edit-'.$period->id">
                @include('periods._fields', ['period' => $period, 'prefix' => 'period-edit-'.$period->id])
            </x-modal-form>
        @endforeach
    @endunless

    @if ($editItem && ! $showTrashed && ! $periods->getCollection()->contains('id', $editItem->id))
        <x-modal-form id="period-edit-{{ $editItem->id }}" :title="__('app.periods.edit_title')" :subtitle="__('app.periods.subtitle')" :action="route('periods.update', $editItem)" method="PUT" :open="$openModal === 'period-edit-'.$editItem->id">
            @include('periods._fields', ['period' => $editItem, 'prefix' => 'period-edit-'.$editItem->id])
        </x-modal-form>
    @endif
@endsection
