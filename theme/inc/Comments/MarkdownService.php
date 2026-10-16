<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Parser\MarkdownParser;
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
        $parts = self::codeParts($content);
        $legacy = new HtmlConverter(['strip_tags' => true, 'remove_nodes' => 'script style']);

        // HTML конвертируем только вне кода, иначе теряются примеры тегов и операторов.
        foreach ($parts as $index => $part) {
            if (! $part['code'] && preg_match('/<\/?(?:strong|em|b|i|p|a|blockquote|pre|code|ul|ol|li|br)\b/i', $part['text'])) {
                $parts[$index]['text'] = $legacy->convert($part['text']);
            }
        }

        $html = wp_kses_post((string) self::converter()->convert(implode('', array_column($parts, 'text'))));
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
        return implode('', array_map(static fn (array $part): string => $part['code'] ? esc_html($part['text']) : $part['text'], self::codeParts($content)));
    }

    /**
     * Отделяем код по границам блоков CommonMark перед обработкой старого HTML.
     *
     * @param string $content Markdown, например ~~~html с HTML-примером.
     * @return array<int,array{text:string,code:bool}> Исходные фрагменты без изменения пробелов.
     */
    private static function codeParts(string $content): array
    {
        $parser = new MarkdownParser(self::converter()->getEnvironment());
        $walker = $parser->parse($content)->walker();
        $codeLines = [];

        while ($event = $walker->next()) {
            $node = $event->getNode();
            if ($event->isEntering() && ($node instanceof FencedCode || $node instanceof IndentedCode)) {
                for ($line = $node->getStartLine(); $line <= $node->getEndLine(); ++$line) {
                    $codeLines[$line] = true;
                }
            }
        }

        $blocks = [];
        foreach (preg_split('/(?<=\n)|(?<=\r)(?!\n)/', $content) ?: [] as $index => $line) {
            $code = isset($codeLines[$index + 1]);
            $last = array_key_last($blocks);
            if ($last !== null && $blocks[$last]['code'] === $code) {
                $blocks[$last]['text'] .= $line;
            } else {
                $blocks[] = ['text' => $line, 'code' => $code];
            }
        }

        $parts = [];
        foreach ($blocks as $block) {
            if ($block['code']) {
                $parts[] = $block;
                continue;
            }
            $inline = preg_split('/((?<!`)(`+)(?!`)[\s\S]*?\2(?!`))/', $block['text'], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
            foreach ($inline as $index => $text) {
                if ($index % 3 !== 2) {
                    $parts[] = ['text' => $text, 'code' => $index % 3 === 1];
                }
            }
        }

        return $parts;
    }

    private static function converter(): CommonMarkConverter
    {
        return new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 32,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);
    }
}
