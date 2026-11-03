@php
    $brandName = \App\Support\Brand::name();
    $brandTagline = \App\Support\Brand::tagline();
    $brandDescription = \App\Support\Brand::description();
    $brandLogo = \App\Support\Brand::logoUrl();
    $locale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $brandDescription }}">
    <title>{{ $brandName }} · {{ $brandTagline }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .js .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            transition-delay: var(--reveal-delay, 0ms);
            will-change: opacity, transform;
        }

        .js .reveal.is-visible {
            opacity: 1;
            transform: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .js .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <section class="relative isolate overflow-hidden bg-slate-950">
        <div class="pointer-events-none absolute inset-0">
            <div class="animate-drift absolute -right-24 -top-24 h-[28rem] w-[28rem] rounded-full bg-indigo-600/30 blur-3xl"></div>
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

        <div class="relative mx-auto flex min-h-[82vh] w-full flex-col px-5 py-6 sm:px-8 lg:px-12">
            <header class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
                    @if ($brandLogo)
                        <img src="{{ $brandLogo }}" alt="" class="h-10 w-10 shrink-0 rounded-xl object-contain">
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-sm font-extrabold text-indigo-200">
                            {{ mb_strtoupper(mb_substr($brandName, 0, 1)) }}
                        </span>
                    @endif
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-extrabold tracking-tight text-white">{{ $brandName }}</span>
                        <span class="block truncate text-[11px] text-slate-400">{{ $brandTagline }}</span>
                    </span>
                </a>

                <div class="flex shrink-0 items-center gap-2">
                    <div class="flex items-center gap-1 rounded-xl border border-white/10 bg-white/5 p-1" title="{{ __('app.language') }}">
                        @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $text)
                            <a href="{{ route('locale.switch', $code) }}"
                               class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $locale === $code ? 'bg-white text-indigo-700' : 'text-slate-400 hover:text-white' }}">
                                {{ $text }}
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                        {{ __('app.landing.sign_in') }}
                    </a>
                </div>
            </header>

            <div class="flex flex-1 flex-col items-center justify-center py-16 text-center sm:py-20">
                @if ($brandLogo)
                    <img src="{{ $brandLogo }}" alt="" class="animate-float-logo mb-8 h-20 w-20 object-contain drop-shadow-[0_22px_55px_rgba(99,102,241,0.5)] sm:h-24 sm:w-24">
                @endif

                <p class="text-[11px] font-bold uppercase tracking-[0.4em] text-indigo-300">{{ $brandTagline }}</p>
                <h1 class="mt-5 max-w-4xl text-5xl font-extrabold leading-[1.02] tracking-tight text-white sm:text-6xl lg:text-7xl">
                    {{ $brandName }}
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
                    {{ $brandDescription }}
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-500 px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-indigo-950/50 transition hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2 focus:ring-offset-slate-950">
                        {{ __('app.landing.cta') }}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <p class="mx-auto mt-4 max-w-md text-xs leading-relaxed text-slate-500">{{ __('app.landing.cta_hint') }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 pt-14 sm:px-8 sm:pt-20">
        <div class="reveal max-w-2xl" data-reveal>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-indigo-600">{{ __('app.landing.features_eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('app.landing.features_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-500">{{ __('app.landing.features_subtitle') }}</p>
        </div>

        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            <div class="reveal rounded-2xl border border-slate-200/80 bg-white p-6 shadow-lg shadow-slate-900/5" data-reveal>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg>
                </span>
                <h3 class="mt-4 text-sm font-bold text-slate-900">{{ __('app.landing.feature_1_title') }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ __('app.landing.feature_1_body') }}</p>
            </div>

            <div class="reveal rounded-2xl border border-slate-200/80 bg-white p-6 shadow-lg shadow-slate-900/5" style="--reveal-delay: 80ms" data-reveal>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 9h2a2 2 0 0 1 2 2v10"/><path d="M9 7h2M9 11h2M9 15h2"/></svg>
                </span>
                <h3 class="mt-4 text-sm font-bold text-slate-900">{{ __('app.landing.feature_2_title') }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ __('app.landing.feature_2_body') }}</p>
            </div>

            <div class="reveal rounded-2xl border border-slate-200/80 bg-white p-6 shadow-lg shadow-slate-900/5" style="--reveal-delay: 160ms" data-reveal>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6" rx="1"/><rect x="13" y="8" width="3" height="10" rx="1"/><rect x="19" y="5" width="3" height="13" rx="1"/></svg>
                </span>
                <h3 class="mt-4 text-sm font-bold text-slate-900">{{ __('app.landing.feature_3_title') }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ __('app.landing.feature_3_body') }}</p>
            </div>
        </div>
    </section>

    <section class="mt-16 border-y border-slate-200 bg-white py-16 sm:mt-20 sm:py-20">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="reveal max-w-2xl" data-reveal>
                <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-indigo-600">{{ __('app.landing.roles_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('app.landing.roles_title') }}</h2>
                <p class="mt-3 text-base leading-relaxed text-slate-500">{{ __('app.landing.roles_subtitle') }}</p>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-3">
                <div class="reveal rounded-2xl border border-slate-200 bg-slate-50/60 p-6" data-reveal>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/25">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">{{ __('app.landing.role_admin_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('app.landing.role_admin_body') }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        @foreach ([1, 2, 3] as $point)
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>
                                <span>{{ __('app.landing.role_admin_point_'.$point) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="reveal rounded-2xl border border-slate-200 bg-slate-50/60 p-6" style="--reveal-delay: 80ms" data-reveal>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 text-white shadow-lg shadow-violet-600/25">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">{{ __('app.landing.role_manager_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('app.landing.role_manager_body') }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        @foreach ([1, 2, 3] as $point)
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>
                                <span>{{ __('app.landing.role_manager_point_'.$point) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="reveal rounded-2xl border border-slate-200 bg-slate-50/60 p-6" style="--reveal-delay: 160ms" data-reveal>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-600/25">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">{{ __('app.landing.role_employee_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('app.landing.role_employee_body') }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        @foreach ([1, 2, 3] as $point)
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>
                                <span>{{ __('app.landing.role_employee_point_'.$point) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-16 sm:px-8 sm:py-20">
        <div class="reveal max-w-2xl" data-reveal>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-indigo-600">{{ __('app.landing.workflow_eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('app.landing.workflow_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-500">{{ __('app.landing.workflow_subtitle') }}</p>
        </div>

        <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @for ($step = 1; $step <= 4; $step++)
                <li class="reveal flex flex-col rounded-2xl border border-slate-200 bg-white p-6" style="--reveal-delay: {{ ($step - 1) * 80 }}ms" data-reveal>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-xs font-extrabold text-white">
                        0{{ $step }}
                    </span>
                    <h3 class="mt-4 text-sm font-bold text-slate-900">{{ __('app.landing.step_'.$step.'_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('app.landing.step_'.$step.'_body') }}</p>
                </li>
            @endfor
        </ol>
    </section>

    <section class="mx-auto max-w-6xl px-5 pb-16 sm:px-8 sm:pb-20">
        <div class="reveal relative isolate overflow-hidden rounded-3xl bg-slate-950 px-6 py-14 text-center sm:px-12 sm:py-16" data-reveal>
            <div class="pointer-events-none absolute inset-0">
                <div class="animate-drift absolute -right-20 -top-24 h-72 w-72 rounded-full bg-indigo-600/30 blur-3xl"></div>
                <div class="animate-drift-alt absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-violet-600/20 blur-3xl"></div>
                <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)] bg-[size:48px_48px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_80%)]"></div>
            </div>

            <div class="relative">
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('app.landing.closing_title') }}</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-400">{{ __('app.landing.closing_body') }}</p>
                <a href="{{ route('login') }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-500 px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-indigo-950/50 transition hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2 focus:ring-offset-slate-950">
                    {{ __('app.landing.cta') }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </section>

    <footer class="border-t border-slate-200 py-8 text-center text-[11px] text-slate-400">
        <p>{{ __('app.landing.footer') }}</p>
        <p class="mt-1">&copy; {{ date('Y') }} {{ $brandName }}</p>
    </footer>

    <script>
        (() => {
            const items = document.querySelectorAll('[data-reveal]');

            if (!('IntersectionObserver' in window)) {
                items.forEach((item) => item.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -48px 0px' });

            items.forEach((item) => observer.observe(item));
        })();
    </script>
</body>
</html>
