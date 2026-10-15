<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class Icon
{
    public static function registerHooks(): void
    {
        add_action('wp_body_open', [self::class, 'renderSprite']);
    }

    public static function renderSprite(): void
    {
        $path = get_template_directory() . '/assets/dist/icons.svg';
        if (! is_readable($path)) {
            return;
        }
        $svg = new \WP_HTML_Tag_Processor((string) file_get_contents($path));
        $svg->next_tag('SVG');
        $svg->set_attribute('aria-hidden', 'true');
        $svg->set_attribute('focusable', 'false');
        $svg->set_attribute('width', '0');
        $svg->set_attribute('height', '0');
        $svg->set_attribute('style', 'position:absolute;width:0;height:0;overflow:hidden');
        echo $svg->get_updated_html();
    }

    private const NAMES = [
        'comment' => 'message-circle', 'bookmark' => 'bookmark', 'share' => 'share',
        'more' => 'ellipsis', 'thumb-up' => 'thumbs-up', 'thumb-down' => 'thumbs-down',
        'chevron-down' => 'chevron-down', 'chevron-right' => 'chevron-right',
        'link' => 'link', 'search' => 'search', 'account' => 'user-round',
        'sun' => 'sun', 'moon' => 'moon', 'menu' => 'menu', 'copy' => 'copy',
        'mail' => 'mail', 'reply' => 'reply', 'security' => 'shield-check', 'edit' => 'pen-line', 'logout' => 'log-out',
        'pause' => 'pause', 'play' => 'play',
    ];

    public static function render(string $name, string $className = ''): string
    {
        $symbol = self::NAMES[$name] ?? 'ellipsis';
        $class = trim('imperial-icon ' . $className);

        return sprintf(
            '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><use href="#imperial-icon-%s"></use></svg>',
            esc_attr($class),
            esc_attr($symbol)
        );
    }
}
