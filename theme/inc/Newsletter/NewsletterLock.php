<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

final class NewsletterLock
{
    public static function acquire(string $scope): string|false
    {
        $key = 'imperial_newsletter_lock_' . md5($scope);
        $existing = get_option($key);

        // Зависшую обработку продолжаем после запаса на время отправки.
        if (is_array($existing) && (int) $existing['expires'] < time()) {
            delete_option($key);
        }

        $token = bin2hex(random_bytes(16));

        return add_option($key, ['token' => $token, 'expires' => time() + 15 * MINUTE_IN_SECONDS], '', false) ? $token : false;
    }

    public static function release(string $scope, string $token): void
    {
        $key = 'imperial_newsletter_lock_' . md5($scope);
        $existing = get_option($key);

        if (is_array($existing) && hash_equals((string) $existing['token'], $token)) {
            delete_option($key);
        }
    }
}
