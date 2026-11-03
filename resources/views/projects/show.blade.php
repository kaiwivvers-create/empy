@extends('layouts.app')

@section('title', $project->name)

@section('content')
    @php
        $badge = match ($project->status) {
            'active' => 'badge-green',
            'on_hold' => 'badge-amber',
            default => 'badge-slate',
        };
    @endphp

    <div class="mb-6">
        <a href="{{ route('projects.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600">
            <svg class="h-4 w-4 rotate-180"><use href="#i-arrow"/></svg>
            {{ __('app.projects.title') }}
        </a>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6"><use href="#i-folder"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $project->name }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span class="{{ $badge }}">{{ $project->statusLabel() }}</span>
                        <span>{{ __('app.projects.owner') }}: {{ $project->owner?->name ?? '-' }}</span>
                    </div>
                </div>
            </div>

            @if ($canManage)
                <div class="flex items-center gap-2">
                    <a href="{{ route('projects.index', ['open' => 'edit-'.$project->id]) }}" class="btn-secondary">
                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                        {{ __('app.actions.edit') }}
                    </a>
                    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-secondary text-rose-600">
                            <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                            {{ __('app.actions.delete') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.projects.description') }}</h3>
                </div>
                <div class="card-body">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $project->description ?: '-' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.projects.team') }}</h3>
                    <span class="badge-slate">{{ $project->members->count() }} {{ __('app.projects.members') }}</span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($project->members as $member)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                @if ($member->photoUrl())
                                    <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ $member->initials() }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $member->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $member->position?->name ?? '-' }}@if ($member->department) · {{ $member->department->name }}@endif</p>
                            </div>
                            @if ($canManage)
                                <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost text-rose-600 hover:bg-rose-50">{{ __('app.projects.remove_member') }}</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-400">{{ __('app.projects.no_members') }}</p>
                    @endforelse
                </div>

                @if ($canManage)
                    <div class="border-t border-slate-100 px-5 py-4">
                        @if ($memberOptions->isNotEmpty())
                            <form method="POST" action="{{ route('projects.members.store', $project) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <div class="min-w-[14rem] flex-1">
                                    <label class="label" for="project-member">{{ __('app.projects.add_member') }}</label>
                                    <select id="project-member" name="employee_id" class="input" required>
                                        @foreach ($memberOptions as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }} — {{ $option->position?->name ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn-primary">{{ __('app.projects.add_member') }}</button>
                            </form>
                            <p class="mt-2 text-xs text-slate-400">{{ __('app.projects.member_hint') }}</p>
                        @else
                            <p class="text-xs text-slate-400">{{ __('app.projects.no_options') }}</p>
                        @endif
                        @error('members') <p class="form-error">{{ $message }}</p> @enderror
                        @error('employee_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.actions.detail') }}</h3>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400">{{ __('app.projects.status') }}</span>
                        <span class="{{ $badge }}">{{ $project->statusLabel() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400">{{ __('app.projects.owner') }}</span>
                        <span class="font-semibold text-slate-700">{{ $project->owner?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400">{{ __('app.projects.start_date') }}</span>
                        <span class="font-semibold text-slate-700">{{ $project->start_date?->format('d M Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400">{{ __('app.projects.due_date') }}</span>
                        <span class="font-semibold text-slate-700">{{ $project->due_date?->format('d M Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400">{{ __('app.projects.created_by') }}</span>
                        <span class="font-semibold text-slate-700">{{ $project->creator?->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
