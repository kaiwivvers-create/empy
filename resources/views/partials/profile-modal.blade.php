@php
    $avatarUrl = $user->avatarUrl();
    $openOnError = $errors->updateProfile->any() || $errors->updatePassword->any();
@endphp

<div id="profile-modal"
     class="modal"
     data-modal
     data-open="{{ $openOnError ? 'true' : 'false' }}"
     aria-hidden="{{ $openOnError ? 'false' : 'true' }}">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="modal-panel max-w-2xl" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 id="profile-modal-title" class="text-lg font-extrabold text-slate-900">{{ __('app.profile.title') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.profile.subtitle') }}</p>
            </div>
            <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-modal-close aria-label="{{ __('app.actions.close') }}">
                <svg class="h-5 w-5"><use href="#i-x"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-5">
            @if ($errors->updateProfile->any() || $errors->updatePassword->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->updateProfile->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @foreach ($errors->updatePassword->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <p class="label">{{ __('app.profile.photo') }}</p>
                    @include('partials.image-cropper', [
                        'dataName' => 'avatar',
                        'removeName' => 'remove_avatar',
                        'allowRemove' => (bool) $avatarUrl,
                        'currentUrl' => $avatarUrl,
                        'initials' => $user->avatarInitials(),
                    ])
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('app.profile.details') }}</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="profile-name">{{ __('app.common.name') }}</label>
                            <input id="profile-name" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                        </div>
                        <div>
                            <label class="label" for="profile-email">{{ __('app.auth.email') }}</label>
                            <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">{{ __('app.profile.save_details') }}</button>
                </div>
            </form>

            <div class="border-t border-slate-100 pt-5">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('app.profile.security') }}</p>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="label" for="current-password">{{ __('app.profile.current_password') }}</label>
                        <input id="current-password" type="password" name="current_password" class="input" required autocomplete="current-password">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="new-password">{{ __('app.profile.new_password') }}</label>
                            <input id="new-password" type="password" name="password" class="input" required autocomplete="new-password">
                        </div>
                        <div>
                            <label class="label" for="confirm-password">{{ __('app.profile.confirm_password') }}</label>
                            <input id="confirm-password" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-secondary">{{ __('app.profile.save_password') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
