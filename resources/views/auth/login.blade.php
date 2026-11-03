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
                <div class="min-w-0">
                    <h2 class="text-4xl font-extrabold tracking-tight text-slate-900">{{ __('app.auth.log_in_or_sign_up') }}</h2>
                    <p class="mt-2 max-w-sm text-base text-slate-500">{{ __('app.auth.sign_in_subtitle') }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">
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

                <div data-captcha>
                    @if ($turnstileEnabled)
                        <div data-turnstile-wrap>
                            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light"></div>
                        </div>
                    @endif

                    <div id="captcha-local" data-local-captcha class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold text-slate-500">{{ __('app.auth.captcha') }}</p>
                            <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-0.5">
                                <button type="button" data-captcha-tab="math" class="rounded-md bg-indigo-600 px-2.5 py-1 text-[11px] font-bold text-white transition">{{ __('app.auth.captcha_math') }}</button>
                                <button type="button" data-captcha-tab="physics" class="rounded-md px-2.5 py-1 text-[11px] font-bold text-slate-500 transition">{{ __('app.auth.captcha_physics') }}</button>
                            </div>
                        </div>

                        <input type="hidden" name="captcha_type" value="math" data-captcha-type>

                        <div id="captcha-math-panel" data-captcha-panel="math">
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <p data-captcha-question class="text-lg font-extrabold tracking-wide text-slate-800">{{ $captchas['math']['question'] }}</p>
                                <div class="flex items-center gap-2">
                                    <input name="captcha_answer" data-captcha-answer inputmode="numeric" autocomplete="off"
                                           class="input !w-20 !py-2 text-center" placeholder="?"
                                           aria-label="{{ __('app.auth.captcha') }}">
                                    <button type="button" data-captcha-refresh="{{ route('captcha.math') }}" class="rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-indigo-600" title="{{ __('app.auth.captcha_refresh') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="captcha_token" value="{{ $captchas['math']['token'] }}" data-captcha-token>
                        </div>

                        <div id="captcha-physics-panel" data-captcha-panel="physics" class="hidden">
                            <div class="mt-2 flex items-start justify-between gap-3">
                                <p data-captcha-topic class="text-[10px] font-bold uppercase tracking-[0.14em] text-indigo-500">{{ $captchas['physics']['topic'] }}</p>
                                <button type="button" data-captcha-refresh="{{ route('captcha.physics') }}" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-indigo-600" title="{{ __('app.auth.captcha_refresh') }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                </button>
                            </div>
                            <p data-captcha-question class="mt-0.5 text-sm font-semibold leading-snug text-slate-800">{{ $captchas['physics']['question'] }}</p>
                            <div data-captcha-options class="mt-2 space-y-1.5">
                                @foreach ($captchas['physics']['options'] as $index => $option)
                                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm text-slate-700 transition hover:border-indigo-300">
                                        <input type="radio" name="captcha_answer" value="{{ $index }}" class="mt-0.5 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" disabled>
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <input type="hidden" name="captcha_token" value="{{ $captchas['physics']['token'] }}" data-captcha-token disabled>
                        </div>

                        <p class="mt-2 text-[11px] leading-relaxed text-slate-400">{{ __('app.auth.captcha_hint') }}</p>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full">{{ __('app.auth.sign_in') }}</button>
            </form>
        </div>
    </div>
</div>
<script>
    (() => {
        const siteKey = @json(config('services.turnstile.site_key'));
        const localCaptcha = document.querySelector('[data-local-captcha]');
        const turnstileWrap = document.querySelector('[data-turnstile-wrap]');
        const typeInput = document.querySelector('[data-captcha-type]');
        const tabs = [...document.querySelectorAll('[data-captcha-tab]')];
        const panels = [...document.querySelectorAll('[data-captcha-panel]')];

        if (!localCaptcha || !typeInput) {
            return;
        }

        let activeType = 'math';

        const setPanelState = (panel, enabled) => {
            panel.classList.toggle('hidden', !enabled);

            panel.querySelectorAll('input').forEach((input) => {
                input.disabled = !enabled;
            });
        };

        const selectTab = (type) => {
            activeType = type;
            typeInput.value = type;

            tabs.forEach((tab) => {
                const selected = tab.dataset.captchaTab === type;
                tab.classList.toggle('bg-indigo-600', selected);
                tab.classList.toggle('text-white', selected);
                tab.classList.toggle('text-slate-500', !selected);
            });

            panels.forEach((panel) => setPanelState(panel, panel.dataset.captchaPanel === type));
        };

        const refreshChallenge = async (type) => {
            const panel = document.querySelector('[data-captcha-panel="' + type + '"]');
            const button = panel?.querySelector('[data-captcha-refresh]');

            if (!panel || !button) {
                return;
            }

            try {
                const response = await fetch(button.dataset.captchaRefresh, { headers: { Accept: 'application/json' } });
                const data = await response.json();

                panel.querySelector('[data-captcha-token]').value = data.token;

                if (type === 'math') {
                    panel.querySelector('[data-captcha-question]').textContent = data.question;
                    panel.querySelector('[data-captcha-answer]').value = '';
                    return;
                }

                panel.querySelector('[data-captcha-topic]').textContent = data.topic;
                panel.querySelector('[data-captcha-question]').textContent = data.question;

                const options = panel.querySelector('[data-captcha-options]');
                options.innerHTML = '';

                data.options.forEach((option, index) => {
                    const label = document.createElement('label');
                    label.className = 'flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm text-slate-700 transition hover:border-indigo-300';

                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.name = 'captcha_answer';
                    input.value = String(index);
                    input.className = 'mt-0.5 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500';
                    input.disabled = activeType !== 'physics';

                    const text = document.createElement('span');
                    text.textContent = option;

                    label.append(input, text);
                    options.append(label);
                });
            } catch (error) {
                // Offline and the challenge could not refresh; keep the current one.
            }
        };

        const useLocalCaptcha = () => {
            turnstileWrap?.classList.add('hidden');
            localCaptcha.classList.remove('hidden');
            selectTab(activeType);
        };

        const useTurnstile = () => {
            if (!siteKey || !turnstileWrap) {
                useLocalCaptcha();
                return;
            }

            localCaptcha.classList.add('hidden');
            turnstileWrap.classList.remove('hidden');

            localCaptcha.querySelectorAll('input').forEach((input) => {
                input.disabled = true;
            });
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => selectTab(tab.dataset.captchaTab));
        });

        localCaptcha.querySelectorAll('[data-captcha-refresh]').forEach((button) => {
            button.addEventListener('click', () => {
                refreshChallenge(button.closest('[data-captcha-panel]').dataset.captchaPanel);
            });
        });

        window.addEventListener('offline', useLocalCaptcha);

        selectTab('math');

        if (!siteKey || !navigator.onLine) {
            useLocalCaptcha();
        } else {
            useTurnstile();
        }
    })();
</script>

@if ($turnstileEnabled)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer
            onerror="document.querySelector('[data-turnstile-wrap]')?.classList.add('hidden'); document.getElementById('captcha-local')?.classList.remove('hidden'); document.getElementById('captcha-math-panel')?.querySelectorAll('input').forEach(function (input) { input.disabled = false; }); document.querySelector('[data-captcha-type]')?.removeAttribute('disabled');"></script>
@endif
</body>
</html>
