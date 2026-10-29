@extends('layouts.app')

@section('title', __('app.access.title'))

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.access.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.access.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="btn-secondary" data-modal-open="access-defaults">
                <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                {{ __('app.access.edit_defaults') }}
            </button>
            <form method="POST" action="{{ route('access.reset') }}" onsubmit="return confirm('{{ __('app.access.reset_confirm') }}')">
                @csrf
                <button type="submit" class="btn-secondary">
                    <svg class="h-4 w-4"><use href="#i-shield"/></svg>
                    {{ __('app.access.reset') }}
                </button>
            </form>
        </div>
    </div>

    <div class="mb-4 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        {{ __('app.access.defaults_hint') }}
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('access.update') }}" class="card">
        @csrf
        @method('PUT')

        <div class="overflow-x-auto">
            <table class="table-stable w-full">
                <colgroup>
                    <col>
                    @foreach ($roles as $role)
                        <col class="w-[7rem]">
                    @endforeach
                    <col class="w-[9rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.access.page') }}</th>
                        @foreach ($roles as $role)
                            <th class="text-center">{{ \App\Models\Role::allCached()->firstWhere('name', $role)?->displayLabel() ?? __('app.roles.'.$role) }}</th>
                        @endforeach
                        <th class="text-center">
                            <span class="inline-flex items-center gap-1.5 text-indigo-600">
                                <svg class="h-3.5 w-3.5"><use href="#i-shield"/></svg>
                                {{ __('app.access.super_admin') }}
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (collect($pages)->groupBy('group') as $group => $items)
                        <tr class="border-t border-slate-100 bg-slate-50/80">
                            <td colspan="{{ count($roles) + 2 }}" class="px-5 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                {{ __('app.access.group_'.$group) }}
                            </td>
                        </tr>
                        @foreach ($items as $page => $meta)
                            <tr class="table-row">
                                <td class="font-medium text-slate-700">{{ __($meta['label']) }}</td>
                                @foreach ($roles as $role)
                                    <td class="text-center">
                                        <input type="checkbox"
                                               name="permissions[{{ $role }}][{{ $page }}]"
                                               value="1"
                                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                               @checked(old('permissions.'.$role.'.'.$page, $matrix[$role][$page] ?? false))>
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-indigo-600">
                                        <svg class="h-3 w-3"><use href="#i-check"/></svg>
                                        {{ __('app.access.always_on') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
            <p class="flex items-center gap-2 text-xs text-slate-500">
                <svg class="h-4 w-4 text-indigo-500"><use href="#i-shield"/></svg>
                {{ __('app.access.super_admin_hint') }}
            </p>
            <button type="submit" class="btn-primary">{{ __('app.access.save') }}</button>
        </div>
    </form>

    <x-modal-form id="access-defaults" :title="__('app.access.defaults_title')" :subtitle="__('app.access.defaults_subtitle')" :action="route('access.defaults')" method="PUT" wide>
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="table-stable w-full">
                <colgroup>
                    <col>
                    @foreach ($roles as $role)
                        <col class="w-[7rem]">
                    @endforeach
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.access.page') }}</th>
                        @foreach ($roles as $role)
                            <th class="text-center">{{ \App\Models\Role::allCached()->firstWhere('name', $role)?->displayLabel() ?? __('app.roles.'.$role) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (collect($pages)->groupBy('group') as $group => $items)
                        <tr class="border-t border-slate-100 bg-slate-50/80">
                            <td colspan="{{ count($roles) + 1 }}" class="px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                {{ __('app.access.group_'.$group) }}
                            </td>
                        </tr>
                        @foreach ($items as $page => $meta)
                            <tr class="table-row">
                                <td class="font-medium text-slate-700">{{ __($meta['label']) }}</td>
                                @foreach ($roles as $role)
                                    <td class="text-center">
                                        <input type="checkbox"
                                               name="permissions[{{ $page }}][{{ $role }}]"
                                               value="1"
                                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                               @checked(old('permissions.'.$page.'.'.$role, $defaults[$page][$role] ?? false))>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-modal-form>
@endsection
