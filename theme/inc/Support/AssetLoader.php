<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class AssetLoader
{
    private const SCRIPT_HANDLE = 'imperial-future-main';
    private const STYLE_HANDLE = 'imperial-future-main';

    public static function registerHooks(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
        add_filter('script_loader_tag', [self::class, 'addModuleType'], 10, 3);
        add_filter('body_class', [self::class, 'addBodyClasses']);
        add_action('wp_head', [self::class, 'preloadBuiltStyles'], 2);
        add_filter('style_loader_tag', [CriticalStyles::class, 'deferStylesheet'], 10, 4);
    }

    public static function enqueue(): void
    {
        $manifest = self::readManifest();
        $entry = $manifest['src/js/main.js'] ?? null;

        if (! is_array($entry)) {
            wp_enqueue_style(self::STYLE_HANDLE, get_stylesheet_uri(), [], self::themeVersion());
            return;
        }

        CriticalStyles::enqueue();
        foreach (($entry['css'] ?? []) as $index => $cssFile) {
            wp_enqueue_style(
                self::STYLE_HANDLE . '-' . $index,
                self::assetUrl($cssFile),
                [],
                self::assetVersion($cssFile)
            );
        }

        $pageStyles = [];
        if (is_singular('post')) {
            $pageStyles[] = 'comments';
        }
        if (is_page_template('page-account.php')) {
            $pageStyles[] = 'account';
        }
        foreach ($pageStyles as $name) {
            $file = $manifest['src/scss/' . $name . '.scss']['file'] ?? '';
            if ($file !== '') {
                wp_enqueue_style('imperial-future-' . $name, self::assetUrl($file), [], self::assetVersion($file));
            }
        }

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            self::assetUrl((string) $entry['file']),
            [],
            self::assetVersion((string) $entry['file']),
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );

        wp_localize_script(self::SCRIPT_HANDLE, 'imperialFuture', [
            'homeUrl' => home_url('/'),
            'themeUrl' => get_template_directory_uri(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'searchNonce' => wp_create_nonce('imperial_live_search'),
            'ratingNonce' => wp_create_nonce('imperial_rate_post'),
            'commentVoteNonce' => wp_create_nonce('imperial_vote_comment'),
            'commentPreviewNonce' => wp_create_nonce('imperial_markdown_preview'),
            'bookmarkNonce' => wp_create_nonce('imperial_toggle_bookmark'),
            'searchExamples' => ThemeOptions::lines('imperial_search_examples'),
            'messages' => FrontendStrings::all(),
            'i18n' => [
                'networkError' => __('Не удалось связаться с сервером. Повтори попытку.', 'imperial-future'),
                'noticeClose' => __('Закрыть уведомление', 'imperial-future'),
                'searchEmpty' => __('В архиве пока нет таких листов.', 'imperial-future'),
                'searchLoading' => __('Архивная машина ищет...', 'imperial-future'),
                'ratingSaved' => __('Печать принята.', 'imperial-future'),
                'commentVoteSaved' => __('Оценка комментария принята.', 'imperial-future'),
                'bookmarkSaved' => __('Сохранено', 'imperial-future'),
                'bookmarkRemoved' => __('Закладка', 'imperial-future'),
            ],
        ]);
    }

    public static function addModuleType(string $tag, string $handle, string $src): string
    {
        if ($handle !== self::SCRIPT_HANDLE) {
            return $tag;
        }

        return wp_get_script_tag([
            'type' => 'module',
            'src' => $src,
            'id' => $handle . '-js',
        ]);
    }

    public static function addBodyClasses(array $classes): array
    {
        $classes[] = 'imperial-theme';

        if (is_singular('post')) {
            $classes[] = 'has-reading-progress';
        }

        return $classes;
    }

    public static function preloadBuiltStyles(): void
    {
        if (is_front_page() && ThemeOptions::get('imperial_hero_mode') === 'slider' && ThemeOptions::get('imperial_hero_slider_background_mode') === 'image') {
            $id = (int) ThemeOptions::get('imperial_hero_slider_background_image');
            $image = wp_get_attachment_image_url($id, 'large');
            if ($image) {
                printf('<link rel="preload" href="%s" as="image" media="(max-width:759px)" fetchpriority="high">' . PHP_EOL, esc_url(ThemeOptions::mediaUrl('imperial_hero_slider_background_image', 'imperial-backdrop-mobile')));
                printf('<link rel="preload" href="%s" as="image" imagesrcset="%s" imagesizes="100vw" media="(min-width:760px)" fetchpriority="high">' . PHP_EOL, esc_url($image), esc_attr(wp_get_attachment_image_srcset($id, 'large') ?: ''));
            }
        }

        $manifest = self::readManifest();
        $entry = $manifest['src/js/main.js'] ?? null;

        if (! is_array($entry)) {
            return;
        }

        foreach (CriticalStyles::isLoaded() ? [] : ($entry['css'] ?? []) as $cssFile) {
            $url = add_query_arg('ver', self::assetVersion((string) $cssFile), self::assetUrl((string) $cssFile));

            printf(
                '<link rel="preload" href="%s" as="style">' . PHP_EOL,
                esc_url($url)
            );
        }

        $heading = strtolower(ThemeOptions::get('imperial_font_heading')) . '-cyrillic-400-normal-';
        $body = match (ThemeOptions::get('imperial_font_body')) {
            'PT Sans' => 'pt-sans-cyrillic-400-normal-',
            'IBM Plex Sans' => 'ibm-plex-sans-cyrillic-400-normal-',
            default => 'inter-cyrillic-wght-normal-',
        };
        foreach ([$heading, $body, str_replace('-cyrillic-', '-latin-', $heading), str_replace('-cyrillic-', '-latin-', $body)] as $prefix) {
            $fontFiles = [];
            foreach (($entry['assets'] ?? []) as $file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                if (str_starts_with(basename($file), $prefix) && ($extension === 'woff2' || ($prefix !== $body && $prefix !== str_replace('-cyrillic-', '-latin-', $body) && $extension === 'woff'))) {
                    $fontFiles[$file] = filesize(get_template_directory() . '/assets/dist/' . $file);
                }
            }
            asort($fontFiles);
            $file = array_key_first($fontFiles);
            if ($file !== null) {
                printf('<link rel="preload" href="%s" as="font" type="font/%s" crossorigin>' . PHP_EOL, esc_url(self::assetUrl($file)), esc_attr(pathinfo($file, PATHINFO_EXTENSION)));
            }
        }
    }

    public static function readManifest(): array
    {
        static $manifest = null;
        if (is_array($manifest)) {
            return $manifest;
        }
        $manifestPath = get_template_directory() . '/assets/dist/.vite/manifest.json';

        if (! is_readable($manifestPath)) {
            return $manifest = [];
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);

        return $manifest = is_array($decoded) ? $decoded : [];
    }

    private static function assetUrl(string $file): string
    {
        return get_template_directory_uri() . '/assets/dist/' . ltrim($file, '/');
    }

    private static function assetVersion(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    private static function themeVersion(): string
    {
        return (string) wp_get_theme()->get('Version');
    }
}
