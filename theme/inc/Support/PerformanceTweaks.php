<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class PerformanceTweaks
{
    public static function registerHooks(): void
    {
        add_action('init', [self::class, 'disableEmojiAssets']);
        add_filter('emoji_svg_url', '__return_false');
    }

    public static function disableEmojiAssets(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }
}
