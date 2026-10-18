@props([
    'id',
    'title',
    'subtitle' => null,
    'action',
    'method' => 'POST',
    'open' => false,
    'enctype' => null,
    'wide' => false,
    'submitLabel' => null,
])

@php
    $isOpen = (bool) $open || old('_modal') === $id;
    $panelWidth = $wide ? 'max-w-3xl' : 'max-w-xl';
@endphp

<div id="{{ $id }}"
     class="modal"
     data-modal
     data-open="{{ $isOpen ? 'true' : 'false' }}"
     aria-hidden="{{ $isOpen ? 'false' : 'true' }}">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="modal-panel {{ $panelWidth }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <h2 id="{{ $id }}-title" class="text-lg font-extrabold text-slate-900">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-modal-close aria-label="{{ __('app.actions.close') }}">
                <svg class="h-5 w-5"><use href="#i-x"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ $action }}" @if ($enctype) enctype="{{ $enctype }}" @endif class="flex min-h-0 flex-1 flex-col">
            @csrf
            @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
                @method($method)
            @endif
            <input type="hidden" name="_modal" value="{{ $id }}">

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">
                @if (old('_modal') === $id && $errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                <button type="button" class="btn-secondary" data-modal-close>{{ __('app.actions.cancel') }}</button>
                <button type="submit" class="btn-primary">{{ $submitLabel ?? __('app.actions.save') }}</button>
            </div>
        </form>
    </div>
</div>
