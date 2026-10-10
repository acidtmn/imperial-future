<?php
declare(strict_types=1);

namespace ImperialFuture\External;

if (! defined('ABSPATH')) {
    exit;
}

final class ExternalUrlGuard
{
    public static function isPublicHttpUrl(string $url): bool
    {
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);
        $host = self::host($url);

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        return self::hostLooksPublic($host);
    }

    public static function isSafeMetadataTarget(string $url): bool
    {
        if (! self::isPublicHttpUrl($url)) {
            return false;
        }

        $host = self::host($url);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        if (! function_exists('dns_get_record')) {
            return false;
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);

        if (! is_array($records) || $records === []) {
            return false;
        }

        foreach ($records as $record) {
            $ip = (string) ($record['ip'] ?? $record['ipv6'] ?? '');

            if ($ip === '' || ! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function host(string $url): string
    {
        $host = wp_parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower(trim($host, " \t\n\r\0\x0B[].")) : '';
    }

    private static function hostLooksPublic(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        return true;
    }

    private static function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
