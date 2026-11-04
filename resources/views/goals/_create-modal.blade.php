@php
    $defaultPeriod = old('period_id', $selectedPeriod ?? $periods->firstWhere('status', 'active')?->id);
@endphp

<x-modal-form id="goal-create"
              :title="__('app.goals.add')"
              :subtitle="__('app.goals.subtitle')"
              :action="route('employees.goals.store', $employee)">
    <div>
        <label class="label" for="goal-period">{{ __('app.goals.period') }}</label>
        <select id="goal-period" name="period_id" class="input" required>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected($defaultPeriod == $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        @error('period_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="goal-title">{{ __('app.common.name') }}</label>
        <input id="goal-title" name="title" class="input" value="{{ old('title') }}" placeholder="Contoh: Closing 20 pelanggan" required>
        @error('title') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="goal-target">{{ __('app.goals.target') }}</label>
            <input id="goal-target" type="number" step="0.01" min="0.01" name="target_value" value="{{ old('target_value') }}" class="input" required>
            @error('target_value') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="goal-unit">{{ __('app.goals.unit') }}</label>
            <input id="goal-unit" name="unit" value="{{ old('unit', '%') }}" class="input" required>
            @error('unit') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="label" for="goal-achievement">{{ __('app.goals.achievement') }}</label>
        <input id="goal-achievement" type="number" step="0.01" min="0" name="achievement_value" value="{{ old('achievement_value') }}" class="input" placeholder="{{ __('app.common.optional') }}">
        @error('achievement_value') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="goal-notes">{{ __('app.goals.notes') }}</label>
        <input id="goal-notes" name="notes" value="{{ old('notes') }}" class="input">
        @error('notes') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</x-modal-form>
