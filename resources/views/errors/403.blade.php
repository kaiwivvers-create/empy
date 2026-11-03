<x-error-page code="403"
    :title="__('app.errors.403.title')"
    :message="__('app.errors.403.message')"
    :actionHref="url('/')"
    :actionLabel="__('app.errors.back_home')"
    :secondaryHref="route('login')"
    :secondaryLabel="__('app.errors.sign_in')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
        <path d="M12 8v4"/>
        <path d="M12 16h.01"/>
    </svg>
</x-error-page>
