<?php
declare(strict_types=1);

namespace ImperialFuture\Security;

if (! defined('ABSPATH')) {
    exit;
}

final class RateLimiter
{
    private const KEY_PREFIX = 'imperial_rate_limit_';

    public static function allow(string $scope, string $identity, int $limit, int $windowSeconds): bool
    {
        $limit = max(1, $limit);
        $windowSeconds = max(60, $windowSeconds);
        $key = self::key($scope, $identity);
        $now = time();
        $bucket = get_transient($key);

        if (! is_array($bucket) || (int) ($bucket['expires_at'] ?? 0) <= $now) {
            $bucket = [
                'count' => 0,
                'expires_at' => $now + $windowSeconds,
            ];
        }

        $bucket['count'] = (int) $bucket['count'] + 1;
        set_transient($key, $bucket, max(1, (int) $bucket['expires_at'] - $now));

        return (int) $bucket['count'] <= $limit;
    }

    public static function clientIdentity(): string
    {
        $address = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        return 'ip:' . ($address !== '' ? $address : 'unknown');
    }

    public static function userOrClientIdentity(): string
    {
        $userId = get_current_user_id();

        if ($userId > 0) {
            return 'user:' . $userId;
        }

        return self::clientIdentity();
    }

    private static function key(string $scope, string $identity): string
    {
        return self::KEY_PREFIX . md5(sanitize_key($scope) . '|' . $identity);
    }
}
