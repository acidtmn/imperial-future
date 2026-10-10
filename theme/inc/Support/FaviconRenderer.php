<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class FaviconRenderer
{
    public static function registerHooks(): void
    {
        add_action('wp_head', [self::class, 'render'], 3);
    }

    public static function render(): void
    {
        $favicon = ThemeOptions::mediaUrl('imperial_favicon');

        if ($favicon === '') {
            if (has_site_icon()) {
                return;
            }

            $base = get_template_directory_uri() . '/assets/images/brand/';
            echo '<link rel="icon" href="' . esc_url($base . 'favicon.svg') . '" type="image/svg+xml">' . PHP_EOL;
            echo '<link rel="icon" href="' . esc_url($base . 'favicon-32.png') . '" sizes="32x32" type="image/png">' . PHP_EOL;
            echo '<link rel="apple-touch-icon" href="' . esc_url($base . 'apple-touch-icon.png') . '" sizes="180x180">' . PHP_EOL;
            return;
        }

        echo '<link rel="icon" href="' . esc_url($favicon) . '">' . PHP_EOL;
    }
}
