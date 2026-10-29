<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Captcha
{
    public const SESSION_KEY = 'math_captcha';

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    /**
     * Build a fresh arithmetic challenge and remember the answer in the session.
     *
     * @return array{token: string, question: string}
     */
    public static function challenge(): array
    {
        $left = random_int(2, 9);
        $right = random_int(2, 9);
        $subtract = random_int(0, 1) === 1;
        $answer = $subtract ? $left - $right : $left + $right;

        if ($subtract && $answer < 0) {
            [$left, $right] = [$right, $left];
            $answer = $left - $right;
        }

        $token = Str::random(32);

        session([static::SESSION_KEY => [
            'token' => $token,
            'answer' => $answer,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]]);

        return [
            'token' => $token,
            'question' => $left.' '.($subtract ? '−' : '+').' '.$right.' = ?',
        ];
    }

    /**
     * Validate the submitted challenge. Returns an app.lang key on failure.
     *
     * The math challenge is always accepted when submitted (it works offline).
     * Turnstile is verified when it is configured and no math answer was posted.
     */
    public static function verify(Request $request): ?string
    {
        $token = (string) $request->input('math_token', '');
        $answer = $request->input('math_answer');

        if ($token !== '' && $answer !== null && $answer !== '') {
            $stored = $request->session()->pull(static::SESSION_KEY);

            if (! is_array($stored) || ! hash_equals((string) $stored['token'], $token)) {
                return 'app.auth.captcha_expired';
            }

            if (($stored['expires_at'] ?? 0) < now()->timestamp) {
                return 'app.auth.captcha_expired';
            }

            if ((string) (int) $answer !== (string) $stored['answer']) {
                return 'app.auth.captcha_failed';
            }

            return null;
        }

        if (! static::turnstileEnabled()) {
            return 'app.auth.captcha_required';
        }

        $response = $request->input('cf-turnstile-response');

        if (blank($response)) {
            return 'app.auth.captcha_required';
        }

        try {
            $result = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $response,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable) {
            return 'app.auth.captcha_offline';
        }

        if ($result->failed() || ! $result->json('success')) {
            return 'app.auth.captcha_failed';
        }

        return null;
    }
}
