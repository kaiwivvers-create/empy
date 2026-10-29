@extends('layouts.app')

@section('title', __('app.activity.title'))

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.activity.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.activity.subtitle') }}</p>
        <p class="mt-2 flex items-center gap-2 text-xs text-slate-400">
            <svg class="h-3.5 w-3.5"><use href="#i-trash"/></svg>
            {{ __('app.activity.deleted_hint') }}
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('app.common.search_placeholder') }}" class="input pl-9">
                    </div>
                </div>
                <select name="action" class="input">
                    <option value="">{{ __('app.common.all') }} · {{ __('app.activity.action') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <select name="user" class="input">
                        <option value="">{{ __('app.common.all') }} · {{ __('app.activity.user') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user'] ?? '') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary shrink-0">{{ __('app.actions.filter') }}</button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[58rem]">
                <colgroup>
                    <col class="w-[9.5rem]">
                    <col class="w-[8rem]">
                    <col class="w-[7.5rem]">
                    <col>
                    <col class="w-[17rem]">
                    <col class="w-[11rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.activity.when') }}</th>
                        <th>{{ __('app.activity.user') }}</th>
                        <th>{{ __('app.activity.action') }}</th>
                        <th>{{ __('app.activity.description') }}</th>
                        <th>{{ __('app.activity.changes') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $before = $log->before();
                            $after = $log->after();
                            $fields = array_unique(array_merge(array_keys($before), array_keys($after)));
                        @endphp
                        <tr class="table-row align-top">
                            <td class="whitespace-nowrap text-xs text-slate-500">
                                {{ $log->created_at->translatedFormat('d M Y H:i') }}
                            </td>
                            <td class="whitespace-nowrap font-medium text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                            <td>
                                <span class="font-mono badge-slate">{{ $log->action }}</span>
                                @if ($log->isReverted())
                                    <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wide text-emerald-600">{{ __('app.activity.reverted') }}</span>
                                @endif
                            </td>
                            <td class="max-w-xs text-slate-600">{{ $log->description }}</td>
                            <td class="max-w-sm">
                                @if ($fields !== [])
                                    <details class="group">
                                        <summary class="cursor-pointer text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            {{ __('app.activity.changes') }} ({{ count($fields) }})
                                        </summary>
                                        <div class="mt-2 space-y-1 rounded-lg bg-slate-50 p-2.5 text-xs">
                                            @foreach ($fields as $field)
                                                <div class="grid grid-cols-[6rem_minmax(0,1fr)] gap-2">
                                                    <span class="font-semibold text-slate-500">{{ $field }}</span>
                                                    <span class="min-w-0 break-words text-slate-600">
                                                        <span class="text-rose-500">{{ \Illuminate\Support\Str::limit((string) ($before[$field] ?? '—'), 60) }}</span>
                                                        <span class="mx-1 text-slate-300">→</span>
                                                        <span class="text-emerald-600">{{ \Illuminate\Support\Str::limit((string) ($after[$field] ?? '—'), 60) }}</span>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-col items-end gap-1.5">
                                    @if ($log->canRevert())
                                        <form method="POST" action="{{ route('activity.revert', $log) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                                    onclick="return confirm('{{ __('app.activity.confirm_revert') }}')">
                                                {{ __('app.activity.revert') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if ($log->canPurge())
                                        <form method="POST" action="{{ route('activity.destroy', $log) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                                    onclick="return confirm('{{ __('app.activity.confirm_purge') }}')">
                                                {{ __('app.activity.permanently_delete') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if (! $log->canRevert() && ! $log->canPurge())
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('app.activity.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
