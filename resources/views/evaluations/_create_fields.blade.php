@php $prefix = $prefix ?? 'evaluation'; @endphp

<div>
    <label class="label" for="{{ $prefix }}-employee">{{ __('app.evaluations.choose_employee') }}</label>
    <select id="{{ $prefix }}-employee" name="employee_id" class="input" required>
        <option value="">— {{ __('app.employees.choose') }} —</option>
        @foreach ($employees as $employee)
            <option value="{{ $employee->id }}" @selected(old('employee_id', $selectedEmployee) == $employee->id)>
                {{ $employee->name }} — {{ $employee->department?->name ?? '-' }} ({{ $employee->nip }})
            </option>
        @endforeach
    </select>
    @error('employee_id') <p class="form-error">{{ $message }}</p> @enderror
</div>

@if ($periods->isEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
        <p class="font-semibold">{{ __('app.evaluations.no_active_period') }}</p>
        <p class="mt-1">{{ __('app.evaluations.period_created_hint') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-3">
            <label class="label" for="{{ $prefix }}-period-name">{{ __('app.evaluations.period_name') }}</label>
            <input id="{{ $prefix }}-period-name" name="period_name" value="{{ old('period_name') }}" class="input"
                   placeholder="{{ __('app.evaluations.period_name_placeholder') }}" required>
            @error('period_name') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-period-type">{{ __('app.periods.type') }}</label>
            <select id="{{ $prefix }}-period-type" name="period_type" class="input">
                @foreach (['monthly', 'quarterly', 'semester', 'annual'] as $type)
                    <option value="{{ $type }}" @selected(old('period_type', 'annual') === $type)>{{ __('app.periods.types.'.$type) }}</option>
                @endforeach
            </select>
            @error('period_type') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-period-start">{{ __('app.periods.start') }}</label>
            <input id="{{ $prefix }}-period-start" type="date" name="period_start" value="{{ old('period_start', now()->toDateString()) }}" class="input" required>
            @error('period_start') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="{{ $prefix }}-period-end">{{ __('app.periods.end') }}</label>
            <input id="{{ $prefix }}-period-end" type="date" name="period_end" value="{{ old('period_end', now()->addMonths(3)->toDateString()) }}" class="input" required>
            @error('period_end') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
@else
    <div>
        <label class="label" for="{{ $prefix }}-period">{{ __('app.evaluations.choose_period') }}</label>
        <select id="{{ $prefix }}-period" name="period_id" class="input" required>
            <option value="">— {{ __('app.employees.choose') }} —</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}" @selected(old('period_id', $selectedPeriod) == $period->id)>
                    {{ $period->name }} ({{ $period->start_date?->format('d M Y') }} — {{ $period->end_date?->format('d M Y') }})
                </option>
            @endforeach
        </select>
        @error('period_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
@endif

<div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-500">
    <p class="font-semibold text-slate-600">{{ __('app.evaluations.scale') }}</p>
    <p class="mt-1">{{ __('app.evaluations.scale_hint') }}</p>
    <p class="mt-1">{{ __('app.evaluations.weighted_note') }}</p>
</div>
