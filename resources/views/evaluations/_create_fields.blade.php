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

<div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-500">
    <p class="font-semibold text-slate-600">{{ __('app.evaluations.scale') }}</p>
    <p class="mt-1">{{ __('app.evaluations.scale_hint') }}</p>
    <p class="mt-1">{{ __('app.evaluations.weighted_note') }}</p>
</div>
