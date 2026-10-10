<?php
declare(strict_types=1);

namespace ImperialFuture\Slug;

if (! defined('ABSPATH')) {
    exit;
}

final class SlugTransliterationHooks
{
    public static function registerHooks(): void
    {
        add_filter('sanitize_title', [self::class, 'sanitizeTitle'], 9, 3);
        add_filter('wp_insert_post_data', [self::class, 'sanitizePostData'], 20, 2);
        add_filter('wp_insert_term_data', [self::class, 'sanitizeTermData'], 20, 3);
    }

    public static function sanitizeTitle(mixed $title, mixed $rawTitle = '', mixed $context = 'save'): string
    {
        $source = (string) ($title !== '' ? $title : $rawTitle);

        if ($source === '') {
            return (string) $title;
        }

        $latin = SlugTransliterator::toLatin($source);

        return $latin !== '' ? $latin : (string) $title;
    }

    public static function sanitizePostData(array $data, array $postarr): array
    {
        if (! in_array((string) ($data['post_type'] ?? ''), ['post', 'page'], true)) {
            return $data;
        }

        $data['post_name'] = self::sanitizeStoredSlug((string) ($data['post_name'] ?? ''));

        return $data;
    }

    public static function sanitizeTermData(array $data, string $taxonomy, array $args): array
    {
        $data['slug'] = self::sanitizeStoredSlug((string) ($data['slug'] ?? ''));

        return $data;
    }

    private static function sanitizeStoredSlug(string $slug): string
    {
        if ($slug === '') {
            return $slug;
        }

        $decoded = rawurldecode($slug);

        if (preg_match('/[^\x00-\x7F]/u', $decoded) !== 1) {
            return $slug;
        }

        $latinSlug = SlugTransliterator::toSlug($decoded);

        return $latinSlug !== '' ? $latinSlug : $slug;
    }
}
