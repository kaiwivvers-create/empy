@extends('layouts.app')

@section('title', __('app.access.title'))

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.access.title') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.access.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('access.update') }}" class="card">
        @csrf
        @method('PUT')

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.access.page') }}</th>
                        @foreach ($roles as $role)
                            <th class="text-center">{{ __('app.roles.'.$role) }}</th>
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
@endsection
