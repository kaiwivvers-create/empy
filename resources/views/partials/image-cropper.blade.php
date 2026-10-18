@php
    $dataName = $dataName ?? 'avatar';
    $removeName = $removeName ?? null;
    $allowRemove = $allowRemove ?? false;
    $currentUrl = $currentUrl ?? null;
    $initials = $initials ?? '?';
    $label = $label ?? __('app.profile.photo');
    $hint = $hint ?? __('app.profile.photo_hint');
    $pickLabel = $pickLabel ?? __('app.profile.choose_photo');
    $removeLabel = $removeLabel ?? __('app.profile.remove_photo');
@endphp

<div data-cropper>
    <div class="flex flex-wrap items-start gap-4">
        <div class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xl font-bold text-indigo-600 ring-1 ring-indigo-100">
            <img data-crop-preview src="{{ $currentUrl ?? '' }}" alt="" class="h-full w-full object-cover {{ $currentUrl ? '' : 'hidden' }}">
            <span data-crop-initials class="{{ $currentUrl ? 'hidden' : '' }}">{{ $initials }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn-secondary btn-sm" data-crop-pick>{{ $pickLabel }}</button>
                @if ($allowRemove && $removeName)
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500">
                        <input type="checkbox" name="{{ $removeName }}" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $removeLabel }}
                    </label>
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
            <input type="file" data-crop-input accept="image/png,image/jpeg,image/webp" class="hidden">
            <input type="hidden" name="{{ $dataName }}" data-crop-data>
        </div>
    </div>

    <div data-crop-editor class="mt-4 hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-col items-center gap-4 sm:flex-row">
            <div data-crop-viewport class="relative h-56 w-56 shrink-0 cursor-grab touch-none overflow-hidden rounded-2xl bg-slate-900 active:cursor-grabbing">
                <img data-crop-image alt="" class="pointer-events-none absolute select-none">
                <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/20"></div>
            </div>
            <div class="w-full min-w-0 flex-1 space-y-3">
                <label class="label" for="{{ $dataName }}-zoom">Zoom</label>
                <input id="{{ $dataName }}-zoom" type="range" min="1" max="3" step="0.01" value="1" data-crop-zoom class="w-full accent-indigo-600">
                <button type="button" class="btn-primary btn-sm w-full" data-crop-apply>{{ __('app.profile.apply_crop') }}</button>
            </div>
        </div>
    </div>
</div>
