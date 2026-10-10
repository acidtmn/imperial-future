<?php
declare(strict_types=1);

namespace ImperialFuture\Security;

if (! defined('ABSPATH')) {
    exit;
}

final class SecurityHeaders
{
    public static function registerHooks(): void
    {
        add_action('send_headers', [self::class, 'send']);
    }

    public static function send(): void
    {
        if (is_admin() || headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff', true);
        header('X-Frame-Options: SAMEORIGIN', true);
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()', true);
    }
}
