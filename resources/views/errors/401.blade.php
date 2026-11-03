<x-error-page code="401"
    :title="__('app.errors.401.title')"
    :message="__('app.errors.401.message')"
    :actionHref="route('login')"
    :actionLabel="__('app.errors.sign_in')"
    :secondaryHref="url('/')"
    :secondaryLabel="__('app.errors.back_home')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="8" cy="15" r="4"/>
        <path d="m11 12 8-8"/>
        <path d="m16 4 3 3"/>
        <path d="m19 7 2-2"/>
    </svg>
</x-error-page>
