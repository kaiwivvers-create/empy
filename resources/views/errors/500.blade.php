<x-error-page code="500"
    :title="__('app.errors.500.title')"
    :message="__('app.errors.500.message')"
    :actionHref="url()->current()"
    :actionLabel="__('app.errors.reload')"
    :secondaryHref="url('/')"
    :secondaryLabel="__('app.errors.back_home')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="7" rx="2"/>
        <rect x="3" y="14" width="18" height="7" rx="2"/>
        <path d="M7 6.5h.01"/>
        <path d="M7 17.5h.01"/>
        <path d="M13 5.5h4M13 16.5h4"/>
    </svg>
</x-error-page>
