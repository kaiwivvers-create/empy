<x-error-page code="429"
    :title="__('app.errors.429.title')"
    :message="__('app.errors.429.message')"
    :actionHref="url('/')"
    :actionLabel="__('app.errors.back_home')"
    :secondaryHref="route('login')"
    :secondaryLabel="__('app.errors.sign_in')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 17a9 9 0 1 1 18 0"/>
        <path d="M12 17 16 9"/>
        <circle cx="12" cy="17" r="1.5"/>
    </svg>
</x-error-page>
