@php
    $brandName = \App\Support\Brand::name();
    $brandTagline = \App\Support\Brand::tagline();
    $brandLogo = \App\Support\Brand::logoUrl();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.auth.log_in_or_sign_up') }} · {{ $brandName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950">
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden overflow-hidden bg-slate-950 p-12 lg:flex lg:flex-col lg:justify-between">
        <div class="pointer-events-none absolute inset-0">
            <div class="animate-drift absolute -right-20 -top-24 h-[28rem] w-[28rem] rounded-full bg-indigo-600/30 blur-3xl"></div>
            <div class="animate-drift-alt absolute -bottom-32 -left-24 h-[30rem] w-[30rem] rounded-full bg-violet-600/20 blur-3xl"></div>
            <div class="animate-drift absolute left-1/3 top-1/3 h-72 w-72 rounded-full bg-sky-500/10 blur-3xl" style="animation-delay: -8s"></div>
            <div class="animate-spin-slow absolute -right-40 top-1/2 h-[38rem] w-[38rem] -translate-y-1/2 rounded-full border border-white/10">
                <span class="absolute left-1/2 top-0 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-400 shadow-[0_0_24px_6px_rgba(129,140,248,0.45)]"></span>
                <span class="absolute bottom-0 left-1/2 h-2 w-2 -translate-x-1/2 translate-y-1/2 rounded-full bg-sky-300/80"></span>
            </div>
            <div class="animate-spin-slow absolute -bottom-56 -left-40 h-[34rem] w-[34rem] rounded-full border border-white/5" style="animation-direction: reverse; animation-duration: 64s"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.035)_1px,transparent_1px)] bg-[size:64px_64px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
            <div class="animate-rise absolute left-[18%] top-[30%] h-1.5 w-1.5 rounded-full bg-white/60" style="animation-delay: -2s"></div>
            <div class="animate-rise absolute left-[72%] top-[62%] h-1 w-1 rounded-full bg-indigo-200/70" style="animation-delay: -4s"></div>
            <div class="animate-rise absolute left-[42%] top-[78%] h-2 w-2 rounded-full bg-violet-200/40" style="animation-delay: -6s"></div>
        </div>

        <div class="relative flex items-center gap-4">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="" class="h-14 w-14 rounded-2xl object-contain">
            @endif
            <div>
                <span class="block text-2xl font-extrabold tracking-tight text-white xl:text-3xl">{{ $brandName }}</span>
                <span class="mt-0.5 block text-base text-slate-400">{{ $brandTagline }}</span>
            </div>
        </div>

        <div class="relative flex flex-1 flex-col justify-center">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="" class="animate-float-logo mb-10 h-28 w-28 object-contain drop-shadow-[0_22px_55px_rgba(99,102,241,0.5)] xl:h-32 xl:w-32">
            @endif
            <h1 class="text-5xl font-extrabold leading-[1.05] tracking-tight text-white xl:text-6xl">
                {{ __('app.auth.log_in_or_sign_up') }}
            </h1>
            <div class="mt-10 flex items-center gap-2">
                <span class="h-1 w-10 rounded-full bg-indigo-400"></span>
                <span class="animate-rise h-1 w-4 rounded-full bg-white/30"></span>
                <span class="h-1 w-2 rounded-full bg-white/20"></span>
            </div>
        </div>

        <p class="relative text-xs text-slate-500">&copy; {{ date('Y') }} {{ $brandName }}</p>
    </div>

    <div class="relative flex items-center justify-center overflow-hidden bg-slate-100 p-5 sm:p-10">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-indigo-200/50 blur-3xl lg:hidden"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-violet-200/40 blur-3xl lg:hidden"></div>

        <div class="relative w-full max-w-md">
            <div class="mb-8 flex items-center gap-4 lg:hidden">
                @if ($brandLogo)
                    <img src="{{ $brandLogo }}" alt="" class="h-12 w-12 rounded-xl object-contain">
                @endif
                <div>
                    <span class="block text-xl font-extrabold tracking-tight text-slate-900">{{ $brandName }}</span>
                    <span class="mt-0.5 block text-base text-slate-500">{{ $brandTagline }}</span>
                </div>
            </div>

            <div class="mb-6 flex items-start justify-between gap-4">
                <h2 class="text-4xl font-extrabold tracking-tight text-slate-900">{{ __('app.auth.log_in_or_sign_up') }}</h2>
                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">
                    <a href="{{ route('locale.switch', 'id') }}" class="rounded-lg px-2 py-1 text-xs font-bold {{ app()->getLocale() === 'id' ? 'bg-indigo-600 text-white' : 'text-slate-500' }}">ID</a>
                    <a href="{{ route('locale.switch', 'en') }}" class="rounded-lg px-2 py-1 text-xs font-bold {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-500' }}">EN</a>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5">
                @csrf
                <div>
                    <label for="email" class="label">{{ __('app.auth.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="input" placeholder="you@company.com">
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="label">{{ __('app.auth.password') }}</label>
                        <a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">{{ __('app.auth.forgot_password') }}</a>
                    </div>
                    <input id="password" name="password" type="password" required class="input" placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    {{ __('app.auth.remember') }}
                </label>
                <button type="submit" class="btn-primary w-full">{{ __('app.auth.sign_in') }}</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
