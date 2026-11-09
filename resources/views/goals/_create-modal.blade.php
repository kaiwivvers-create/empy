@php
    $defaultPeriod = old('period_id', $selectedPeriod ?? $periods->firstWhere('status', 'active')?->id);
    $unitOptions = $unitOptions ?? \App\Models\EmployeeGoal::UNITS;
    $integerUnits = \App\Models\EmployeeGoal::INTEGER_UNITS;
    $defaultUnit = old('unit', '%');
    $defaultIsInteger = in_array($defaultUnit, $integerUnits, true);
@endphp

<x-modal-form id="goal-create"
              :title="__('app.goals.add')"
              :subtitle="__('app.goals.subtitle')"
              :action="route('employees.goals.store', $employee)">
    <div>
        <label class="label" for="goal-period">{{ __('app.goals.period') }}</label>
        <select id="goal-period" name="period_id" class="input" required>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected($defaultPeriod == $period->id)>
                    {{ $period->name }} · {{ $period->start_date->format('M j, Y') }} – {{ $period->end_date->format('M j, Y') }}
                </option>
            @endforeach
        </select>
        @if ($periods->isEmpty())
            <p class="mt-1 text-xs text-amber-700">{{ __('app.goals.no_open_period') }}</p>
        @else
            <p class="mt-1 text-xs text-slate-500">{{ __('app.goals.period_hint') }}</p>
        @endif
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
            <input id="goal-target" type="number"
                   step="{{ $defaultIsInteger ? '1' : '0.01' }}"
                   min="{{ $defaultIsInteger ? '1' : '0.01' }}"
                   max="9999999999.99"
                   name="target_value" value="{{ old('target_value') }}" class="input" required>
            @error('target_value') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="goal-unit">{{ __('app.goals.unit') }}</label>
            <select id="goal-unit" name="unit" class="input" data-integer-units="{{ implode(',', $integerUnits) }}" required>
                @foreach ($unitOptions as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', '%') === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">{{ __('app.goals.unit_hint') }}</p>
            @error('unit') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="label" for="goal-achievement">{{ __('app.goals.achievement') }}</label>
        <input id="goal-achievement" type="number"
               step="{{ $defaultIsInteger ? '1' : '0.01' }}"
               min="0" max="9999999999.99"
               name="achievement_value" value="{{ old('achievement_value') }}" class="input" placeholder="{{ __('app.common.optional') }}">
        @error('achievement_value') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="goal-notes">{{ __('app.goals.notes') }}</label>
        <textarea id="goal-notes" name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
        @error('notes') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</x-modal-form>

<script>
    (() => {
        const unit = document.getElementById('goal-unit');
        const target = document.getElementById('goal-target');
        const achievement = document.getElementById('goal-achievement');

        if (!unit || !target || !achievement) return;

        const integerUnits = unit.dataset.integerUnits.split(',');

        const updateValueRules = () => {
            const isInteger = integerUnits.includes(unit.value);
            target.step = isInteger ? '1' : '0.01';
            target.min = isInteger ? '1' : '0.01';
            achievement.step = isInteger ? '1' : '0.01';
        };

        unit.addEventListener('change', updateValueRules);
    })();
</script>
