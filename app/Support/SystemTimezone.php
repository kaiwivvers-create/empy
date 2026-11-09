<?php

namespace App\Support;

use App\Models\Setting;

class SystemTimezone
{
    public const DEFAULT = 'Asia/Jakarta';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'Asia/Jakarta' => 'Jakarta (UTC+7)',
            'Asia/Bangkok' => 'Bangkok (UTC+7)',
            'Asia/Singapore' => 'Singapore (UTC+8)',
            'Asia/Kuala_Lumpur' => 'Kuala Lumpur (UTC+8)',
            'Asia/Manila' => 'Manila (UTC+8)',
            'Asia/Tokyo' => 'Tokyo (UTC+9)',
            'Australia/Sydney' => 'Sydney',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'America/New_York' => 'New York',
            'America/Los_Angeles' => 'Los Angeles',
            'UTC' => 'UTC',
        ];
    }

    public static function current(): string
    {
        $timezone = Setting::get('system_timezone', self::DEFAULT);

        return array_key_exists($timezone, self::options()) ? $timezone : self::DEFAULT;
    }
}
