@extends('layouts.app')

@section('title', __('app.roles_page.title'))

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.roles_page.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ __('app.roles_page.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('access.index') }}" class="btn-secondary">{{ __('app.access.title') }}</a>
            <button type="button" class="btn-primary" data-modal-open="role-create">
                <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                {{ __('app.roles_page.add') }}
            </button>
        </div>
    </div>

    <div class="mb-4 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
        {{ __('app.roles_page.hint') }}
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            <div class="card flex flex-col">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate font-bold text-slate-900">{{ $role->displayLabel() }}</h3>
                            @if ($role->is_system)
                                <span class="badge-slate">{{ __('app.roles_page.system') }}</span>
                            @else
                                <span class="badge-indigo">{{ __('app.roles_page.custom') }}</span>
                            @endif
                        </div>
                        <p class="mt-0.5 font-mono text-xs text-slate-400">{{ $role->name }}</p>
                    </div>
                    <span class="badge-amber">{{ __('app.roles_page.base_'.$role->base) }}</span>
                </div>

                <div class="flex flex-1 items-center justify-between gap-3 px-5 py-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('app.roles_page.users') }}</p>
                        <p class="text-lg font-extrabold text-slate-800">{{ $role->users_count }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('app.roles_page.pages') }}</p>
                        <p class="text-lg font-extrabold text-slate-800">{{ $usage[$role->name] ?? 0 }}/{{ $pageCount }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-1 border-t border-slate-100 px-5 py-3">
                    <button type="button" class="btn-ghost" data-modal-open="role-edit-{{ $role->id }}">
                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                        {{ __('app.actions.edit') }}
                    </button>
                    @unless ($role->is_system)
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ghost text-rose-600 hover:bg-rose-50">
                                <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                {{ __('app.actions.delete') }}
                            </button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>

    <x-modal-form id="role-create" :title="__('app.roles_page.create_title')" :subtitle="__('app.roles_page.subtitle')" :action="route('roles.store')">
        @include('roles._fields', ['role' => new \App\Models\Role, 'bases' => $bases, 'prefix' => 'role-create'])
    </x-modal-form>

    @foreach ($roles as $role)
        <x-modal-form id="role-edit-{{ $role->id }}" :title="__('app.roles_page.edit_title')" :subtitle="$role->displayLabel()" :action="route('roles.update', $role)" method="PUT">
            @include('roles._fields', ['role' => $role, 'bases' => $bases, 'prefix' => 'role-edit-'.$role->id])
        </x-modal-form>
    @endforeach
@endsection
