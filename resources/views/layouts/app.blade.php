<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.dashboard.title')) · {{ \App\Support\Brand::name() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $locale = app()->getLocale();
    $pendingFeedback = \App\Models\PeerFeedback::where('reviewer_id', $user->id)->whereNull('submitted_at')->count();
    $pendingPasswordRequests = $user->isAdmin() ? \App\Models\PasswordResetRequest::query()->pending()->count() : 0;
    $can = fn (string $page) => \App\Support\PageAccess::allows($user, $page);
    $brandName = \App\Support\Brand::name();
    $brandTagline = \App\Support\Brand::tagline();
    $brandLogo = \App\Support\Brand::logoUrl();
@endphp

<svg xmlns="http://www.w3.org/2000/svg" class="hidden">
    <symbol id="i-grid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
    <symbol id="i-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 9h2a2 2 0 0 1 2 2v10"/><path d="M9 7h2M9 11h2M9 15h2"/></symbol>
    <symbol id="i-briefcase" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></symbol>
    <symbol id="i-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></symbol>
    <symbol id="i-list" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6" rx="1"/><rect x="13" y="8" width="3" height="10" rx="1"/><rect x="19" y="5" width="3" height="13" rx="1"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol>
    <symbol id="i-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></symbol>
    <symbol id="i-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></symbol>
    <symbol id="i-pencil" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></symbol>
    <symbol id="i-star" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2Z"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></symbol>
    <symbol id="i-palette" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="8" cy="10" r="1"/><circle cx="12" cy="8" r="1"/><circle cx="16" cy="10" r="1"/><path d="M12 21a3 3 0 0 0 0-6h-1a2 2 0 0 1 0-4h4a3 3 0 0 0 3-3"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></symbol>
    <symbol id="i-help" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4"/><path d="M12 17h.01"/></symbol>
    <symbol id="i-database" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v7c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12v7c0 1.66 4.03 3 9 3s9-1.34 9-3v-7"/></symbol>
    <symbol id="i-folder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></symbol>
    <symbol id="i-print" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></symbol>
</svg>

