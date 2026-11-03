<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Captcha
{
    public const SESSION_KEY = 'math_captcha';

    public const PHYSICS_SESSION_KEY = 'physics_captcha';

    public const TYPES = ['math', 'physics'];

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    /**
     * Build a fresh local challenge and remember the answer in the session.
     *
     * @return array{type: string, token: string, question: string}
     */
    public static function challenge(string $type = 'math'): array
    {
        return $type === 'physics' ? static::physicsChallenge() : static::mathChallenge();
    }

    /**
     * Both local challenges, so the login page can offer a choice.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function challenges(): array
    {
        return [
            'math' => static::mathChallenge(),
            'physics' => static::physicsChallenge(),
        ];
    }

    /**
     * @return array{type: string, token: string, question: string}
     */
    private static function mathChallenge(): array
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
            'type' => 'math',
            'token' => $token,
            'answer' => $answer,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]]);

        return [
            'type' => 'math',
            'token' => $token,
            'question' => $left.' '.($subtract ? '−' : '+').' '.$right.' = ?',
        ];
    }

    /**
     * @return array{type: string, token: string, topic: string, question: string, options: list<string>}
     */
    private static function physicsChallenge(): array
    {
        $question = PhysicsCaptcha::question();
        $token = Str::random(32);

        session([static::PHYSICS_SESSION_KEY => [
            'type' => 'physics',
            'token' => $token,
            'answer' => $question['answer'],
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]]);

        return [
            'type' => 'physics',
            'token' => $token,
            'topic' => __('app.auth.captcha_topics.'.$question['topic']),
            'question' => $question['question'],
            'options' => $question['options'],
        ];
    }

    /**
     * Validate the submitted challenge. Returns an app.lang key on failure.
     *
     * The selected math or physics challenge is always accepted offline.
     * Turnstile is verified when it is configured and no local answer was posted.
     */
    public static function verify(Request $request): ?string
    {
        $type = (string) $request->input('captcha_type', 'math');
        $type = in_array($type, static::TYPES, true) ? $type : 'math';

        $token = (string) $request->input('captcha_token', '');
        $answer = $request->input('captcha_answer');

        if ($token !== '' && $answer !== null && $answer !== '') {
            $sessionKey = $type === 'physics' ? static::PHYSICS_SESSION_KEY : static::SESSION_KEY;
            $stored = $request->session()->pull($sessionKey);

            if (! is_array($stored) || ($stored['type'] ?? null) !== $type) {
                return 'app.auth.captcha_expired';
            }

            if (! hash_equals((string) $stored['token'], $token)) {
                return 'app.auth.captcha_expired';
            }

            if (($stored['expires_at'] ?? 0) < now()->timestamp) {
                return 'app.auth.captcha_expired';
            }

            if (! hash_equals((string) $stored['answer'], (string) $answer)) {
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
