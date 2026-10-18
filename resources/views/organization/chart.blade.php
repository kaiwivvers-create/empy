@extends('layouts.app')

@section('title', __('app.organization.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.organization.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.organization.subtitle') }}</p>
        </div>
        <span class="badge-indigo">{{ $totalEmployees }} {{ __('app.common.employee') }}</span>
    </div>

    @if ($roots->isNotEmpty())
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.organization.leadership') }}</h3>
            </div>
            <div class="flex flex-wrap gap-3 p-5">
                @foreach ($roots as $root)
                    <div class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                        <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-indigo-600 text-xs font-bold text-white">
                            @if ($root->photoUrl())
                                <img src="{{ $root->photoUrl() }}" alt="{{ $root->name }}" class="h-full w-full object-cover">
                            @else
                                {{ $root->initials() }}
                            @endif
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ $root->name }}</p>
                            <p class="text-xs text-slate-500">{{ $root->position?->name ?? '-' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        @forelse ($departments as $department)
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ $department->name }}</h3>
                        <p class="text-xs text-slate-400">{{ $department->code }} · {{ $department->employees->count() }} {{ __('app.organization.members') }}</p>
                    </div>
                    <span class="badge-slate">{{ $department->head?->name ?? __('app.organization.no_head') }}</span>
                </div>

                @if ($department->head)
                    <div class="mx-5 mt-4 flex items-center gap-3 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">
                            {{ $department->head->initials() }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $department->head->name }}</p>
                            <p class="text-xs text-amber-700">{{ __('app.organization.head') }}</p>
                        </div>
                    </div>
                @endif

                <div class="space-y-2 p-5">
                    @forelse ($department->employees as $member)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 px-3 py-2.5">
                            <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                @if ($member->photoUrl())
                                    <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ $member->initials() }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $member->name }}</p>
                                <p class="truncate text-xs text-slate-400">
                                    {{ $member->position?->name ?? '-' }}
                                    @if ($member->manager) · {{ __('app.common.manager') }}: {{ $member->manager->name }} @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('app.common.empty') }}</p>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="card"><div class="empty-state">{{ __('app.common.empty') }}</div></div>
        @endforelse
    </div>
@endsection
