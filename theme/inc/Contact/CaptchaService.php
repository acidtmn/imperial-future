<?php
declare(strict_types=1);

namespace ImperialFuture\Contact;

if (! defined('ABSPATH')) {
    exit;
}

final class CaptchaService
{
    private const TRANSIENT_PREFIX = 'imperial_contact_captcha_';
    private const LIFETIME = 600;

    public static function create(): array
    {
        $token = wp_generate_password(32, false, false);
        $challenge = [
            'code' => self::createChallenge(),
            'target' => random_int(32, 82),
            'created_at' => time(),
        ];

        set_transient(self::transientKey($token), $challenge, self::LIFETIME);

        return [
            'token' => $token,
            'url' => admin_url('admin-post.php?action=imperial_contact_captcha_image&token=' . rawurlencode($token)),
        ];
    }

    public static function payload(string $token): array
    {
        $challenge = get_transient(self::transientKey($token));

        return is_array($challenge) ? $challenge : [];
    }

    public static function challenge(string $token): string
    {
        $challenge = self::payload($token);

        return is_string($challenge['code'] ?? null) ? $challenge['code'] : '';
    }

    public static function validate(string $token, string $answer, string $dragValue = ''): bool
    {
        $challenge = self::payload($token);

        if ($challenge === []) {
            return false;
        }

        delete_transient(self::transientKey($token));

        if (! is_numeric($dragValue) || time() - (int) ($challenge['created_at'] ?? time()) < 2) {
            return false;
        }

        $target = self::targetPercent((int) ($challenge['target'] ?? -1000));
        $drag = max(0, min(100, (float) str_replace(',', '.', $dragValue)));

        return $target >= 0 && abs($target - $drag) <= 5.5;
    }

    private static function createChallenge(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $challenge = '';

        for ($i = 0; $i < 5; $i++) {
            $challenge .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $challenge;
    }

    private static function normalize(string $value): string
    {
        $value = preg_replace('/\s+/', '', $value) ?? '';

        return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
    }

    private static function transientKey(string $token): string
    {
        return self::TRANSIENT_PREFIX . sanitize_key($token);
    }

    private static function targetPercent(int $target): int
    {
        if ($target > 100) {
            return max(0, min(100, (int) round($target / 360 * 100)));
        }

        return max(0, min(100, $target));
    }
}
