<?php
declare(strict_types=1);

namespace ImperialFuture\Content;

if (! defined('ABSPATH')) {
    exit;
}

final class FileBlockEnhancer
{
    public static function registerHooks(): void
    {
        add_filter('render_block_core/file', [self::class, 'render'], 10, 2);
    }

    public static function render(string $blockContent, array $block): string
    {
        $file = self::resolveFile($blockContent, $block);

        if ($file['url'] === '') {
            return $blockContent;
        }

        return self::renderCard($file, $block);
    }

    private static function resolveFile(string $blockContent, array $block): array
    {
        $attributes = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $attachmentId = (int) ($attributes['id'] ?? 0);
        $url = self::resolveUrl($attributes, $attachmentId, $blockContent);
        $path = $attachmentId > 0 ? (string) get_attached_file($attachmentId) : '';
        $name = self::resolveName($attributes, $attachmentId, $url, $path);
        $extension = self::resolveExtension($path !== '' ? $path : $url);
        $bytes = $path !== '' && is_readable($path) ? (int) filesize($path) : 0;

        return [
            'url' => $url,
            'name' => $name,
            'extension' => $extension,
            'meta' => self::formatMeta($extension, $bytes),
            'downloadLabel' => (string) ($attributes['downloadButtonText'] ?? __('Скачать', 'imperial-future')),
            'showDownload' => (bool) ($attributes['showDownloadButton'] ?? true),
            'target' => (string) ($attributes['textLinkTarget'] ?? ''),
        ];
    }

    private static function resolveUrl(array $attributes, int $attachmentId, string $blockContent): string
    {
        $url = esc_url_raw((string) ($attributes['href'] ?? $attributes['textLinkHref'] ?? ''));

        if ($url === '' && $attachmentId > 0) {
            $attachmentUrl = wp_get_attachment_url($attachmentId);
            $url = is_string($attachmentUrl) ? esc_url_raw($attachmentUrl) : '';
        }

        return $url !== '' ? $url : self::extractFirstHref($blockContent);
    }

    private static function resolveName(array $attributes, int $attachmentId, string $url, string $path): string
    {
        $name = trim((string) ($attributes['fileName'] ?? ''));

        if ($name !== '') {
            return $name;
        }

        if ($path !== '') {
            return wp_basename($path);
        }

        if ($attachmentId > 0) {
            $title = get_the_title($attachmentId);

            if ($title !== '') {
                return $title;
            }
        }

        $urlPath = (string) wp_parse_url($url, PHP_URL_PATH);

        return $urlPath !== '' ? wp_basename($urlPath) : __('Файл архива', 'imperial-future');
    }

    private static function resolveExtension(string $pathOrUrl): string
    {
        $path = (string) wp_parse_url($pathOrUrl, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo($path !== '' ? $path : $pathOrUrl, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'file';
    }

    private static function extractFirstHref(string $html): string
    {
        if (! preg_match('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return '';
        }

        return esc_url_raw(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8'));
    }

    private static function formatMeta(string $extension, int $bytes): string
    {
        $parts = [strtoupper($extension)];

        if ($bytes > 0) {
            $parts[] = size_format($bytes, 1);
        }

        return implode(' · ', $parts);
    }

    private static function renderCard(array $file, array $block): string
    {
        $classes = self::classes($file['extension'], $block);
        $target = $file['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
            <a class="imperial-file-card__main" href="<?php echo esc_url($file['url']); ?>"<?php echo $target; ?>>
                <span class="imperial-file-card__icon" aria-hidden="true"><?php echo esc_html(strtoupper($file['extension'])); ?></span>
                <span class="imperial-file-card__details">
                    <strong class="imperial-file-card__name"><?php echo esc_html($file['name']); ?></strong>
                    <span class="imperial-file-card__meta"><?php echo esc_html($file['meta']); ?></span>
                </span>
            </a>
            <?php if ($file['showDownload']) : ?>
                <a class="imperial-file-card__download" href="<?php echo esc_url($file['url']); ?>" download>
                    <?php echo esc_html($file['downloadLabel']); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php

        return trim((string) ob_get_clean());
    }

    private static function classes(string $extension, array $block): array
    {
        $attributes = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $classes = [
            'wp-block-file',
            'imperial-file-card',
            'imperial-file-card--' . sanitize_html_class($extension),
        ];

        if (! empty($attributes['align'])) {
            $classes[] = 'align' . sanitize_html_class((string) $attributes['align']);
        }

        return $classes;
    }
}
