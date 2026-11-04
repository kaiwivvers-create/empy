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
    $cropperId = 'cropper-'.\Illuminate\Support\Str::uuid();
@endphp

<div data-cropper>
    <div class="flex flex-wrap items-start gap-4">
        <div class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xl font-bold text-indigo-600 ring-1 ring-indigo-100">
            <img data-crop-preview src="{{ $currentUrl ?? '' }}" alt="" class="h-full w-full object-cover {{ $currentUrl ? '' : 'hidden' }}">
            <span data-crop-initials class="{{ $currentUrl ? 'hidden' : '' }}">{{ $initials }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        class="btn-secondary btn-sm"
                        data-crop-pick
                        data-label-pick="{{ $pickLabel }}"
                        data-label-change="{{ __('app.profile.change_photo') }}">{{ $pickLabel }}</button>
                @if ($allowRemove && $removeName)
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500">
                        <input type="checkbox" name="{{ $removeName }}" value="1" data-crop-remove class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $removeLabel }}
                    </label>
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
            <input type="file" data-crop-input accept="image/png,image/jpeg,image/webp" class="hidden">
            <input type="hidden" name="{{ $dataName }}" data-crop-data>
        </div>
    </div>

    <div id="{{ $cropperId }}"
         class="modal"
         data-crop-modal
         data-open="false"
         aria-hidden="true"
         style="z-index: 90">
        <div class="modal-backdrop" data-crop-close></div>

        <div class="modal-panel max-w-3xl" role="dialog" aria-modal="true" aria-labelledby="{{ $cropperId }}-title">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 id="{{ $cropperId }}-title" class="text-lg font-extrabold text-slate-900">{{ __('app.profile.crop_title') }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ __('app.profile.crop_subtitle') }}</p>
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-crop-close aria-label="{{ __('app.actions.close') }}">
                    <svg class="h-5 w-5"><use href="#i-x"/></svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <div class="flex flex-col items-center gap-5 lg:flex-row lg:items-start">
                    <div data-crop-viewport class="relative h-80 w-80 max-w-full shrink-0 cursor-grab touch-none overflow-hidden rounded-2xl bg-slate-900 active:cursor-grabbing">
                        <canvas data-crop-canvas class="h-full w-full"></canvas>
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/25"></div>
                        <div class="pointer-events-none absolute inset-x-0 top-1/3 border-t border-white/20"></div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-1/3 border-t border-white/20"></div>
                        <div class="pointer-events-none absolute inset-y-0 left-1/3 border-l border-white/20"></div>
                        <div class="pointer-events-none absolute inset-y-0 right-1/3 border-l border-white/20"></div>
                    </div>

                    <div class="w-full min-w-0 flex-1 space-y-4">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="label !mb-0" for="{{ $cropperId }}-zoom">{{ __('app.profile.crop_zoom') }}</label>
                                <span class="text-xs font-bold text-slate-500" data-crop-zoom-label>100%</span>
                            </div>
                            <input id="{{ $cropperId }}-zoom" type="range" min="1" max="3" step="0.01" value="1" data-crop-zoom class="w-full accent-indigo-600">
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="label !mb-0" for="{{ $cropperId }}-rotation">{{ __('app.profile.crop_rotation') }}</label>
                                <span class="text-xs font-bold text-slate-500" data-crop-rotation-label>0°</span>
                            </div>
                            <input id="{{ $cropperId }}-rotation" type="range" min="-180" max="180" step="1" value="0" data-crop-rotation class="w-full accent-indigo-600">
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="btn-secondary btn-sm justify-center" data-crop-rotate="-90">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
                                {{ __('app.profile.crop_rotate_left') }}
                            </button>
                            <button type="button" class="btn-secondary btn-sm justify-center" data-crop-rotate="90">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
                                {{ __('app.profile.crop_rotate_right') }}
                            </button>
                            <button type="button" class="btn-secondary btn-sm justify-center" data-crop-flip="x">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><path d="m8 7-5 5 5 5"/><path d="m16 7 5 5-5 5"/></svg>
                                {{ __('app.profile.crop_flip_horizontal') }}
                            </button>
                            <button type="button" class="btn-secondary btn-sm justify-center" data-crop-flip="y">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="m7 8 5-5 5 5"/><path d="m7 16 5 5 5-5"/></svg>
                                {{ __('app.profile.crop_flip_vertical') }}
                            </button>
                        </div>

                        <button type="button" class="btn-ghost" data-crop-reset>
                            <svg class="h-4 w-4"><use href="#i-x"/></svg>
                            {{ __('app.profile.crop_reset') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                <button type="button" class="btn-secondary" data-crop-close>{{ __('app.actions.cancel') }}</button>
                <button type="button" class="btn-primary" data-crop-apply>{{ __('app.profile.crop_apply') }}</button>
            </div>
        </div>
    </div>
</div>
