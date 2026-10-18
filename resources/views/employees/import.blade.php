@extends('layouts.app')

@section('title', __('app.import.title'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.import.title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('app.import.subtitle') }}</p>
            </div>
            <a href="{{ route('employees.index') }}" class="btn-secondary">{{ __('app.actions.back') }}</a>
        </div>

        @if (session('import_errors'))
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="mb-1 font-bold">{{ __('app.import.errors_title') }}</p>
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('employees.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body space-y-4">
                    <div>
                        <label class="label" for="file">{{ __('app.import.file') }}</label>
                        <input id="file" type="file" name="file" accept=".csv,text/csv" class="input" required>
                        @error('file') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-600">
                        <p class="font-semibold text-slate-700">{{ __('app.import.columns') }}</p>
                        <p class="mt-1">{{ __('app.import.hint') }}</p>
                    </div>

                    <a href="{{ route('employees.import.template') }}" class="btn-ghost">
                        <svg class="h-4 w-4"><use href="#i-download"/></svg>
                        {{ __('app.import.download_template') }}
                    </a>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <a href="{{ route('employees.index') }}" class="btn-secondary">{{ __('app.actions.cancel') }}</a>
                    <button type="submit" class="btn-primary">{{ __('app.import.button') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
