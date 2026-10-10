<?php
declare(strict_types=1);

namespace ImperialFuture\Reading;

use DOMDocument;
use DOMElement;
use DOMXPath;

if (! defined('ABSPATH')) {
    exit;
}

final class TableOfContents
{
    public static function build(string $html): array
    {
        if (trim($html) === '' || ! class_exists(DOMDocument::class)) {
            return [
                'content' => $html,
                'items' => [],
            ];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // DOMDocument получает фрагмент, поэтому оборачиваем контент в временной контейнер и не ломаем HTML WordPress.
        $document->loadHTML('<?xml encoding="UTF-8"><div id="imperial-content-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $headings = $xpath->query('//h2|//h3');
        $items = [];

        if ($headings === false) {
            return [
                'content' => $html,
                'items' => [],
            ];
        }

        foreach ($headings as $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }

            $text = trim($heading->textContent);

            if ($text === '') {
                continue;
            }

            $id = $heading->getAttribute('id') ?: sanitize_title($text);
            $heading->setAttribute('id', $id);

            $items[] = [
                'id' => $id,
                'text' => $text,
                'level' => strtolower($heading->tagName),
            ];
        }

        $root = $document->getElementById('imperial-content-root');

        return [
            'content' => $root ? self::innerHtml($root) : $html,
            'items' => $items,
        ];
    }

    private static function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?: '';
        }

        return $html;
    }
}
