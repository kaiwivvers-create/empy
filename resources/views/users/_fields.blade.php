@php
    $prefix = $prefix ?? 'user';
    $isEdit = $user->exists;
    $roleOptions = $roleOptions ?? collect(['admin', 'manager', 'employee'])
        ->mapWithKeys(fn (string $role) => [$role => __('app.roles.'.$role)])->all();
    $employee = $user->employee;
    $departments = $departments ?? collect();
    $positions = $positions ?? collect();
    $managers = $managers ?? collect();
    $employeeOptions = collect($employeeOptions ?? collect());

    if ($employee && ! $employeeOptions->contains('id', $employee->id)) {
        $employeeOptions = $employeeOptions->prepend($employee);
    }
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
        <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $user->name) }}" class="input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-email">{{ __('app.auth.email') }}</label>
        <input id="{{ $prefix }}-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
        @error('email') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-role">{{ __('app.users.role') }}</label>
        @if ($user->isSuperAdmin())
            {{-- The super admin role is permanent; there is no demotion path. --}}
            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_SUPER_ADMIN }}">
            <select id="{{ $prefix }}-role" class="input" disabled>
                <option selected>{{ __('app.roles.super_admin') }}</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">{{ __('app.users.super_admin_locked') }}</p>
        @else
            <select id="{{ $prefix }}-role" name="role" class="input">
                @foreach ($roleOptions as $role => $label)
                    <option value="{{ $role }}" @selected(old('role', $user->role ?? 'employee') === $role)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        @error('role') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-password">
            {{ __('app.auth.password') }}
            @if ($isEdit) <span class="font-normal text-slate-400">({{ __('app.common.optional') }})</span> @endif
        </label>
        <input id="{{ $prefix }}-password" type="password" name="password" class="input" @required(! $isEdit) autocomplete="new-password">
        <p class="mt-1 text-xs text-slate-500">{{ __('app.users.password_hint') }}</p>
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-5 space-y-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
    <div>
        <h3 class="text-sm font-bold text-slate-900">{{ __('app.users.employee_section') }}</h3>
        <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ __('app.users.employee_hint') }}</p>
    </div>

    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3">
        <label class="label" for="{{ $prefix }}-employee-link">{{ __('app.users.link_employee') }}</label>
        <select id="{{ $prefix }}-employee-link" name="employee_id" class="input" data-employee-link data-searchable>
            <option value="">{{ __('app.users.link_employee_none') }}</option>
            @foreach ($employeeOptions as $candidate)
                <option value="{{ $candidate->id }}"
                        @selected((string) old('employee_id', $employee?->id) === (string) $candidate->id)
                        data-name="{{ $candidate->name }}"
                        data-nip="{{ $candidate->nip }}"
                        data-phone="{{ $candidate->phone }}"
                        data-department-id="{{ $candidate->department_id }}"
                        data-position-id="{{ $candidate->position_id }}"
                        data-manager-id="{{ $candidate->manager_id }}"
                        data-hire-date="{{ $candidate->hire_date?->format('Y-m-d') }}">
                    {{ $candidate->name }} — {{ $candidate->nip }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('app.users.link_employee_hint') }}</p>
        @error('employee_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    @include('partials.image-cropper', [
        'dataName' => 'photo_data',
        'removeName' => 'remove_photo',
        'allowRemove' => (bool) ($employee?->photo_path || $user->avatar_path),
        'currentUrl' => $user->avatarUrl(),
        'initials' => $user->avatarInitials(),
        'hint' => __('app.profile.photo_hint'),
        'pickLabel' => __('app.common.upload_photo'),
        'removeLabel' => __('app.common.remove_photo'),
    ])
    @error('photo_data') <p class="form-error">{{ $message }}</p> @enderror

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="{{ $prefix }}-nip">{{ __('app.employees.nip') }}</label>
            <input id="{{ $prefix }}-nip" name="nip" value="{{ old('nip', $employee?->nip) }}" class="input">
            <p class="mt-1 text-xs text-slate-500">{{ __('app.users.employee_nip_hint') }}</p>
            @error('nip') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-phone">{{ __('app.common.phone') }}</label>
            <input id="{{ $prefix }}-phone" name="phone" value="{{ old('phone', $employee?->phone) }}" class="input">
            @error('phone') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-department">{{ __('app.common.department') }}</label>
            <select id="{{ $prefix }}-department" name="department_id" class="input">
                <option value="">{{ __('app.common.none') }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $employee?->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-position">{{ __('app.common.position') }}</label>
            <select id="{{ $prefix }}-position" name="position_id" class="input">
                <option value="">{{ __('app.common.none') }}</option>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" @selected(old('position_id', $employee?->position_id) == $position->id)>{{ $position->name }}</option>
                @endforeach
            </select>
            @error('position_id') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="label" for="{{ $prefix }}-manager">{{ __('app.common.manager') }}</label>
            <select id="{{ $prefix }}-manager" name="manager_id" class="input">
                <option value="">{{ __('app.employees.no_manager') }}</option>
                @foreach ($managers as $manager)
                    @continue($employee && $manager->id === $employee->id)
                    <option value="{{ $manager->id }}" @selected(old('manager_id', $employee?->manager_id) == $manager->id)>{{ $manager->name }} — {{ $manager->position?->name ?? $manager->nip }}</option>
                @endforeach
            </select>
            @error('manager_id') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-hire">{{ __('app.employees.hire_date') }}</label>
            <input id="{{ $prefix }}-hire" type="date" name="hire_date" value="{{ old('hire_date', $employee?->hire_date?->format('Y-m-d')) }}" class="input">
            @error('hire_date') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<label class="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           @checked(old('is_active', $user->is_active ?? true))>
    <span>
        <span class="block text-sm font-semibold text-slate-700">{{ __('app.common.active') }}</span>
        <span class="block text-xs text-slate-500">{{ __('app.users.is_active_hint') }}</span>
    </span>
</label>
