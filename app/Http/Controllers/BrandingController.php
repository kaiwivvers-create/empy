<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ActivityLogger;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function edit(): View
    {
        return view('settings.branding', [
            'name' => Brand::name(),
            'tagline' => Brand::tagline(),
            'logoUrl' => Brand::logoUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:60'],
            'brand_tagline' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logo_data' => ['nullable', 'string'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        Setting::put('brand_name', $data['brand_name']);
        Setting::put('brand_tagline', $data['brand_tagline'] ?? null);

        $current = Setting::get('brand_logo_path');

        if ($request->boolean('remove_logo') && $current) {
            Storage::disk('public')->delete($current);
            Setting::put('brand_logo_path', null);
        }

        if (filled($data['logo_data'] ?? null)) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }

            Setting::put('brand_logo_path', $this->storeCroppedLogo($data['logo_data']));
        } elseif ($request->hasFile('logo')) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }

            Setting::put('brand_logo_path', $request->file('logo')->store('branding', 'public'));
        }

        ActivityLogger::record('updated', 'Brand settings updated: '.$data['brand_name']);

        return back()->with('success', __('app.branding.success_updated'));
    }

    private function storeCroppedLogo(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'logo_data' => __('app.profile.invalid_image'),
            ]);
        }

        $binary = base64_decode(substr($dataUrl, strlen($matches[0])), true);
        $size = $binary === false ? false : @getimagesizefromstring($binary);

        if ($binary === false || strlen($binary) > 3 * 1024 * 1024 || $size === false
            || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages([
                'logo_data' => __('app.profile.invalid_image'),
            ]);
        }

        $path = 'branding/logo.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
