<?php
declare(strict_types=1);

namespace ImperialFuture\Media;

if (! defined('ABSPATH')) {
    exit;
}

final class ResponsiveImageFormats
{
    public static function registerHooks(): void
    {
        add_filter('image_editor_output_format', [self::class, 'outputFormats']);
        add_filter('wp_editor_set_quality', [self::class, 'quality'], 10, 2);
    }

    public static function outputFormats(array $formats): array
    {
        if (! wp_image_editor_supports(['mime_type' => 'image/avif'])) {
            return $formats;
        }

        // Штатный редактор сохраняет оригинал; настройки формата других расширений имеют приоритет.
        return $formats + [
            'image/jpeg' => 'image/avif',
            'image/png' => 'image/avif',
            'image/webp' => 'image/avif',
        ];
    }

    public static function quality(int $quality, string $mimeType): int
    {
        if ($mimeType !== 'image/avif') {
            return $quality;
        }

        return min($quality, 60);
    }
}
