@extends('layouts.app')

@section('title', __('app.organization.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.organization.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.organization.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge-indigo">{{ $totalEmployees }} {{ __('app.common.employee') }}</span>
            @if ($canEdit)
                <button type="button" class="btn-secondary" data-modal-open="chart-department-create">
                    <svg class="h-4 w-4"><use href="#i-building"/></svg>
                    {{ __('app.organization.add_department') }}
                </button>
                <button type="button" class="btn-secondary" data-modal-open="chart-position-create">
                    <svg class="h-4 w-4"><use href="#i-briefcase"/></svg>
                    {{ __('app.organization.add_position') }}
                </button>
                <button type="button" class="btn-primary" data-modal-open="chart-employee-create">
                    <svg class="h-4 w-4"><use href="#i-plus"/></svg>
                    {{ __('app.organization.add_employee') }}
                </button>
            @endif
        </div>
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
            @php
                $headId = $department->head_id;
                // The head gets their own card above, and other leaders are
                // already shown elsewhere in the chart.
                $members = $department->employees->reject(
                    fn ($member) => $member->id === $headId || $member->isManagerAccount()
                );
            @endphp
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ $department->name }}</h3>
                        <p class="text-xs text-slate-400">{{ $department->code }} · {{ $members->count() }} {{ __('app.organization.members') }}</p>
                    </div>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('organization.departments.head', $department) }}" class="flex shrink-0 items-center gap-1.5">
                            @csrf
                            @method('PATCH')
                            <select name="head_id" class="input !w-auto !py-1.5 text-xs" aria-label="{{ __('app.organization.head') }}">
                                <option value="">{{ __('app.organization.no_head') }}</option>
                                @foreach ($managerEmployees as $candidate)
                                    @continue($candidate->headedDepartment && $candidate->headedDepartment->id !== $department->id)
                                    <option value="{{ $candidate->id }}" @selected($department->head_id == $candidate->id)>{{ $candidate->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-indigo-50 hover:text-indigo-600" title="{{ __('app.actions.save') }}">
                                <svg class="h-4 w-4"><use href="#i-check"/></svg>
                            </button>
                        </form>
                    @else
                        <span class="badge-slate">{{ $department->head?->name ?? __('app.organization.no_head') }}</span>
                    @endif
                </div>

                @if ($department->head)
                    <div class="mx-5 mt-4 flex items-center gap-3 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-amber-500 text-xs font-bold text-white">
                            @if ($department->head->photoUrl())
                                <img src="{{ $department->head->photoUrl() }}" alt="{{ $department->head->name }}" class="h-full w-full object-cover">
                            @else
                                {{ $department->head->initials() }}
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $department->head->name }}</p>
                            <p class="text-xs text-amber-700">{{ __('app.organization.head') }}</p>
                        </div>
                    </div>
                @endif

                <div class="space-y-2 p-5">
                    @forelse ($members as $member)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 px-3 py-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                @if ($member->photoUrl())
                                    <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ $member->initials() }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $member->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $member->position?->name ?? '-' }}</p>
                            </div>
                            @if ($headId && $department->head)
                                {{-- Department members always report to their head. --}}
                                <span class="shrink-0 text-xs font-medium text-slate-500">{{ $department->head->name }}</span>
                            @elseif ($canEdit)
                                <form method="POST" action="{{ route('organization.employees.manager', $member) }}" class="flex shrink-0 items-center gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="manager_id" class="input !w-[9.5rem] !px-2 !py-1.5 text-xs" aria-label="{{ __('app.common.manager') }}">
                                        <option value="">{{ __('app.employees.no_manager') }}</option>
                                        @foreach ($managerEmployees as $candidate)
                                            @continue($candidate->id === $member->id)
                                            <option value="{{ $candidate->id }}" @selected($member->manager_id == $candidate->id)>{{ $candidate->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-indigo-50 hover:text-indigo-600" title="{{ __('app.actions.save') }}">
                                        <svg class="h-4 w-4"><use href="#i-check"/></svg>
                                    </button>
                                </form>
                            @else
                                <span class="shrink-0 text-xs text-slate-400">{{ $member->manager?->name ?? '-' }}</span>
                            @endif
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

    @if ($canEdit)
        <x-modal-form id="chart-department-create" :title="__('app.departments.create_title')" :subtitle="__('app.departments.subtitle')" :action="route('departments.store')">
            <input type="hidden" name="return_to" value="chart">
            @include('departments._fields', [
                'department' => new \App\Models\Department,
                'employees' => $managerEmployees,
                'prefix' => 'chart-department',
            ])
        </x-modal-form>

        <x-modal-form id="chart-position-create" :title="__('app.positions.create_title')" :subtitle="__('app.positions.subtitle')" :action="route('positions.store')">
            <input type="hidden" name="return_to" value="chart">
            @include('positions._fields', [
                'position' => new \App\Models\Position,
                'prefix' => 'chart-position',
            ])
        </x-modal-form>

        <x-modal-form id="chart-employee-create"
                      :title="__('app.employees.create_title')"
                      :subtitle="__('app.employees.subtitle')"
                      :action="old('employee_id') ? route('employees.update', (int) old('employee_id')) : route('employees.store')"
                      :method="old('employee_id') ? 'PUT' : 'POST'"
                      enctype="multipart/form-data"
                      wide>
            <input type="hidden" name="return_to" value="chart">
            @include('employees._fields', [
                'employee' => new \App\Models\Employee,
                'departments' => $departments,
                'positions' => $positions,
                'managers' => $managerEmployees,
                'accountRoles' => $accountRoles,
                'accountPicker' => $employees,
                'prefix' => 'chart-employee',
            ])
        </x-modal-form>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-employee-fill]').forEach((picker) => {
                const form = picker.closest('form');

                if (!form) {
                    return;
                }

                const note = form.querySelector('[data-generated-password]');
                const noteTemplate = @json(__('app.organization.autofill_password'));

                const setField = (name, value) => {
                    form.querySelectorAll(`[name="${name}"]`).forEach((field) => {
                        field.value = value ?? '';
                    });
                };

                const setMethod = (method) => {
                    let input = form.querySelector('input[name="_method"]');

                    if (!method) {
                        input?.remove();

                        return;
                    }

                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = '_method';
                        form.append(input);
                    }

                    input.value = method;
                };

                const hideNote = () => {
                    if (!note) {
                        return;
                    }

                    note.textContent = '';
                    note.classList.add('hidden');
                };

                const generatePassword = () => {
                    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                    const bytes = new Uint32Array(10);
                    window.crypto.getRandomValues(bytes);

                    return Array.from(bytes, (byte) => alphabet[byte % alphabet.length]).join('');
                };

                picker.addEventListener('change', () => {
                    const option = picker.selectedOptions[0];

                    if (!option || !option.value) {
                        form.action = picker.dataset.storeUrl;
                        setMethod(null);
                        setField('employee_id', '');
                        hideNote();

                        return;
                    }

                    form.action = option.dataset.updateUrl;
                    setMethod('PUT');
                    setField('employee_id', option.value);
                    setField('name', option.dataset.name);
                    setField('nip', option.dataset.nip);
                    setField('email', option.dataset.email);
                    setField('phone', option.dataset.phone);
                    setField('department_id', option.dataset.departmentId);
                    setField('position_id', option.dataset.positionId);
                    setField('manager_id', option.dataset.managerId);
                    setField('hire_date', option.dataset.hireDate);
                    setField('status', option.dataset.status || 'active');
                    setField('account_email', option.dataset.accountEmail);
                    setField('account_role', option.dataset.accountRole || 'employee');

                    if (option.dataset.hasAccount === '1') {
                        setField('account_password', '');
                        hideNote();

                        return;
                    }

                    const generated = generatePassword();
                    setField('account_password', generated);

                    if (note) {
                        note.textContent = noteTemplate.replace(':password', generated);
                        note.classList.remove('hidden');
                    }
                });
            });
        });
    </script>
@endpush
