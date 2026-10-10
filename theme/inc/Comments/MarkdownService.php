<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use League\CommonMark\CommonMarkConverter;
use League\HTMLToMarkdown\HtmlConverter;

if (! defined('ABSPATH')) {
    exit;
}

final class MarkdownService
{
    /**
     * Приводим старые HTML-комментарии и новые Markdown-комментарии к безопасному HTML.
     *
     * @param string $content Сохранённый текст, например **текст** или блок ```php.
     * @return string Разметка без исполняемого HTML и опасных ссылок.
     */
    public static function render(string $content): string
    {
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/(```[\s\S]*?```|`[^`\n]+`)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $legacy = new HtmlConverter(['strip_tags' => true, 'remove_nodes' => 'script style']);

        // HTML конвертируем только вне кода, иначе теряются примеры тегов и операторов.
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0 && preg_match('/<\/?(?:strong|em|b|i|p|a|blockquote|pre|code|ul|ol|li|br)\b/i', $part)) {
                $parts[$index] = $legacy->convert($part);
            }
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 32,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);
        $html = wp_kses_post((string) $converter->convert(implode('', $parts)));
        $processor = new \WP_HTML_Tag_Processor($html);
        while ($processor->next_tag('A')) {
            $processor->set_attribute('rel', 'nofollow ugc noopener');
        }

        return $processor->get_updated_html();
    }

    public static function preserveCode(array $comment): array
    {
        $comment['comment_content'] = self::preserveCodeText((string) ($comment['comment_content'] ?? ''));

        return $comment;
    }

    public static function preserveCodeText(string $content): string
    {
        return preg_replace_callback('/(```[\s\S]*?```|`[^`\n]+`)/', static fn (array $match): string => esc_html($match[0]), $content) ?? $content;
    }
}
