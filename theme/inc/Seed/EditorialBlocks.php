<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class EditorialBlocks
{
    public static function paragraph(string $text, bool $lead = false): string
    {
        $attributes = $lead ? ' {"className":"is-style-lead"}' : '';
        $class = $lead ? ' class="is-style-lead"' : '';

        return '<!-- wp:paragraph' . $attributes . ' --><p' . $class . '>' . esc_html($text) . '</p><!-- /wp:paragraph -->';
    }

    public static function heading(string $text): string
    {
        return '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($text) . '</h2><!-- /wp:heading -->';
    }

    public static function listing(array $items, bool $ordered = false): string
    {
        $tag = $ordered ? 'ol' : 'ul';
        $attributes = $ordered ? ' {"ordered":true}' : '';
        $content = '';

        foreach ($items as $item) {
            $content .= '<!-- wp:list-item --><li>' . esc_html($item) . '</li><!-- /wp:list-item -->';
        }

        return '<!-- wp:list' . $attributes . ' --><' . $tag . ' class="wp-block-list">' . $content . '</' . $tag . '><!-- /wp:list -->';
    }

    public static function quote(string $text): string
    {
        return '<!-- wp:quote --><blockquote class="wp-block-quote">' . self::paragraph($text) . '</blockquote><!-- /wp:quote -->';
    }

    public static function details(string $summary, string $text): string
    {
        return '<!-- wp:details --><details class="wp-block-details"><summary>' . esc_html($summary) . '</summary>' . self::paragraph($text) . '</details><!-- /wp:details -->';
    }

    public static function code(string $code): string
    {
        return '<!-- wp:code --><pre class="wp-block-code"><code>' . esc_html($code) . '</code></pre><!-- /wp:code -->';
    }

    public static function table(array $headings, array $rows): string
    {
        $head = '';
        $body = '';

        foreach ($headings as $heading) {
            $head .= '<th>' . esc_html($heading) . '</th>';
        }

        foreach ($rows as $row) {
            $body .= '<tr>';
            foreach ($row as $cell) {
                $body .= '<td>' . esc_html($cell) . '</td>';
            }
            $body .= '</tr>';
        }

        return '<!-- wp:table --><figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table></figure><!-- /wp:table -->';
    }

    public static function source(string $label, string $url): string
    {
        return '<!-- wp:paragraph --><p><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></p><!-- /wp:paragraph -->';
    }
}
