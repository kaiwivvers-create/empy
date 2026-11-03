<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('updateProfile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'avatar' => ['nullable', 'string'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $before = $user->getAttributes();

        if ($request->boolean('remove_avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            if ($user->employee?->photo_path) {
                Storage::disk('public')->delete($user->employee->photo_path);
                $user->employee->update(['photo_path' => null]);
            }

            $data['avatar_path'] = null;
        }

        if (filled($data['avatar'] ?? null)) {
            $path = $this->storeAvatar($user->id, $data['avatar']);

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Keep the linked employee photo identical to the account avatar.
            if ($user->employee) {
                if ($user->employee->photo_path && $user->employee->photo_path !== $path) {
                    Storage::disk('public')->delete($user->employee->photo_path);
                }

                $user->employee->update(['photo_path' => $path]);
            }

            $data['avatar_path'] = $path;
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...(array_key_exists('avatar_path', $data) ? ['avatar_path' => $data['avatar_path']] : []),
        ]);

        if ($user->employee) {
            $user->employee->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        }

        ActivityLogger::record(
            'updated',
            'Profile updated: '.$user->email,
            $user,
            ActivityLogger::changes($user, $before),
        );

        return back()->with('success', __('app.profile.success_updated'));
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $data['password']]);

        ActivityLogger::record('updated', 'Password changed: '.$request->user()->email, $request->user(), []);

        return back()->with('success', __('app.profile.success_password'));
    }

    private function storeAvatar(int $userId, string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'avatar' => __('app.profile.invalid_image'),
            ])->errorBag('updateProfile');
        }

        $binary = base64_decode(substr($dataUrl, strlen($matches[0])), true);

        if ($binary === false || strlen($binary) > 3 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'avatar' => __('app.profile.invalid_image'),
            ])->errorBag('updateProfile');
        }

        $size = @getimagesizefromstring($binary);

        if ($size === false || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages([
                'avatar' => __('app.profile.invalid_image'),
            ])->errorBag('updateProfile');
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $path = 'avatars/user-'.$userId.'.'.$extension;

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