<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform duration-300 lg:translate-x-0 print:hidden">
    <div class="flex items-center justify-between px-5 py-5">
        <a href="{{ route(\App\Support\PageAccess::homeRoute($user)) }}" class="flex min-w-0 items-center gap-3">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="" class="h-10 w-10 shrink-0 rounded-xl object-contain">
            @endif
            <span class="min-w-0">
                <span class="block truncate text-base font-extrabold tracking-tight text-white">{{ $brandName }}</span>
                <span class="block truncate text-[11px] font-medium text-slate-400">{{ $brandTagline }}</span>
            </span>
        </a>
        <button id="sidebar-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close">
            <svg class="h-5 w-5"><use href="#i-x"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4">
        <p class="nav-label">{{ __('app.nav.main') }}</p>

        @if ($can('dashboard'))
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-grid"/></svg>
                {{ __('app.nav.dashboard') }}
            </a>
        @endif

        @if ($can('employees'))
            @if ($user->isEmployee() && $user->employee)
                <a href="{{ route('employees.show', $user->employee) }}" class="nav-link {{ request()->routeIs('employees.show') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-users"/></svg>
                    {{ __('app.employees.detail_title') }}
                </a>
            @else
                <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-users"/></svg>
                    {{ $user->isManager() ? __('app.employees.team') : __('app.nav.employees') }}
                </a>
            @endif
        @endif

        @if ($can('evaluations'))
            <a href="{{ route('evaluations.index') }}" class="nav-link {{ request()->routeIs('evaluations.index') || request()->routeIs('evaluations.show') || request()->routeIs('evaluations.edit') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-clipboard"/></svg>
                {{ __('app.nav.evaluations') }}
            </a>
            @unless ($user->isEmployee())
                <a href="{{ route('evaluations.team') }}" class="nav-link {{ request()->routeIs('evaluations.team') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-users"/></svg>
                    {{ __('app.team.title') }}
                </a>
            @endunless
        @endif

        @if ($can('feedback'))
            <a href="{{ route('feedback.index') }}" class="nav-link {{ request()->routeIs('feedback.*') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-star"/></svg>
                <span class="flex-1">{{ __('app.feedback.title') }}</span>
                @if ($pendingFeedback > 0)
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">{{ $pendingFeedback }}</span>
                @endif
            </a>
        @endif

        @if (! $user->isEmployee() && $can('reports'))
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.index') || request()->routeIs('reports.calibration') || request()->routeIs('reports.pdf') || request()->routeIs('reports.excel') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-chart"/></svg>
                {{ __('app.nav.reports') }}
            </a>
            <a href="{{ route('reports.progress') }}" class="nav-link {{ request()->routeIs('reports.progress') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-list"/></svg>
                {{ __('app.progress.title') }}
            </a>
        @endif

        @if (! $user->isEmployee() && $can('organization'))
            <a href="{{ route('organization.chart') }}" class="nav-link {{ request()->routeIs('organization.*') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-building"/></svg>
                {{ __('app.organization.title') }}
            </a>
        @endif

        @if ($can('departments') || $can('positions'))
            <p class="nav-label">{{ __('app.nav.organization') }}</p>
            @if ($can('departments'))
                <a href="{{ route('departments.index') }}" class="nav-link {{ request()->routeIs('departments.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-building"/></svg>
                    {{ __('app.nav.departments') }}
                </a>
            @endif
            @if ($can('positions'))
                <a href="{{ route('positions.index') }}" class="nav-link {{ request()->routeIs('positions.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-briefcase"/></svg>
                    {{ __('app.nav.positions') }}
                </a>
            @endif
        @endif

        @if ($can('periods') || $can('criteria'))
            <p class="nav-label">{{ __('app.nav.evaluation_setup') }}</p>
            @if ($can('periods'))
                <a href="{{ route('periods.index') }}" class="nav-link {{ request()->routeIs('periods.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-calendar"/></svg>
                    {{ __('app.nav.periods') }}
                </a>
            @endif
            @if ($can('criteria'))
                <a href="{{ route('criteria.index') }}" class="nav-link {{ request()->routeIs('criteria.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-list"/></svg>
                    {{ __('app.nav.criteria') }}
                </a>
            @endif
        @endif

        @if ($can('users') || $can('activity') || $user->isAdmin())
            <p class="nav-label">{{ __('app.nav.administration') }}</p>
            @if ($can('users'))
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-users"/></svg>
                    {{ __('app.users.title') }}
                </a>
            @endif
            @if ($user->isAdmin())
                <a href="{{ route('password-requests.index') }}" class="nav-link {{ request()->routeIs('password-requests.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-shield"/></svg>
                    <span class="flex-1">{{ __('app.password_requests.title') }}</span>
                    @if ($pendingPasswordRequests > 0)
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $pendingPasswordRequests }}</span>
                    @endif
                </a>
            @endif
            @if ($can('activity'))
                <a href="{{ route('activity.index') }}" class="nav-link {{ request()->routeIs('activity.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-list"/></svg>
                    {{ __('app.activity.title') }}
                </a>
            @endif
            @if ($user->isSuperAdmin())
                <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}">
                    <svg class="h-5 w-5"><use href="#i-shield"/></svg>
                    {{ __('app.roles_page.title') }}
                </a>
            @endif
        @endif

        @if ($user->isSuperAdmin())
            <p class="nav-label">{{ __('app.nav.system') }}</p>
            <a href="{{ route('access.index') }}" class="nav-link {{ request()->routeIs('access.*') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-shield"/></svg>
                {{ __('app.access.title') }}
            </a>
            <a href="{{ route('branding.edit') }}" class="nav-link {{ request()->routeIs('branding.*') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-palette"/></svg>
                {{ __('app.branding.title') }}
            </a>
            <a href="{{ route('database.index') }}" class="nav-link {{ request()->routeIs('database.*') ? 'nav-link-active' : '' }}">
                <svg class="h-5 w-5"><use href="#i-database"/></svg>
                {{ __('app.database.title') }}
            </a>
        @endif
    </nav>

    <div class="border-t border-white/10 p-3">
        <button type="button" data-modal-open="profile-modal" class="flex w-full items-center gap-3 rounded-xl bg-white/5 px-3 py-3 text-left transition hover:bg-white/10">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-500/20 text-sm font-bold text-indigo-300">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                @else
                    {{ $user->avatarInitials() }}
                @endif
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-white">{{ $user->name }}</span>
                <span class="block text-[11px] text-slate-400">{{ $user->roleLabel() }}</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-500"><use href="#i-chevron"/></svg>
        </button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link mt-1 w-full text-left">
                <svg class="h-5 w-5"><use href="#i-logout"/></svg>
                {{ __('app.auth.logout') }}
            </button>
        </form>
    </div>
</aside>

<div class="lg:pl-64 print:pl-0">
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur print:hidden">
        <div class="flex h-16 items-center justify-between gap-4 px-3 sm:px-4 lg:px-5">
            <div class="flex items-center gap-3">
                <button id="sidebar-open" class="rounded-lg border border-slate-200 p-2 text-slate-600 lg:hidden" aria-label="Open menu">
                    <svg class="h-5 w-5"><use href="#i-menu"/></svg>
                </button>
                <h1 class="text-lg font-bold text-slate-900">@yield('title', __('app.dashboard.title'))</h1>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
                        data-modal-open="page-help-modal"
                        title="{{ __('app.help.button') }}"
                        aria-label="{{ __('app.help.button') }}">
                    <svg class="h-4 w-4"><use href="#i-help"/></svg>
                </button>
                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1" title="{{ __('app.language') }}">
                    @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $text)
                        <a href="{{ route('locale.switch', $code) }}"
                           class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ $locale === $code ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            {{ $text }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </header>

    <main class="w-full px-3 py-5 sm:px-4 lg:px-5 lg:py-6">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

@include('partials.profile-modal')
@include('partials.page-help')

@stack('scripts')
</body>
</html>
