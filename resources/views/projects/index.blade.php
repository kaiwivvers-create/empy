@extends('layouts.app')

@section('title', __('app.projects.title'))

@section('content')
    @php
        $hasFilters = collect($filters ?? [])->contains(fn ($value) => filled($value));
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.projects.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.projects.subtitle') }}</p>
        </div>
        @if ($canCreate)
            <button type="button" class="btn-primary" data-modal-open="project-create">
                <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                {{ __('app.projects.add') }}
            </button>
        @endif
    </div>

    <div class="card mb-5">
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('app.common.search_placeholder') }}" class="input pl-9">
                    </div>
                </div>
                <div class="flex gap-2">
                    <select name="status" class="input" data-searchable>
                        <option value="">{{ __('app.common.all') }} · {{ __('app.projects.status') }}</option>
                        @foreach (\App\Models\Project::statuses() as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __('app.projects.statuses.'.$status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary shrink-0">{{ __('app.actions.filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    @if ($projects->isEmpty())
        <div class="card">
            <div class="empty-state flex flex-col items-center gap-2">
                <svg class="h-8 w-8 text-slate-300"><use href="#i-folder"/></svg>
                <p>{{ $hasFilters ? __('app.common.no_results') : __('app.projects.empty') }}</p>
                <div class="mt-1 flex flex-wrap items-center justify-center gap-2">
                    @if ($hasFilters)
                        <a href="{{ route('projects.index') }}" class="btn-secondary">{{ __('app.actions.reset') }}</a>
                    @endif
                    @if ($canCreate)
                        <button type="button" class="btn-primary" data-modal-open="project-create">
                            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                            {{ __('app.projects.add') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($projects as $project)
                @php
                    $badge = match ($project->status) {
                        'active' => 'badge-green',
                        'on_hold' => 'badge-amber',
                        default => 'badge-slate',
                    };
                @endphp
                <div class="card flex flex-col">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <svg class="h-5 w-5"><use href="#i-folder"/></svg>
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('projects.show', $project) }}" class="block truncate font-bold text-slate-900 hover:text-indigo-600">{{ $project->name }}</a>
                                <p class="truncate text-xs text-slate-400">{{ __('app.projects.owner') }}: {{ $project->owner?->name ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="{{ $badge }} shrink-0">{{ $project->statusLabel() }}</span>
                    </div>

                    <div class="flex-1 space-y-3 p-5">
                        <p class="line-clamp-2 text-sm text-slate-600">{{ $project->description ?: '-' }}</p>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                            <span>{{ $project->members_count }} {{ __('app.projects.members') }}</span>
                            @if ($project->start_date)
                                <span>{{ $project->start_date->format('d M Y') }}</span>
                            @endif
                            @if ($project->due_date)
                                <span>→ {{ $project->due_date->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-5 py-3">
                        <div class="flex -space-x-2">
                            @foreach ($project->members->take(4) as $member)
                                <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-slate-100 text-[10px] font-bold text-slate-600" title="{{ $member->name }}">
                                    {{ $member->initials() }}
                                </span>
                            @endforeach
                            @if ($project->members_count > 4)
                                <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-slate-800 text-[10px] font-bold text-white">
                                    +{{ $project->members_count - 4 }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            <a href="{{ route('projects.show', $project) }}" class="btn-ghost">{{ __('app.actions.detail') }}</a>
                            @if ($project->canBeManagedBy(auth()->user()))
                                <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                        data-modal-open="project-edit-{{ $project->id }}">
                                    <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                </button>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="{{ __('app.actions.delete') }}">
                                        <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($projects->hasPages())
            <div class="mt-5">{{ $projects->links() }}</div>
        @endif
    @endif

    @if ($canCreate)
        <x-modal-form id="project-create" :title="__('app.projects.create_title')" :subtitle="__('app.projects.subtitle')" :action="route('projects.store')" wide :open="$openModal === 'project-create'">
            @include('projects._fields', ['project' => new \App\Models\Project, 'prefix' => 'project-create'])

            @if ($memberOptions->isNotEmpty())
                <div>
                    <label class="label">{{ __('app.projects.members') }}</label>
                    <p class="mb-2 text-xs text-slate-500">{{ __('app.projects.member_hint') }}</p>
                    <div class="relative mb-2">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                        <input type="search" data-member-filter="project-create-members" placeholder="{{ __('app.common.search_placeholder') }}" class="input pl-9" autocomplete="off">
                    </div>
                    <div id="project-create-members" class="grid max-h-56 gap-2 overflow-y-auto sm:grid-cols-2">
                        @foreach ($memberOptions as $option)
                            <label data-member-filter-item data-member-name="{{ $option->name }} {{ $option->position?->name }}" class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-[10px] font-bold text-indigo-600">
                                    @if ($option->photoUrl())
                                        <img src="{{ $option->photoUrl() }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        {{ $option->initials() }}
                                    @endif
                                </span>
                                <input type="checkbox" name="members[]" value="{{ $option->id }}" @checked(collect(old('members', []))->contains($option->id)) class="h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600">
                                <span class="min-w-0 flex-1 truncate">{{ $option->name }}</span>
                                <span class="shrink-0 text-xs text-slate-400">{{ $option->position?->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p data-member-filter-empty="project-create-members" class="hidden px-1 py-2 text-xs text-slate-400">{{ __('app.common.no_results') }}</p>
                </div>
            @endif
        </x-modal-form>

        @foreach ($projects as $project)
            @if ($project->canBeManagedBy(auth()->user()))
                <x-modal-form id="project-edit-{{ $project->id }}" :title="__('app.projects.edit_title')" :subtitle="$project->name" :action="route('projects.update', $project)" method="PUT" wide :open="$openModal === 'project-edit-'.$project->id">
                    @include('projects._fields', ['project' => $project, 'prefix' => 'project-edit-'.$project->id])
                </x-modal-form>
            @endif
        @endforeach

        @if ($editItem && ! $projects->getCollection()->contains('id', $editItem->id))
            <x-modal-form id="project-edit-{{ $editItem->id }}" :title="__('app.projects.edit_title')" :subtitle="$editItem->name" :action="route('projects.update', $editItem)" method="PUT" wide :open="$openModal === 'project-edit-'.$editItem->id">
                @include('projects._fields', ['project' => $editItem, 'prefix' => 'project-edit-'.$editItem->id])
            </x-modal-form>
        @endif
    @endif
@endsection
