<?php
declare(strict_types=1);

namespace ImperialFuture\Media;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class GeneratedFeaturedImageHooks
{
    private const META_KEY = '_imperial_auto_featured_image';

    public static function registerHooks(): void
    {
        add_action('save_post_post', [self::class, 'ensureImage'], 30, 3);
    }

    public static function ensureImage(int $postId, \WP_Post $post, bool $update): void
    {
        if (! ThemeOptions::enabled('imperial_auto_featured_image_enabled')) {
            return;
        }

        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || $post->post_status === 'auto-draft') {
            return;
        }

        if (has_post_thumbnail($postId) || get_post_meta($postId, self::META_KEY, true) !== '') {
            return;
        }

        $attachmentId = GeneratedFeaturedImageService::generateForPost($postId);

        if ($attachmentId <= 0) {
            return;
        }

        set_post_thumbnail($postId, $attachmentId);
        update_post_meta($postId, self::META_KEY, (string) $attachmentId);
    }
}
