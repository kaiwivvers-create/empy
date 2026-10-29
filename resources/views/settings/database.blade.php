@extends('layouts.app')

@section('title', __('app.database.title'))

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.database.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.database.subtitle') }}</p>
        </div>
        <a href="{{ route('database.export') }}" class="btn-primary">
            <svg class="h-4 w-4"><use href="#i-download"/></svg>
            {{ __('app.database.export') }}
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.database.driver') }}</p>
            <p class="mt-2 text-2xl font-extrabold uppercase text-slate-900">{{ $driver }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $database }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.database.tables') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ count($tables) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.database.total_rows') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalRows) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">{{ __('app.database.backups') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $backups->count() }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="font-bold text-slate-900">{{ __('app.database.import_title') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('app.database.import_hint') }}</p>
                </div>
                <span class="badge-amber">{{ __('app.database.danger') }}</span>
            </div>
            <form method="POST" action="{{ route('database.import') }}" enctype="multipart/form-data" class="card-body space-y-4"
                  onsubmit="return confirm('{{ __('app.database.import_confirm') }}')">
                @csrf
                <div>
                    <label class="label" for="sql_file">{{ __('app.database.file') }}</label>
                    <input id="sql_file" type="file" name="sql_file" accept=".sql,.txt" class="input" required>
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.database.file_hint') }}</p>
                    @error('sql_file') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <input type="checkbox" name="confirm_restore" value="1" class="mt-0.5 h-4 w-4 rounded border-amber-300 text-amber-600" required>
                    <span class="text-xs leading-relaxed text-amber-800">{{ __('app.database.confirm') }}</span>
                </label>
                @error('confirm_restore') <p class="form-error">{{ $message }}</p> @enderror
                <div class="flex justify-end">
                    <button type="submit" class="btn-danger">{{ __('app.database.import') }}</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.database.backups') }}</h3>
                <span class="badge-slate">{{ $backups->count() }}</span>
            </div>
            <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                @forelse ($backups as $backup)
                    <div class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-xs font-semibold text-slate-700">{{ $backup['name'] }}</p>
                            <p class="text-[11px] text-slate-400">
                                {{ number_format($backup['size'] / 1024, 1) }} KB ·
                                {{ \Illuminate\Support\Carbon::createFromTimestamp($backup['modified'])->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('database.backup.download', $backup['name']) }}" class="btn-ghost">{{ __('app.actions.download') }}</a>
                            <form method="POST" action="{{ route('database.backup.destroy', $backup['name']) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">{{ __('app.database.no_backups') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="font-bold text-slate-900">{{ __('app.database.tables') }}</h3>
            <span class="text-xs text-slate-400">{{ __('app.database.table_hint') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[30rem]">
                <colgroup>
                    <col>
                    <col class="w-[8rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.database.table') }}</th>
                        <th class="text-right">{{ __('app.database.rows') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tables as $table)
                        <tr class="table-row">
                            <td class="font-mono text-xs font-semibold text-slate-700">{{ $table }}</td>
                            <td class="text-right font-semibold text-slate-800">{{ number_format($counts[$table] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
