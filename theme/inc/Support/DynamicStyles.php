<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class DynamicStyles
{
    public static function registerHooks(): void
    {
        add_action('wp_head', [self::class, 'render'], 40);
    }

    public static function render(): void
    {
        $dark = [
            '--font-heading' => self::fontStack(ThemeOptions::get('imperial_font_heading'), 'heading'),
            '--font-body' => self::fontStack(ThemeOptions::get('imperial_font_body'), 'body'),
            '--color-void' => ThemeOptions::get('imperial_color_dark_void'),
            '--color-panel' => ThemeOptions::get('imperial_color_dark_panel'),
            '--color-gold' => ThemeOptions::get('imperial_color_dark_gold'),
            '--color-paper' => ThemeOptions::get('imperial_color_dark_paper'),
            '--color-bronze' => ThemeOptions::get('imperial_color_dark_bronze'),
            '--color-parchment' => ThemeOptions::get('imperial_color_dark_parchment'),
            '--color-red' => ThemeOptions::get('imperial_color_dark_red'),
            '--color-text' => ThemeOptions::get('imperial_color_dark_paper'),
            '--color-muted' => 'rgba(248, 239, 216, .68)',
            '--color-panel-soft' => 'rgba(27, 27, 34, .76)',
            '--color-border' => 'rgba(212, 175, 55, .34)',
            '--color-glass' => 'rgba(244, 231, 197, .08)',
        ];

        $light = [
            '--color-void' => ThemeOptions::get('imperial_color_light_void'),
            '--color-panel' => ThemeOptions::get('imperial_color_light_panel'),
            '--color-text' => ThemeOptions::get('imperial_color_light_text'),
            '--color-paper' => ThemeOptions::get('imperial_color_light_text'),
            '--color-gold' => ThemeOptions::get('imperial_color_light_gold'),
            '--color-bronze' => '#5f421a',
            '--color-parchment' => '#3a2d1e',
            '--color-red' => '#84272b',
            '--color-muted' => 'rgba(32, 24, 19, .66)',
            '--color-panel-soft' => 'rgba(247, 237, 207, .82)',
            '--color-border' => 'rgba(122, 92, 46, .42)',
            '--color-glass' => 'rgba(255, 255, 255, .46)',
        ];

        echo '<style id="imperial-future-dynamic-styles">';
        echo ':root{' . self::cssVariables($dark) . '}';
        echo ':root[data-theme="light"]{' . self::cssVariables($light) . '}';
        echo self::heroSliderBackgroundCss();
        echo '</style>' . PHP_EOL;
    }

    private static function cssVariables(array $variables): string
    {
        $css = '';

        foreach ($variables as $name => $value) {
            if (str_starts_with($name, '--font-')) {
                // Семейства берутся из закрытого списка; HTML-сущности внутри style ломают CSS.
                $css .= $name . ':' . $value . ';';
                continue;
            }

            if (str_starts_with($value, 'rgba(')) {
                $css .= $name . ':' . esc_attr($value) . ';';
                continue;
            }

            $color = sanitize_hex_color($value);

            if (! is_string($color) || $color === '') {
                continue;
            }

            $css .= $name . ':' . $color . ';';
        }

        return $css;
    }

    private static function fontStack(string $font, string $type): string
    {
        $heading = [
            'Oranienbaum' => "'Oranienbaum', Georgia, serif",
            'Forum' => "'Forum', Georgia, serif",
            'Prata' => "'Prata', Georgia, serif",
        ];

        $body = [
            'Inter' => "'Inter Variable', Arial, sans-serif",
            'PT Sans' => "'PT Sans', Arial, sans-serif",
            'IBM Plex Sans' => "'IBM Plex Sans', Arial, sans-serif",
        ];

        $stacks = $type === 'heading' ? $heading : $body;

        return $stacks[$font] ?? reset($stacks);
    }

    private static function heroSliderBackgroundCss(): string
    {
        $mode = ThemeOptions::get('imperial_hero_slider_background_mode');

        if ($mode === 'image') {
            $image = ThemeOptions::mediaUrl('imperial_hero_slider_background_image', 'large');

            if ($image !== '') {
                return '';
            }
        }

        if ($mode === 'css') {
            $css = self::sanitizeBackgroundValue(ThemeOptions::get('imperial_hero_slider_background_css'));

            if ($css !== '') {
                return '.hero--slider{--hero-slider-background:' . esc_html($css) . ';}';
            }
        }

        $presets = [
            'brass' => 'radial-gradient(circle at 72% 34%,rgba(212,175,55,.28),transparent 24rem),linear-gradient(135deg,#0f0f12 0%,#241c13 54%,#0f0f12 100%)',
            'ruby' => 'radial-gradient(circle at 18% 42%,rgba(212,175,55,.22),transparent 20rem),linear-gradient(135deg,#120d10 0%,#4d161b 58%,#151014 100%)',
            'aurora' => 'radial-gradient(circle at 80% 20%,rgba(137,182,201,.24),transparent 22rem),linear-gradient(135deg,#0f0f12 0%,#20302e 48%,#7a5c2e 100%)',
            'graphite' => 'radial-gradient(circle at 65% 45%,rgba(244,231,197,.14),transparent 20rem),linear-gradient(135deg,#0f0f12 0%,#1b1b22 45%,#2d2417 100%)',
        ];
        $preset = ThemeOptions::get('imperial_hero_slider_background_preset');

        return '.hero--slider{--hero-slider-background:' . ($presets[$preset] ?? $presets['brass']) . ';}';
    }

    private static function sanitizeBackgroundValue(string $value): string
    {
        $value = wp_strip_all_tags($value);
        $value = str_replace(['</style', '<script', '</script'], '', $value);

        return trim((string) preg_replace('/[{};]/', '', $value));
    }
}
