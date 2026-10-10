<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class ThemeSetup
{
    public static function registerHooks(): void
    {
        add_action('after_setup_theme', [self::class, 'setup']);
    }

    public static function setup(): void
    {
        load_theme_textdomain('imperial-future', get_template_directory() . '/languages');

        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('responsive-embeds');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        add_theme_support('align-wide');
        add_theme_support('custom-line-height');
        add_theme_support('custom-spacing');
        add_theme_support('custom-units', ['px', 'rem', 'em', '%', 'vh', 'vw']);
        add_theme_support('appearance-tools');
        add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script']);
        add_theme_support('custom-logo', [
            'height' => 96,
            'width' => 320,
            'flex-height' => true,
            'flex-width' => true,
        ]);

        add_image_size('imperial-card', 920, 0, false);
        add_image_size('imperial-hero', 1640, 0, false);

        register_nav_menus([
            'primary' => __('Главная навигация', 'imperial-future'),
            'footer' => __('Нижняя навигация', 'imperial-future'),
        ]);

        foreach (self::editorStyles() as $style) {
            add_editor_style($style);
        }
    }

    private static function editorStyles(): array
    {
        $manifest = AssetLoader::readManifest();
        $entry = $manifest['src/js/main.js'] ?? null;

        if (! is_array($entry)) {
            return ['style.css'];
        }

        $styles = [];

        foreach (($entry['css'] ?? []) as $cssFile) {
            $styles[] = 'assets/dist/' . ltrim((string) $cssFile, '/');
        }

        return $styles !== [] ? $styles : ['style.css'];
    }
}
