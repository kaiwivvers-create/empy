<x-error-page code="400"
    :title="__('app.errors.400.title')"
    :message="__('app.errors.400.message')"
    :actionHref="url('/')"
    :actionLabel="__('app.errors.back_home')"
    :secondaryHref="route('login')"
    :secondaryLabel="__('app.errors.sign_in')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.5 1.5"/>
        <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>
        <path d="m3 21 3-3M18 6l3-3"/>
    </svg>
</x-error-page>
