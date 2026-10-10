<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class CounterRenderer
{
    public static function registerHooks(): void
    {
        add_action('wp_head', [self::class, 'renderHeadCode'], 90);
        add_action('wp_body_open', [self::class, 'renderBodyCode'], 5);
    }

    public static function renderHeadCode(): void
    {
        echo ThemeOptions::code('imperial_counter_head_code');
    }

    public static function renderBodyCode(): void
    {
        echo ThemeOptions::code('imperial_counter_body_code');
    }
}
