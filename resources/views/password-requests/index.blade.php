@extends('layouts.app')

@section('title', __('app.password_requests.title'))

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('app.password_requests.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.password_requests.subtitle') }}</p>
    </div>

    <div class="card mb-5">
        <div class="card-header">
            <div>
                <h3 class="text-sm font-bold text-slate-700">{{ __('app.password_requests.pending') }}</h3>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('app.password_requests.pending_hint') }}</p>
            </div>
            <span class="badge-amber">{{ $pending->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[48rem]">
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.password_requests.requester') }}</th>
                        <th class="w-[11rem]">{{ __('app.password_requests.requested_at') }}</th>
                        <th class="w-[15rem] text-right">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pending as $item)
                        <tr class="table-row">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">
                                        @if ($item->user?->avatarUrl())
                                            <img src="{{ $item->user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            {{ $item->user?->avatarInitials() ?? '?' }}
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-800">{{ $item->user?->name ?? $item->email }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $item->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-500">{{ $item->requested_at?->format('d M Y H:i') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn-primary btn-sm" data-modal-open="password-reset-{{ $item->id }}">
                                        {{ __('app.password_requests.reset') }}
                                    </button>
                                    <form method="POST" action="{{ route('password-requests.dismiss', $item) }}" onsubmit="return confirm('{{ __('app.password_requests.dismiss_confirm') }}')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-secondary btn-sm">{{ __('app.password_requests.dismiss') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-state">{{ __('app.password_requests.empty_pending') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($pending as $item)
        <x-modal-form id="password-reset-{{ $item->id }}"
                      :title="__('app.password_requests.reset_title')"
                      :subtitle="__('app.password_requests.reset_subtitle', ['name' => $item->user?->name ?? $item->email])"
                      :action="route('password-requests.reset', $item)"
                      method="PATCH"
                      :submitLabel="__('app.password_requests.reset')">
            <div>
                <label class="label" for="password-{{ $item->id }}">{{ __('app.auth.new_password') }}</label>
                <input id="password-{{ $item->id }}" name="password" type="password" class="input" required minlength="8" autocomplete="new-password">
            </div>
            <div>
                <label class="label" for="password-confirm-{{ $item->id }}">{{ __('app.auth.confirm_password') }}</label>
                <input id="password-confirm-{{ $item->id }}" name="password_confirmation" type="password" class="input" required minlength="8" autocomplete="new-password">
            </div>
            <p class="text-xs leading-relaxed text-slate-500">{{ __('app.password_requests.new_password_hint') }}</p>
        </x-modal-form>
    @endforeach

    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-bold text-slate-700">{{ __('app.password_requests.history') }}</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="table-stable w-full min-w-[48rem]">
                <thead class="table-head">
                    <tr>
                        <th>{{ __('app.password_requests.requester') }}</th>
                        <th class="w-[9rem]">{{ __('app.password_requests.status') }}</th>
                        <th class="w-[13rem]">{{ __('app.password_requests.handled_by') }}</th>
                        <th class="w-[11rem]">{{ __('app.password_requests.handled_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr class="table-row">
                            <td>
                                <p class="font-semibold text-slate-800">{{ $item->user?->name ?? $item->email }}</p>
                                <p class="text-xs text-slate-500">{{ $item->email }}</p>
                            </td>
                            <td>
                                <span class="{{ $item->status === \App\Models\PasswordResetRequest::STATUS_COMPLETED ? 'badge-green' : 'badge-slate' }}">
                                    {{ __('app.password_requests.statuses.'.$item->status) }}
                                </span>
                            </td>
                            <td class="text-slate-500">{{ $item->handledBy?->name ?? '-' }}</td>
                            <td class="text-slate-500">{{ $item->handled_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">{{ __('app.password_requests.empty_history') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
