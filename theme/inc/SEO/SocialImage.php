<?php
declare(strict_types=1);

namespace ImperialFuture\SEO;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class SocialImage
{
    public static function url(int $postId = 0): string
    {
        $attachmentId = $postId > 0 ? (int) get_post_thumbnail_id($postId) : 0;
        if ($attachmentId > 0 && in_array(get_post_mime_type($attachmentId), ['image/jpeg', 'image/png', 'image/webp', 'image/avif'], true)) {
            $image = wp_get_attachment_image_url($attachmentId, 'imperial-hero');
            if ($image) {
                return $image;
            }
        }

        return ThemeOptions::mediaUrl('imperial_seo_og_image') ?: get_template_directory_uri() . '/assets/images/og-imperial.png';
    }
}
