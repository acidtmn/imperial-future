<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class ThemeCodeSanitizer
{
    public static function sanitize(mixed $value): string
    {
        $code = is_string($value) ? $value : '';

        if (current_user_can('unfiltered_html')) {
            return $code;
        }

        return wp_kses_post($code);
    }
}
