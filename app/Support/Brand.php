<?php

namespace App\Support;

use App\Models\Setting;

class Brand
{
    public static function name(): string
    {
        return Setting::get('brand_name') ?: config('app.name', 'Empy');
    }

    public static function tagline(): string
    {
        return Setting::get('brand_tagline') ?: __('app.brand_sub');
    }

    public static function logoPath(): ?string
    {
        return Setting::get('brand_logo_path');
    }

    public static function logoUrl(): ?string
    {
        $path = static::logoPath();

        return $path ? asset('storage/'.$path) : null;
    }
}
