<x-error-page code="404"
    :title="__('app.errors.404.title')"
    :message="__('app.errors.404.message')"
    :actionHref="url('/')"
    :actionLabel="__('app.errors.back_home')"
    :secondaryHref="route('login')"
    :secondaryLabel="__('app.errors.sign_in')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="7"/>
        <path d="m20 20-4-4"/>
        <path d="M8.5 11h.01"/>
        <path d="M13.5 11h.01"/>
    </svg>
</x-error-page>
