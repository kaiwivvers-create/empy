<x-error-page code="503"
    :title="__('app.errors.503.title')"
    :message="__('app.errors.503.message')"
    :actionHref="url()->current()"
    :actionLabel="__('app.errors.reload')">
    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.5-2.5Z"/>
        <path d="M4 4h4M4 8h3"/>
    </svg>
</x-error-page>
