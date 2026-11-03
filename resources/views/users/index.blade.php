@extends('layouts.app')

@section('title', __('app.users.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.users.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.users.subtitle') }}</p>
        </div>
        <button type="button" class="btn-primary" data-modal-open="user-create">
            <svg class="h-4 w-4"><use href="#i-plus"/></svg>
            {{ __('app.users.add') }}
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('app.common.search_placeholder') }}" class="input pl-9">
                    </div>
                </div>
                <div class="flex gap-2">
                    <select name="role" class="input">
                        <option value="">{{ __('app.common.all') }} · {{ __('app.users.role') }}</option>
                        @foreach ($roleOptions as $role => $label)
                            <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary shrink-0">{{ __('app.actions.filter') }}</button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[80rem]">
                <colgroup>
                    <col>
                    <col class="w-[13rem]">
                    <col class="w-[9rem]">
                    <col class="w-[11rem]">
                    <col class="w-[10rem]">
                    <col class="w-[9rem]">
                    <col class="w-[7rem]">
                    <col class="w-[9rem]">
                </colgroup>
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.common.name') }}</th>
                        <th>{{ __('app.auth.email') }}</th>
                        <th>{{ __('app.users.role') }}</th>
                        <th>{{ __('app.users.linked_employee') }}</th>
                        <th>{{ __('app.common.department') }}</th>
                        <th>{{ __('app.common.phone') }}</th>
                        <th class="text-center">{{ __('app.common.status') }}</th>
                        <th class="text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="table-row">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">
                                        @if ($user->avatarUrl())
                                            <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ $user->avatarInitials() }}
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-800">{{ $user->name }}</p>
                                        @if ($user->employee)
                                            <p class="text-xs text-slate-400">{{ $user->employee->nip }}</p>
                                        @endif
                                        @if ($user->id === auth()->id())
                                            <p class="text-xs text-indigo-500">{{ $user->roleLabel() }} — {{ __('app.auth.welcome_back') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-500">{{ $user->email }}</td>
                            <td>
                                <span class="{{ $user->isSuperAdmin() ? 'badge-red' : ($user->isAdmin() ? 'badge-indigo' : ($user->isManager() ? 'badge-amber' : 'badge-slate')) }}">
                                    {{ $user->roleLabel() }}
                                </span>
                            </td>
                            <td class="text-slate-500">
                                @if ($user->employee)
                                    <a href="{{ route('employees.show', $user->employee) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $user->employee->name }}</a>
                                @else
                                    <span class="text-slate-400">{{ __('app.users.no_employee') }}</span>
                                @endif
                            </td>
                            <td class="text-slate-500">{{ $user->employee?->department?->name ?? '-' }}</td>
                            <td class="text-slate-500">{{ $user->employee?->phone ?? '-' }}</td>
                            <td class="text-center">
                                <span class="{{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                                    {{ $user->is_active ? __('app.common.active') : __('app.common.inactive') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="{{ __('app.actions.edit') }}"
                                            data-modal-open="user-edit-{{ $user->id }}">
                                        <svg class="h-4 w-4"><use href="#i-pencil"/></svg>
                                    </button>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('{{ __('app.common.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="{{ __('app.actions.delete') }}">
                                                <svg class="h-4 w-4"><use href="#i-trash"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('app.users.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </div>

    @php $blankUser = new \App\Models\User; @endphp

    @php $employeeFields = ['departments' => $departments, 'positions' => $positions, 'managers' => $managers]; @endphp

    <x-modal-form id="user-create" :title="__('app.users.create_title')" :subtitle="__('app.users.subtitle')" :action="route('users.store')" :open="$openModal === 'user-create'" wide>
        @include('users._fields', ['user' => $blankUser, 'roleOptions' => $roleOptions, 'prefix' => 'user-create'] + $employeeFields)
    </x-modal-form>

    @foreach ($users as $user)
        <x-modal-form id="user-edit-{{ $user->id }}" :title="__('app.users.edit_title')" :subtitle="__('app.users.subtitle')" :action="route('users.update', $user)" method="PUT" :open="$openModal === 'user-edit-'.$user->id" wide>
            @include('users._fields', ['user' => $user, 'roleOptions' => $roleOptions, 'prefix' => 'user-edit-'.$user->id] + $employeeFields)
        </x-modal-form>
    @endforeach

    @if ($editItem && ! $users->getCollection()->contains('id', $editItem->id))
        <x-modal-form id="user-edit-{{ $editItem->id }}" :title="__('app.users.edit_title')" :subtitle="__('app.users.subtitle')" :action="route('users.update', $editItem)" method="PUT" :open="$openModal === 'user-edit-'.$editItem->id" wide>
            @include('users._fields', ['user' => $editItem, 'roleOptions' => $roleOptions, 'prefix' => 'user-edit-'.$editItem->id] + $employeeFields)
        </x-modal-form>
    @endif
@endsection
