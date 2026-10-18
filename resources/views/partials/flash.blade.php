@if (session('success'))
    <div data-flash class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0"><use href="#i-check"/></svg>
        <p class="flex-1">{{ session('success') }}</p>
        <button type="button" data-dismiss class="text-emerald-600 hover:text-emerald-900">
            <svg class="h-4 w-4"><use href="#i-x"/></svg>
        </button>
    </div>
@endif

@if (session('error'))
    <div data-flash class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0"><use href="#i-x"/></svg>
        <p class="flex-1">{{ session('error') }}</p>
        <button type="button" data-dismiss class="text-rose-600 hover:text-rose-900">
            <svg class="h-4 w-4"><use href="#i-x"/></svg>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p class="mb-1 font-semibold">{{ __('app.common.fix_errors') }}</p>
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
