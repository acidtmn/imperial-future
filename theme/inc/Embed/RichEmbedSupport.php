<?php
declare(strict_types=1);

namespace ImperialFuture\Embed;

if (! defined('ABSPATH')) {
    exit;
}

final class RichEmbedSupport
{
    public static function registerHooks(): void
    {
        add_action('init', [self::class, 'registerShortcode']);
        add_action('init', [self::class, 'registerInternalHandler']);
        add_filter('render_block_core/embed', [self::class, 'renderEmbedBlock'], 10, 2);
    }

    public static function registerShortcode(): void
    {
        add_shortcode('imperial_embed', [self::class, 'shortcode']);
        add_shortcode('imperial_card', [self::class, 'shortcode']);
    }

    public static function registerInternalHandler(): void
    {
        $home = preg_quote(home_url('/'), '#');

        wp_embed_register_handler(
            'imperial_internal_card',
            '#^' . $home . '[^\s<]+$#i',
            [self::class, 'renderInternal']
        );
    }

    public static function shortcode(array $attributes): string
    {
        $attributes = shortcode_atts(['url' => ''], $attributes, 'imperial_embed');
        $url = esc_url_raw((string) $attributes['url']);

        if ($url === '') {
            return '';
        }

        return self::isInternal($url) ? LinkCardRenderer::internal($url) : LinkCardRenderer::external($url);
    }

    public static function renderInternal(array $matches, array $attributes, string $url): string
    {
        return LinkCardRenderer::internal($url);
    }

    public static function renderEmbedBlock(string $blockContent, array $block): string
    {
        $url = esc_url_raw((string) ($block['attrs']['url'] ?? ''));

        if ($url === '') {
            return $blockContent;
        }

        if (self::isInternal($url)) {
            return LinkCardRenderer::internal($url);
        }

        if (preg_match('/<(iframe|object|embed|blockquote)\b/i', $blockContent)) {
            return $blockContent;
        }

        return LinkCardRenderer::external($url);
    }

    private static function isInternal(string $url): bool
    {
        $siteHost = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $urlHost = wp_parse_url($url, PHP_URL_HOST);

        return is_string($siteHost) && is_string($urlHost) && strcasecmp($siteHost, $urlHost) === 0;
    }
}
