@php $prefix = $prefix ?? 'position'; @endphp
@php $levelOptions = $levelOptions ?? \App\Models\Position::LEVELS; @endphp

<div>
    <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $position->name) }}" class="input" placeholder="Staff" required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="{{ $prefix }}-level">{{ __('app.positions.level') }}</label>
    <select id="{{ $prefix }}-level" name="level" class="input" required>
        @foreach ($levelOptions as $level)
            <option value="{{ $level }}" @selected(old('level', $position->level) === $level)>{{ $level }}</option>
        @endforeach
    </select>
    @error('level') <p class="form-error">{{ $message }}</p> @enderror
</div>

@if (! empty($canManageVisibility))
    <label class="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <input type="checkbox" name="is_visible" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
               @checked(old('is_visible', $position->exists ? $position->is_visible : true))>
        <span>
            <span class="block text-sm font-semibold text-slate-700">{{ __('app.positions.visible') }}</span>
            <span class="block text-xs text-slate-500">{{ __('app.positions.visible_hint') }}</span>
        </span>
    </label>
@endif
