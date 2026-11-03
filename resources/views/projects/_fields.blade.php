@php $prefix = $prefix ?? 'project'; @endphp

<div>
    <label class="label" for="{{ $prefix }}-name">{{ __('app.projects.name') }}</label>
    <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $project->name) }}" class="input" placeholder="Website Refresh" required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="label" for="{{ $prefix }}-status">{{ __('app.projects.status') }}</label>
        <select id="{{ $prefix }}-status" name="status" class="input">
            @foreach (\App\Models\Project::statuses() as $status)
                <option value="{{ $status }}" @selected(old('status', $project->status ?? 'active') === $status)>{{ __('app.projects.statuses.'.$status) }}</option>
            @endforeach
        </select>
        @error('status') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-start">{{ __('app.projects.start_date') }}</label>
        <input id="{{ $prefix }}-start" type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="input">
        @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="{{ $prefix }}-due">{{ __('app.projects.due_date') }}</label>
        <input id="{{ $prefix }}-due" type="date" name="due_date" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}" class="input">
        @error('due_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="label" for="{{ $prefix }}-description">{{ __('app.projects.description') }}</label>
    <textarea id="{{ $prefix }}-description" name="description" rows="4" class="input" placeholder="{{ __('app.projects.subtitle') }}">{{ old('description', $project->description) }}</textarea>
    @error('description') <p class="form-error">{{ $message }}</p> @enderror
</div>
