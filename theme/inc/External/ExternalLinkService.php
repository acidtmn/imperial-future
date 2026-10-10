<?php
declare(strict_types=1);

namespace ImperialFuture\External;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class ExternalLinkService
{
    public static function shouldWrap(string $url): bool
    {
        if (! ThemeOptions::enabled('imperial_external_exit_enabled')) {
            return false;
        }

        if (! ExternalUrlGuard::isPublicHttpUrl($url)) {
            return false;
        }

        $host = self::host($url);

        if ($host === '' || self::isInternalHost($host)) {
            return false;
        }

        foreach (ThemeOptions::lines('imperial_external_exit_bypass_masks') as $mask) {
            if (self::hostMatches($host, $mask)) {
                return false;
            }
        }

        return true;
    }

    public static function wrapUrl(string $url): string
    {
        if (! self::shouldWrap($url)) {
            return $url;
        }

        return add_query_arg([
            'imperial_external_exit' => '1',
            'to' => rawurlencode($url),
        ], home_url('/'));
    }

    public static function host(string $url): string
    {
        $host = wp_parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower(preg_replace('/^www\./i', '', $host)) : '';
    }

    private static function isInternalHost(string $host): bool
    {
        return $host === self::host(home_url('/'));
    }

    private static function hostMatches(string $host, string $mask): bool
    {
        $mask = strtolower(trim($mask));

        if ($mask === '') {
            return false;
        }

        $mask = preg_replace('/^https?:\/\//', '', $mask);
        $mask = preg_replace('/\/.*$/', '', (string) $mask);
        $mask = preg_replace('/^www\./', '', (string) $mask);

        if ($mask === $host) {
            return true;
        }

        $pattern = '#^' . str_replace('\*', '.*', preg_quote((string) $mask, '#')) . '$#i';

        return (bool) preg_match($pattern, $host);
    }
}
