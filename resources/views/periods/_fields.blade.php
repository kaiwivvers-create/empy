@php $prefix = $prefix ?? 'period'; @endphp

<div>
    <label class="label" for="{{ $prefix }}-name">{{ __('app.common.name') }}</label>
    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $period->name) }}" class="input" placeholder="Annual Review 2026" required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="{{ $prefix }}-type">{{ __('app.periods.type') }}</label>
        <select id="{{ $prefix }}-type" name="type" class="input">
            @foreach (['monthly', 'quarterly', 'semester', 'annual'] as $type)
                <option value="{{ $type }}" @selected(old('type', $period->type ?? 'annual') === $type)>{{ __('app.periods.types.'.$type) }}</option>
            @endforeach
        </select>
        @error('type') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-status">{{ __('app.common.status') }}</label>
        <select id="{{ $prefix }}-status" name="status" class="input">
            @foreach (['draft', 'active', 'closed'] as $status)
                <option value="{{ $status }}" @selected(old('status', $period->status ?? 'draft') === $status)>{{ __('app.periods.statuses.'.$status) }}</option>
            @endforeach
        </select>
        @error('status') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-start">{{ __('app.periods.start') }}</label>
        <input id="{{ $prefix }}-start" type="date" name="start_date" value="{{ old('start_date', $period->start_date?->format('Y-m-d')) }}" class="input" required>
        @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-end">{{ __('app.periods.end') }}</label>
        <input id="{{ $prefix }}-end" type="date" name="end_date" value="{{ old('end_date', $period->end_date?->format('Y-m-d')) }}" class="input" required>
        @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>
