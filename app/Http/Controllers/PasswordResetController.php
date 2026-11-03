<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Queue a reset request for an administrator instead of emailing a link.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = Str::lower(trim($request->string('email')->toString()));
        $key = 'password-request:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => __('app.auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 300);

        // Unknown or inactive accounts get the same confirmation so the page
        // cannot be used to discover which emails exist.
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user && $user->is_active) {
            $pending = PasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->pending()
                ->first();

            if ($pending) {
                $pending->update(['email' => $user->email, 'requested_at' => now()]);
            } else {
                PasswordResetRequest::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => PasswordResetRequest::STATUS_PENDING,
                    'requested_at' => now(),
                ]);
            }
        }

        return redirect()
            ->route('password.request')
            ->with('success', __('app.auth.request_submitted'));
    }
}
