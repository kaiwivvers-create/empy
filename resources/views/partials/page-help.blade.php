@php
    $helpPage = match (true) {
        request()->routeIs('dashboard') => 'dashboard',
        request()->routeIs('employees.import', 'employees.import.*') => 'import',
        request()->routeIs('employees.goals.*') => 'goals',
        request()->routeIs('employees.show') => 'employee_detail',
        request()->routeIs('employees.*') => 'employees',
        request()->routeIs('evaluations.team') => 'team',
        request()->routeIs('evaluations.create', 'evaluations.edit') => 'evaluation_form',
        request()->routeIs('evaluations.show') => 'evaluation_detail',
        request()->routeIs('evaluations.*') => 'evaluations',
        request()->routeIs('feedback.*') => 'feedback',
        request()->routeIs('reports.progress') => 'progress',
        request()->routeIs('reports.calibration') => 'calibration',
        request()->routeIs('reports.*') => 'reports',
        request()->routeIs('organization.*') => 'organization',
        request()->routeIs('departments.*') => 'departments',
        request()->routeIs('positions.*') => 'positions',
        request()->routeIs('periods.*') => 'periods',
        request()->routeIs('criteria.*') => 'criteria',
        request()->routeIs('users.*') => 'users',
        request()->routeIs('activity.*') => 'activity',
        request()->routeIs('roles.*') => 'roles',
        request()->routeIs('access.*') => 'access',
        request()->routeIs('branding.*') => 'branding',
        request()->routeIs('database.*') => 'database',
        request()->routeIs('projects.*') => 'projects',
        request()->routeIs('password-requests.*') => 'password_requests',
        default => null,
    };

    $helpKey = 'app.help.'.($helpPage ?? 'fallback');
    $helpSteps = trans($helpKey.'.steps');
@endphp

<div id="page-help-modal"
     class="modal"
     data-modal
     data-open="false"
     aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="modal-panel max-w-lg" role="dialog" aria-modal="true" aria-labelledby="page-help-modal-title">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5"><use href="#i-help"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 id="page-help-modal-title" class="text-lg font-extrabold text-slate-900">{{ __('app.help.title') }}</h2>
                    <p class="mt-0.5 truncate text-sm font-semibold text-indigo-600">{{ __($helpKey.'.title') }}</p>
                </div>
            </div>
            <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-modal-close aria-label="{{ __('app.actions.close') }}">
                <svg class="h-5 w-5"><use href="#i-x"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
            <p class="text-sm leading-relaxed text-slate-600">{{ __($helpKey.'.summary') }}</p>

            @if (is_array($helpSteps) && count($helpSteps) > 0)
                <ol class="space-y-2.5">
                    @foreach ($helpSteps as $step)
                        <li class="flex gap-3 text-sm leading-relaxed text-slate-600">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-[11px] font-bold text-indigo-600">{{ $loop->iteration }}</span>
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <p class="text-xs text-slate-400">{{ __('app.help.subtitle') }}</p>
        </div>

        <div class="flex justify-end border-t border-slate-100 px-5 py-4">
            <button type="button" class="btn-secondary" data-modal-close>{{ __('app.help.close') }}</button>
        </div>
    </div>
</div>
