<?php
declare(strict_types=1);

namespace ImperialFuture\Share;

if (! defined('ABSPATH')) {
    exit;
}

final class SocialIcon
{
    private const SLUGS = ['vk', 'ok', 'telegram', 'max', 'email'];

    public static function url(string $slug): string
    {
        if (! in_array($slug, self::SLUGS, true)) {
            return '';
        }

        return get_template_directory_uri() . '/assets/icons/social/' . $slug . '.svg';
    }
}
