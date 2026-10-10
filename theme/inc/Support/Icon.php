<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class Icon
{
    private const NAMES = [
        'comment' => 'message-circle', 'bookmark' => 'bookmark', 'share' => 'share',
        'more' => 'ellipsis', 'thumb-up' => 'thumbs-up', 'thumb-down' => 'thumbs-down',
        'chevron-down' => 'chevron-down', 'chevron-right' => 'chevron-right',
        'link' => 'link', 'search' => 'search', 'account' => 'user-round',
        'sun' => 'sun', 'moon' => 'moon', 'menu' => 'menu', 'copy' => 'copy',
        'mail' => 'mail', 'reply' => 'reply', 'security' => 'shield-check', 'edit' => 'pen-line', 'logout' => 'log-out',
    ];

    public static function render(string $name, string $className = ''): string
    {
        $symbol = self::NAMES[$name] ?? 'ellipsis';
        $class = trim('imperial-icon ' . $className);

        return sprintf(
            '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><use href="%s#%s"></use></svg>',
            esc_attr($class),
            esc_url(get_template_directory_uri() . '/assets/dist/icons.svg'),
            esc_attr($symbol)
        );
    }
}
