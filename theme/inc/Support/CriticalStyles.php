<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class CriticalStyles
{
    private static bool $loaded = false;

    public static function enqueue(): void
    {
        $file = 'css/critical-inline.css';
        $path = get_template_directory() . '/assets/dist/' . $file;
        if (! is_readable($path)) {
            return;
        }

        $fontFile = get_template_directory() . '/assets/dist/css/fonts-inline.json';
        $fonts = is_readable($fontFile) ? json_decode((string) file_get_contents($fontFile), true) : [];
        $body = ThemeOptions::get('imperial_font_body');
        $families = [ThemeOptions::get('imperial_font_heading'), $body === 'Inter' ? 'Inter Variable' : $body];
        $fontCss = '';
        foreach ($families as $family) {
            $fontCss .= $fonts[$family] ?? '';
        }
        $css = str_replace('__IMPERIAL_DIST_URL__', esc_url(get_template_directory_uri() . '/assets/dist'), $fontCss . (string) file_get_contents($path));
        wp_register_style('imperial-future-critical', false, [], null);
        wp_enqueue_style('imperial-future-critical');
        self::$loaded = wp_add_inline_style('imperial-future-critical', $css);
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    public static function deferStylesheet(string $html, string $handle, string $href, string $media): string
    {
        if (! self::$loaded || ! is_front_page() || ! is_home() || ! str_starts_with($handle, 'imperial-future-main-')) {
            return $html;
        }

        $id = $handle . '-css';
        $script = '(function(){const load=()=>requestAnimationFrame(()=>setTimeout(()=>{document.getElementById('
            . wp_json_encode($id) . ').href=' . wp_json_encode($href, JSON_HEX_TAG | JSON_HEX_AMP) . ';},0));'
            . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",load,{once:true});}else{load();}})();';

        return sprintf('<link rel="stylesheet" id="%s"><noscript>%s</noscript>', esc_attr($id), $html)
            . wp_get_inline_script_tag($script);
    }
}
