<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use ImperialFuture\Security\RateLimiter;

if (! defined('ABSPATH')) {
    exit;
}

final class MarkdownPreviewController
{
    public static function registerHooks(): void
    {
        add_action('wp_ajax_imperial_markdown_preview', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_imperial_markdown_preview', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        check_ajax_referer('imperial_markdown_preview', 'nonce');
        $content = wp_unslash($_POST['content'] ?? '');
        if (! is_string($content) || strlen($content) > 20000) {
            wp_send_json_error(null, 422);
        }
        if (! RateLimiter::allow('markdown_preview', RateLimiter::userOrClientIdentity(), 60, MINUTE_IN_SECONDS)) {
            wp_send_json_error(null, 429);
        }
        wp_send_json_success(['html' => MarkdownService::render($content)]);
    }
}
