<?php
declare(strict_types=1);

namespace ImperialFuture\External;

if (! defined('ABSPATH')) {
    exit;
}

final class ExternalLinkContentFilter
{
    public static function registerHooks(): void
    {
        add_filter('the_content', [self::class, 'rewrite'], 30);
    }

    public static function rewrite(string $content): string
    {
        if ($content === '' || ! class_exists('\WP_HTML_Tag_Processor')) {
            return $content;
        }

        $processor = new \WP_HTML_Tag_Processor($content);

        while ($processor->next_tag('A')) {
            $href = (string) $processor->get_attribute('href');

            if (! ExternalLinkService::shouldWrap($href)) {
                continue;
            }

            $processor->set_attribute('href', ExternalLinkService::wrapUrl($href));
            $processor->set_attribute('target', '_blank');
            $processor->set_attribute('rel', self::mergeRel((string) $processor->get_attribute('rel')));
        }

        return $processor->get_updated_html();
    }

    private static function mergeRel(string $rel): string
    {
        $items = preg_split('/\s+/', $rel);
        $items = array_filter(array_map('trim', is_array($items) ? $items : []));

        return implode(' ', array_unique([...$items, 'noopener', 'noreferrer', 'nofollow', 'external']));
    }
}
