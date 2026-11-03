@props([
    'code',
    'title',
    'message',
    'accent' => 'violet',
    'actionHref' => null,
    'actionLabel' => null,
    'secondaryHref' => null,
    'secondaryLabel' => null,
    'hint' => null,
])

@php
    $themes = [
        'violet' => [
            'text' => 'text-violet-300',
            'chip' => 'bg-violet-400/10 text-violet-200 ring-violet-400/30',
            'glow' => 'bg-violet-500/20',
            'button' => 'bg-violet-400 hover:bg-violet-300 focus:ring-violet-300',
        ],
        'amber' => [
            'text' => 'text-amber-300',
            'chip' => 'bg-amber-400/10 text-amber-200 ring-amber-400/30',
            'glow' => 'bg-amber-500/20',
            'button' => 'bg-amber-400 hover:bg-amber-300 focus:ring-amber-300',
        ],
        'sky' => [
            'text' => 'text-sky-300',
            'chip' => 'bg-sky-400/10 text-sky-200 ring-sky-400/30',
            'glow' => 'bg-sky-500/20',
            'button' => 'bg-sky-400 hover:bg-sky-300 focus:ring-sky-300',
        ],
        'rose' => [
            'text' => 'text-rose-300',
            'chip' => 'bg-rose-400/10 text-rose-200 ring-rose-400/30',
            'glow' => 'bg-rose-500/20',
            'button' => 'bg-rose-400 hover:bg-rose-300 focus:ring-rose-300',
        ],
        'indigo' => [
            'text' => 'text-indigo-300',
            'chip' => 'bg-indigo-400/10 text-indigo-200 ring-indigo-400/30',
            'glow' => 'bg-indigo-500/25',
            'button' => 'bg-indigo-400 hover:bg-indigo-300 focus:ring-indigo-300',
        ],
        'teal' => [
            'text' => 'text-teal-300',
            'chip' => 'bg-teal-400/10 text-teal-200 ring-teal-400/30',
            'glow' => 'bg-teal-500/20',
            'button' => 'bg-teal-400 hover:bg-teal-300 focus:ring-teal-300',
        ],
        'orange' => [
            'text' => 'text-orange-300',
            'chip' => 'bg-orange-400/10 text-orange-200 ring-orange-400/30',
            'glow' => 'bg-orange-500/20',
            'button' => 'bg-orange-400 hover:bg-orange-300 focus:ring-orange-300',
        ],
        'emerald' => [
            'text' => 'text-emerald-300',
            'chip' => 'bg-emerald-400/10 text-emerald-200 ring-emerald-400/30',
            'glow' => 'bg-emerald-500/20',
            'button' => 'bg-emerald-400 hover:bg-emerald-300 focus:ring-emerald-300',
        ],
    ];

    $theme = $themes[$accent] ?? $themes['violet'];
    $actionHref = $actionHref ?? url('/');
    $actionLabel = $actionLabel ?? __('app.errors.back_home');

    // The brand lives in the database; on a database failure fall back to config
    // so the error page itself never throws.
    try {
        $brandName = \App\Support\Brand::name();
        $brandTagline = \App\Support\Brand::tagline();
        $brandLogo = \App\Support\Brand::logoUrl();
    } catch (\Throwable) {
        $brandName = config('app.name', 'Empy');
        $brandTagline = __('app.brand_sub');
        $brandLogo = null;
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $title }} · {{ $brandName }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
    <div class="pointer-events-none absolute inset-0">
        <div class="animate-drift absolute -right-32 -top-40 h-96 w-96 rounded-full {{ $theme['glow'] }} blur-3xl"></div>
        <div class="animate-drift-alt absolute -bottom-40 -left-32 h-[28rem] w-[28rem] rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:64px_64px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
        <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 select-none text-[18rem] font-extrabold leading-none text-white/[0.03] sm:text-[26rem]">{{ $code }}</span>
    </div>

    <div class="relative flex min-h-screen flex-col px-5 py-6 sm:px-10">
        <header class="flex items-center gap-3">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="" class="h-10 w-10 rounded-xl object-contain">
            @endif
            <div class="min-w-0">
                <p class="truncate text-sm font-extrabold tracking-tight">{{ $brandName }}</p>
                <p class="truncate text-[11px] text-slate-500">{{ $brandTagline }}</p>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center py-10">
            <div class="w-full max-w-xl text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl ring-1 {{ $theme['chip'] }}">
                    {{ $slot }}
                </div>

                <p class="mt-7 text-[11px] font-bold uppercase tracking-[0.35em] {{ $theme['text'] }}">
                    {{ __('app.errors.code_label', ['code' => $code]) }}
                </p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $title }}</h1>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400">{{ $message }}</p>

                @if ($hint)
                    <p class="mx-auto mt-4 max-w-md rounded-xl border border-white/5 bg-white/[0.03] px-4 py-2.5 text-xs leading-relaxed text-slate-400">{{ $hint }}</p>
                @endif

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ $actionHref }}" class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-950 shadow-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950 {{ $theme['button'] }}">
                        {{ $actionLabel }}
                    </a>
                    @if ($secondaryHref)
                        <a href="{{ $secondaryHref }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                            {{ $secondaryLabel }}
                        </a>
                    @endif
                </div>
            </div>
        </main>

        <footer class="text-center text-[11px] text-slate-600">
            &copy; {{ date('Y') }} {{ $brandName }}
        </footer>
    </div>
</body>
</html>
