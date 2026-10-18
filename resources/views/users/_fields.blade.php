@php
    $prefix = $prefix ?? 'user';
    $isEdit = $user->exists;
    $roleOptions = $roleOptions ?? (auth()->user()->isSuperAdmin()
        ? ['super_admin', 'admin', 'manager', 'employee']
        : ['admin', 'manager', 'employee']);
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
        <select id="{{ $prefix }}-role" name="role" class="input">
            @foreach ($roleOptions as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? 'employee') === $role)>{{ __('app.roles.'.$role) }}</option>
            @endforeach
        </select>
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
<label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           @checked(old('is_active', $user->is_active ?? true))>
    <span>
        <span class="block text-sm font-semibold text-slate-700">{{ __('app.common.active') }}</span>
        <span class="block text-xs text-slate-500">{{ __('app.users.is_active_hint') }}</span>
    </span>
</label>
