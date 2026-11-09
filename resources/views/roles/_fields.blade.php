@php
    $prefix = $prefix ?? 'role';
    $isEdit = $role->exists;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="{{ $prefix }}-name">{{ __('app.roles_page.name') }}</label>
        <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $role->name) }}" class="input font-mono" required
               maxlength="20" placeholder="hr_staff" @readonly($role->is_system)>
        <p class="mt-1 text-xs text-slate-500">{{ __('app.roles_page.name_hint') }}</p>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-base">{{ __('app.roles_page.base') }}</label>
        <select id="{{ $prefix }}-base" name="base" class="input" @disabled($role->is_system)>
            @foreach ($bases as $base)
                <option value="{{ $base }}" @selected(old('base', $role->base ?: 'employee') === $base)>{{ __('app.roles_page.base_'.$base) }}</option>
            @endforeach
        </select>
        @if ($role->is_system)
            <input type="hidden" name="base" value="{{ $role->base }}">
        @endif
        <p class="mt-1 text-xs text-slate-500">{{ __('app.roles_page.base_hint') }}</p>
        @error('base') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-label">{{ __('app.roles_page.label_en') }}</label>
        <input id="{{ $prefix }}-label" name="label" value="{{ old('label', $role->label) }}" class="input" required maxlength="60" placeholder="HR Staff">
        @error('label') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-label-id">{{ __('app.roles_page.label_id') }}</label>
        <input id="{{ $prefix }}-label-id" name="label_id" value="{{ old('label_id', $role->label_id) }}" class="input" maxlength="60" placeholder="Staf HR">
        @error('label_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-500">
    {{ __('app.roles_page.permissions_hint') }}
</div>

<label class="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <input type="hidden" name="show_in_accounts" value="0">
    <input type="checkbox" name="show_in_accounts" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           @checked(old('show_in_accounts', $role->exists ? $role->show_in_accounts : true))>
    <span>
        <span class="block text-sm font-semibold text-slate-700">{{ __('app.roles_page.show_in_accounts') }}</span>
        <span class="block text-xs text-slate-500">{{ __('app.roles_page.show_in_accounts_hint') }}</span>
    </span>
</label>
@error('show_in_accounts') <p class="form-error">{{ $message }}</p> @enderror
