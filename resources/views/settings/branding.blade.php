@extends('layouts.app')

@section('title', __('app.branding.title'))

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.branding.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.branding.subtitle') }}</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="card">
            @csrf
            <div class="card-body space-y-5">
                <div>
                    <label class="label" for="brand_name">{{ __('app.branding.name') }}</label>
                    <input id="brand_name" name="brand_name" value="{{ old('brand_name', $name) }}" class="input" required maxlength="60">
                    @error('brand_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="brand_tagline">{{ __('app.branding.tagline') }}</label>
                    <input id="brand_tagline" name="brand_tagline" value="{{ old('brand_tagline', $tagline) }}" class="input" maxlength="120">
                    @error('brand_tagline') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="brand_description">{{ __('app.branding.description') }}</label>
                    <textarea id="brand_description" name="brand_description" rows="3" class="input" maxlength="500">{{ old('brand_description', $description) }}</textarea>
                    <p class="mt-1.5 text-xs text-slate-500">{{ __('app.branding.description_hint') }}</p>
                    @error('brand_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <p class="label">{{ __('app.branding.logo') }}</p>
                    @include('partials.image-cropper', [
                        'dataName' => 'logo_data',
                        'removeName' => 'remove_logo',
                        'allowRemove' => (bool) $logoUrl,
                        'currentUrl' => $logoUrl,
                        'initials' => mb_strtoupper(mb_substr($name, 0, 1)),
                        'hint' => __('app.branding.logo_hint'),
                        'pickLabel' => __('app.profile.choose_photo'),
                        'removeLabel' => __('app.branding.remove_logo'),
                    ])
                    @error('logo_data') <p class="form-error">{{ $message }}</p> @enderror
                    @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 px-5 py-4">
                <button type="submit" class="btn-primary">{{ __('app.branding.save') }}</button>
            </div>
        </form>

        <div class="card h-fit">
            <div class="card-header">
                <h3 class="text-sm font-bold text-slate-700">{{ __('app.branding.preview') }}</h3>
            </div>
            <div class="card-body">
                <div class="rounded-2xl bg-slate-900 p-5">
                    <div class="flex items-center gap-3">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="h-10 w-10 rounded-xl object-contain">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-xs font-bold text-slate-400">
                                {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-base font-extrabold text-white">{{ $name }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ $tagline }}</p>
                            <p class="mt-2 line-clamp-3 text-[11px] leading-relaxed text-slate-400">{{ $description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
