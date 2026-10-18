<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.auth.reset_password') }} · {{ \App\Support\Brand::name() }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-5">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center gap-3">
            @if (\App\Support\Brand::logoUrl())
                <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" class="h-10 w-10 rounded-xl object-contain">
            @endif
            <div>
                <span class="block font-extrabold text-slate-900">{{ \App\Support\Brand::name() }}</span>
                <span class="block text-xs text-slate-500">{{ \App\Support\Brand::tagline() }}</span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="mb-5 text-xl font-extrabold text-slate-900">{{ __('app.auth.reset_password') }}</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label class="label" for="email">{{ __('app.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="input" required>
                </div>
                <div>
                    <label class="label" for="password">{{ __('app.auth.new_password') }}</label>
                    <input id="password" type="password" name="password" class="input" required>
                </div>
                <div>
                    <label class="label" for="password_confirmation">{{ __('app.auth.confirm_password') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input" required>
                </div>
                <button type="submit" class="btn-primary w-full">{{ __('app.auth.reset_password') }}</button>
            </form>

            <a href="{{ route('login') }}" class="mt-4 block text-center text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                {{ __('app.auth.back_to_login') }}
            </a>
        </div>
    </div>
</body>
</html>
