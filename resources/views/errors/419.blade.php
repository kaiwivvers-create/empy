<x-error-page code="419"
    :title="__('app.errors.419.title')"
    :message="__('app.errors.419.message')"
    :hint="__('app.errors.419.hint')"
    :actionHref="route('login')"
    :actionLabel="__('app.errors.sign_in')"
    :secondaryHref="url('/')"
    :secondaryLabel="__('app.errors.back_home')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2h12M6 22h12"/>
        <path d="M7 2c0 4 2 6 5 7 3-1 5-3 5-7"/>
        <path d="M7 22c0-4 2-6 5-7 3 1 5 3 5 7"/>
    </svg>
</x-error-page>
