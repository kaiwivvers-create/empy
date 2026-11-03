<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetRequestController extends Controller
{
    public function index(Request $request): View
    {
        $hiddenSuperAdmin = ! $request->user()->isSuperAdmin();

        $pending = PasswordResetRequest::query()
            ->pending()
            ->with(['user.employee.department'])
            ->when($hiddenSuperAdmin, fn ($query) => $query->whereHas('user', fn ($user) => $user->where('role', '!=', 'super_admin')))
            ->orderByDesc('requested_at')
            ->get();

        $history = PasswordResetRequest::query()
            ->where('status', '!=', PasswordResetRequest::STATUS_PENDING)
            ->with(['user.employee', 'handledBy'])
            ->when($hiddenSuperAdmin, fn ($query) => $query->whereHas('user', fn ($user) => $user->where('role', '!=', 'super_admin')))
            ->orderByDesc('handled_at')
            ->limit(25)
            ->get();

        return view('password-requests.index', [
            'pending' => $pending,
            'history' => $history,
        ]);
    }

    /**
     * Set a new password for the requester and close every pending request.
     */
    public function reset(Request $request, PasswordResetRequest $passwordRequest): RedirectResponse
    {
        $target = $this->authorizeRequest($request, $passwordRequest);

        if ($passwordRequest->status !== PasswordResetRequest::STATUS_PENDING) {
            return redirect()->route('password-requests.index')
                ->with('error', __('app.password_requests.already_handled'));
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $target, $data) {
            $target->forceFill([
                'password' => $data['password'],
                'remember_token' => Str::random(60),
            ])->save();

            PasswordResetRequest::query()
                ->where('user_id', $target->id)
                ->pending()
                ->update([
                    'status' => PasswordResetRequest::STATUS_COMPLETED,
                    'handled_at' => now(),
                    'handled_by' => $request->user()->id,
                ]);
        });

        ActivityLogger::record('updated', 'Password reset by administrator for '.$target->email, $target);

        return redirect()->route('password-requests.index')
            ->with('success', __('app.password_requests.success_reset', ['email' => $target->email]));
    }

    /**
     * Close a request without touching the password.
     */
    public function dismiss(Request $request, PasswordResetRequest $passwordRequest): RedirectResponse
    {
        $target = $this->authorizeRequest($request, $passwordRequest);

        if ($passwordRequest->status === PasswordResetRequest::STATUS_PENDING) {
            $passwordRequest->update([
                'status' => PasswordResetRequest::STATUS_DISMISSED,
                'handled_at' => now(),
                'handled_by' => $request->user()->id,
            ]);

            ActivityLogger::record('updated', 'Password reset request dismissed for '.$target->email, $target);
        }

        return redirect()->route('password-requests.index')
            ->with('success', __('app.password_requests.success_dismissed'));
    }

    /**
     * The target account, hidden from admins who cannot manage super admins.
     */
    private function authorizeRequest(Request $request, PasswordResetRequest $passwordRequest): \App\Models\User
    {
        $target = $passwordRequest->user;

        abort_if(! $target, 404);

        if ($target->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(404);
        }

        return $target;
    }
}
