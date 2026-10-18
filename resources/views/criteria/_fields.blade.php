@php $prefix = $prefix ?? 'criterion'; @endphp

<div>
    <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $criterion->name) }}" class="input" placeholder="Discipline" required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="{{ $prefix }}-description">{{ __('app.common.description') }}</label>
    <textarea id="{{ $prefix }}-description" name="description" rows="3" class="input" placeholder="{{ __('app.common.optional') }}">{{ old('description', $criterion->description) }}</textarea>
    @error('description') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="{{ $prefix }}-weight">{{ __('app.criteria.weight') }} (%)</label>
        <input id="{{ $prefix }}-weight" type="number" step="0.01" min="0" max="100" name="weight" value="{{ old('weight', $criterion->weight) }}" class="input" required>
        @error('weight') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-sort">{{ __('app.criteria.sort_order') }}</label>
        <input id="{{ $prefix }}-sort" type="number" min="0" max="999" name="sort_order" value="{{ old('sort_order', $criterion->sort_order ?? 0) }}" class="input">
        @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>
<label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           @checked(old('is_active', $criterion->is_active ?? true))>
    <span>
        <span class="block text-sm font-semibold text-slate-700">{{ __('app.common.active') }}</span>
        <span class="block text-xs text-slate-500">{{ __('app.criteria.inactive_hint') }}</span>
    </span>
</label>
