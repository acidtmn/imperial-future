<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class SvgUploadSupport
{
    public static function registerHooks(): void
    {
        add_filter('upload_mimes', [self::class, 'allowSvgMime']);
        add_filter('wp_check_filetype_and_ext', [self::class, 'fixSvgFileType'], 10, 5);
    }

    public static function allowSvgMime(array $mimes): array
    {
        if (current_user_can('manage_options')) {
            $mimes['svg'] = 'image/svg+xml';
        }

        return $mimes;
    }

    public static function fixSvgFileType(array $data, string $file, string $filename, ?array $mimes = null, ?string $realMime = null): array
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'svg') {
            return $data;
        }

        if (! current_user_can('manage_options') || ! SvgSanitizer::isSafeFile($file)) {
            return [
                'ext' => false,
                'type' => false,
                'proper_filename' => false,
            ];
        }

        $data['ext'] = 'svg';
        $data['type'] = 'image/svg+xml';

        return $data;
    }
}
