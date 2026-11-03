@php $prefix = $prefix ?? 'department'; @endphp

<div>
    <label class="label" for="{{ $prefix }}-code">{{ __('app.common.code') }}</label>
    <input id="{{ $prefix }}-code" name="code" value="{{ old('code', $department->code) }}" class="input" placeholder="HRD" required>
    @error('code') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $department->name) }}" class="input" placeholder="Human Resources" required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="{{ $prefix }}-head">{{ __('app.organization.head') }}</label>
    <select id="{{ $prefix }}-head" name="head_id" class="input">
        <option value="">{{ __('app.organization.no_head') }}</option>
        @foreach ($employees as $employee)
            @continue($employee->headedDepartment && $employee->headedDepartment->id !== $department->id)
            <option value="{{ $employee->id }}" @selected(old('head_id', $department->head_id) == $employee->id)>
                {{ $employee->name }} — {{ $employee->position?->name ?? $employee->nip }}
            </option>
        @endforeach
    </select>
    @error('head_id') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="{{ $prefix }}-description">{{ __('app.common.description') }}</label>
    <textarea id="{{ $prefix }}-description" name="description" rows="3" class="input">{{ old('description', $department->description) }}</textarea>
    @error('description') <p class="form-error">{{ $message }}</p> @enderror
</div>
