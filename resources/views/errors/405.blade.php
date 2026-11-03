<x-error-page code="405"
    :title="__('app.errors.405.title')"
    :message="__('app.errors.405.message')"
    :actionHref="url('/')"
    :actionLabel="__('app.errors.back_home')"
    :secondaryHref="route('login')"
    :secondaryLabel="__('app.errors.sign_in')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 3v18"/>
        <path d="M4 7h12l3 3-3 3H4Z"/>
        <path d="M20 15H9l-3 3 3 3h11Z"/>
    </svg>
</x-error-page>
