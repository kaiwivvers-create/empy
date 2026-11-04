@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('app.pagination.label') }}" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">
            {{ __('app.pagination.showing', [
                'first' => $paginator->firstItem(),
                'last' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true"
                      aria-label="{{ __('app.pagination.previous') }}"
                      class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-300">
                    <svg class="h-4 w-4 rotate-180"><use href="#i-chevron"/></svg>
                    <span class="hidden sm:inline">{{ __('app.pagination.previous') }}</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   rel="prev"
                   aria-label="{{ __('app.pagination.previous') }}"
                   class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <svg class="h-4 w-4 rotate-180"><use href="#i-chevron"/></svg>
                    <span class="hidden sm:inline">{{ __('app.pagination.previous') }}</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-xs font-semibold text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-indigo-600 px-2.5 text-xs font-bold text-white shadow-sm shadow-indigo-200">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               aria-label="{{ __('app.pagination.go_to', ['page' => $page]) }}"
                               class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   rel="next"
                   aria-label="{{ __('app.pagination.next') }}"
                   class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <span class="hidden sm:inline">{{ __('app.pagination.next') }}</span>
                    <svg class="h-4 w-4"><use href="#i-chevron"/></svg>
                </a>
            @else
                <span aria-disabled="true"
                      aria-label="{{ __('app.pagination.next') }}"
                      class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-300">
                    <span class="hidden sm:inline">{{ __('app.pagination.next') }}</span>
                    <svg class="h-4 w-4"><use href="#i-chevron"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
