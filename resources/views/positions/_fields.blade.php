@php $prefix = $prefix ?? 'position'; @endphp

<div>
    <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $position->name) }}" class="input" placeholder="Staff" required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="{{ $prefix }}-level">{{ __('app.positions.level') }}</label>
    <input id="{{ $prefix }}-level" name="level" value="{{ old('level', $position->level) }}" class="input" placeholder="Senior / Junior / Manager">
    @error('level') <p class="form-error">{{ $message }}</p> @enderror
</div>
