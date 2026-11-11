@php
    $prefix = $prefix ?? 'employee';
    $isEdit = $employee->exists;
    $linkedUser = $employee->user;
    $accountRoles = $accountRoles ?? collect(['employee', 'manager'])
        ->mapWithKeys(fn (string $role) => [$role => __('app.roles.'.$role)])->all();
    $accountPicker = $accountPicker ?? null;
    $linkableUsers = collect($linkableUsers ?? collect());

    if ($linkedUser && ! $linkableUsers->contains('id', $linkedUser->id)) {
        $linkableUsers = $linkableUsers->prepend($linkedUser);
    }
@endphp

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.employees.personal_info') }}</h3>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <p class="label">{{ __('app.common.photo') }} ({{ __('app.common.optional') }})</p>
                    @include('partials.image-cropper', [
                        'dataName' => 'photo_data',
                        'removeName' => 'remove_photo',
                        'allowRemove' => (bool) $employee->photo_path,
                        'currentUrl' => $employee->photoUrl(),
                        'initials' => $employee->initials(),
                        'hint' => __('app.profile.photo_hint'),
                        'pickLabel' => __('app.common.upload_photo'),
                        'removeLabel' => __('app.common.remove_photo'),
                    ])
                    @error('photo') <p class="form-error">{{ $message }}</p> @enderror
                    @error('photo_data') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
                    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $employee->name) }}" class="input" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-nip">{{ __('app.employees.nip') }}</label>
                    <input id="{{ $prefix }}-nip" name="nip" value="{{ old('nip', $employee->nip) }}" class="input" required>
                    @error('nip') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-email">{{ __('app.common.email') }}</label>
                    <input id="{{ $prefix }}-email" type="email" name="email" value="{{ old('email', $employee->email) }}" class="input">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-phone">{{ __('app.common.phone') }}</label>
                    <input id="{{ $prefix }}-phone" name="phone" value="{{ old('phone', $employee->phone) }}" class="input">
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-hire">{{ __('app.employees.hire_date') }}</label>
                    <input id="{{ $prefix }}-hire" type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" class="input">
                    @error('hire_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-status">{{ __('app.common.status') }}</label>
                    <select id="{{ $prefix }}-status" name="status" class="input">
                        <option value="active" @selected(old('status', $employee->status ?? 'active') === 'active')>{{ __('app.common.active') }}</option>
                        <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>{{ __('app.common.inactive') }}</option>
                    </select>
                    @error('status') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.employees.job_info') }}</h3>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="{{ $prefix }}-department">{{ __('app.common.department') }}</label>
                    <select id="{{ $prefix }}-department" name="department_id" class="input">
                        <option value="">{{ __('app.common.none') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id) == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-position">{{ __('app.common.position') }}</label>
                    <select id="{{ $prefix }}-position" name="position_id" class="input">
                        <option value="">{{ __('app.common.none') }}</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected(old('position_id', $employee->position_id) == $position->id)>{{ $position->name }}</option>
                        @endforeach
                    </select>
                    @error('position_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="{{ $prefix }}-manager">{{ __('app.common.manager') }}</label>
                    <select id="{{ $prefix }}-manager" name="manager_id" class="input">
                        <option value="">{{ __('app.employees.no_manager') }}</option>
                        @foreach ($managers as $manager)
                            @continue($isEdit && $manager->id === $employee->id)
                            <option value="{{ $manager->id }}" @selected(old('manager_id', $employee->manager_id) == $manager->id)>{{ $manager->name }} — {{ $manager->position?->name ?? $manager->nip }}</option>
                        @endforeach
                    </select>
                    @error('manager_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        @if (! empty($accountPicker))
            <div class="card border-indigo-100 bg-indigo-50/40">
                <div class="card-header">
                    <h3 class="font-bold text-slate-900">{{ __('app.organization.autofill_title') }}</h3>
                </div>
                <div class="card-body space-y-2">
                    <label class="label" for="{{ $prefix }}-autofill">{{ __('app.organization.autofill_employee') }}</label>
                    <select id="{{ $prefix }}-autofill"
                            class="input"
                            data-employee-fill
                            data-store-url="{{ route('employees.store') }}">
                        <option value="">{{ __('app.organization.autofill_none') }}</option>
                        @foreach ($accountPicker as $candidate)
                            <option value="{{ $candidate->id }}" @selected(old('employee_id') == $candidate->id)
                                    data-name="{{ $candidate->name }}"
                                    data-nip="{{ $candidate->nip }}"
                                    data-email="{{ $candidate->email }}"
                                    data-phone="{{ $candidate->phone }}"
                                    data-department-id="{{ $candidate->department_id }}"
                                    data-position-id="{{ $candidate->position_id }}"
                                    data-manager-id="{{ $candidate->manager_id }}"
                                    data-hire-date="{{ $candidate->hire_date?->format('Y-m-d') }}"
                                    data-status="{{ $candidate->status }}"
                                    data-account-email="{{ $candidate->user?->email ?? $candidate->email }}"
                                    data-account-role="{{ $candidate->user?->role === \App\Models\User::ROLE_SUPER_ADMIN ? 'employee' : ($candidate->user?->role ?? 'employee') }}"
                                    data-has-account="{{ $candidate->user ? '1' : '0' }}"
                                    data-update-url="{{ route('employees.update', $candidate) }}">
                                {{ $candidate->name }} — {{ $candidate->nip }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs leading-relaxed text-slate-500">{{ __('app.organization.autofill_hint') }}</p>
                    <p class="hidden rounded-lg bg-amber-50 px-2.5 py-2 text-xs font-medium text-amber-800" data-generated-password></p>
                    <input type="hidden" name="employee_id" value="{{ old('employee_id') }}">
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-slate-900">{{ __('app.employees.account') }}</h3>
            </div>
            <div class="card-body space-y-4">
                <p class="text-xs leading-relaxed text-slate-500">{{ __('app.employees.account_hint') }}</p>

                @if ($linkedUser)
                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100">
                        {{ __('app.employees.linked_account') }}: {{ $linkedUser->email }}
                    </div>
                @endif

                <div>
                    <label class="label" for="{{ $prefix }}-account-link">{{ __('app.employees.link_account') }}</label>
                    <select id="{{ $prefix }}-account-link" name="user_id" class="input" data-searchable>
                        <option value="">{{ __('app.employees.link_account_none') }}</option>
                        @foreach ($linkableUsers as $candidate)
                            <option value="{{ $candidate->id }}" @selected((string) old('user_id', $linkedUser?->id) === (string) $candidate->id)>
                                {{ $candidate->name }} — {{ $candidate->email }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('app.employees.link_account_hint') }}</p>
                    @error('user_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="{{ $prefix }}-account-email">{{ __('app.common.email') }}</label>
                    <input id="{{ $prefix }}-account-email" type="email" name="account_email" value="{{ old('account_email', $linkedUser?->email) }}" class="input" placeholder="employee@company.com">
                    @error('account_email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-account-role">{{ __('app.employees.account_role') }}</label>
                    <select id="{{ $prefix }}-account-role" name="account_role" class="input">
                        @foreach ($accountRoles as $role => $label)
                            <option value="{{ $role }}" @selected(old('account_role', $linkedUser?->role ?? 'employee') === $role)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="{{ $prefix }}-account-password">
                        {{ __('app.auth.password') }}
                        @if ($linkedUser)
                            <span class="font-normal text-slate-400">({{ __('app.common.optional') }})</span>
                        @endif
                    </label>
                    <input id="{{ $prefix }}-account-password" type="password" name="account_password" class="input" placeholder="••••••••">
                    @error('account_password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>
</div>
