@extends('layouts.app')

@section('title', __('app.reports.calibration'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.reports.calibration') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.reports.calibration_subtitle') }}</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn-secondary">{{ __('app.actions.back') }}</a>
    </div>

    <div class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="label" for="period">{{ __('app.common.period') }}</label>
                <form method="GET" class="flex gap-2">
                    <select id="period" name="period" class="input" data-searchable onchange="this.form.submit()">
                        @foreach ($periods as $item)
                            <option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary shrink-0">{{ __('app.actions.filter') }}</button>
                </form>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('app.reports.company_average') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($companyAverage, 1) }}</p>
                <p class="text-xs text-slate-400">{{ $evaluated }} {{ __('app.reports.evaluated') }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[64rem]">
                <colgroup>
                    <col>
                    <col class="w-[8rem]">
                    <col class="w-[8rem]">
                    <col class="w-[8rem]">
                    <col class="w-[8rem]">
                    <col class="w-[17rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.reports.evaluator') }}</th>
                        <th class="text-center">{{ __('app.reports.evaluated') }}</th>
                        <th class="text-center">{{ __('app.common.average') }}</th>
                        <th class="text-center">{{ __('app.reports.deviation') }}</th>
                        <th class="text-center">{{ __('app.reports.low_scores') }}</th>
                        <th>{{ __('app.reports.fair_distribution') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        @php
                            $deviation = $group['deviation'];
                            $tone = abs($deviation) <= 3 ? 'badge-green' : (abs($deviation) <= 7 ? 'badge-amber' : 'badge-red');
                        @endphp
                        <tr class="table-row">
                            <td class="font-semibold text-slate-800">{{ $group['name'] }}</td>
                            <td class="text-center text-slate-500">{{ $group['count'] }}</td>
                            <td class="text-center font-bold text-slate-900">{{ number_format($group['average'], 1) }}</td>
                            <td class="text-center">
                                <span class="{{ $tone }}">
                                    {{ $deviation > 0 ? '+' : '' }}{{ number_format($deviation, 1) }}
                                </span>
                            </td>
                            <td class="text-center text-slate-600">{{ $group['low'] }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 min-w-0 max-w-[160px] flex-1 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ min($group['average'], 100) }}%"></div>
                                    </div>
                                    <span class="shrink-0 whitespace-nowrap text-xs text-slate-400">{{ number_format($group['min'], 1) }}–{{ number_format($group['max'], 1) }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('app.reports.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
