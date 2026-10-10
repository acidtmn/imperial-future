<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class SvgSanitizer
{
    private const MAX_BYTES = 262144;

    public static function isSafeFile(string $file): bool
    {
        $size = is_readable($file) ? filesize($file) : false;

        if ($size === false || $size > self::MAX_BYTES) {
            return false;
        }

        $svg = (string) file_get_contents($file);

        if ($svg === '' || stripos($svg, '<svg') === false || preg_match('/<!\s*(DOCTYPE|ENTITY)/i', $svg)) {
            return false;
        }

        // XML-парсер раскрывает кодировку атрибутов, которую регулярные выражения могут пропустить.
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $document->loadXML($svg, LIBXML_NONET) || $document->documentElement?->localName !== 'svg') {
                return false;
            }

            $allowed = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan', 'title', 'desc', 'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'use', 'symbol'];
            foreach ($document->getElementsByTagName('*') as $element) {
                if (! in_array(strtolower($element->localName), $allowed, true)
                    || $element->namespaceURI !== 'http://www.w3.org/2000/svg') {
                    return false;
                }

                foreach ($element->attributes as $attribute) {
                    $name = strtolower($attribute->localName);
                    $value = trim($attribute->value);
                    if (str_starts_with($name, 'on') || $name === 'base'
                        || ($name === 'href' && ! preg_match('/^#[a-zA-Z0-9_.:-]+$/D', $value))
                        || preg_match('/(?:url\s*\(|expression\s*\(|javascript\s*:|@import|\\\\)/i', $value)) {
                        return false;
                    }
                }
            }

            return true;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function containsUnsafeMarkup(string $svg): bool
    {
        $patterns = [
            '/<\s*(script|iframe|object|embed|foreignObject|audio|video|canvas)\b/i',
            '/\son[a-z]+\s*=/i',
            '/(?:href|xlink:href|src)\s*=\s*["\']?\s*(?:javascript:|data:text\/html)/i',
            '/<\s*style\b[^>]*>.*?(?:expression\s*\(|javascript:|url\s*\(\s*["\']?\s*javascript:)/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $svg)) {
                return true;
            }
        }

        return false;
    }
}
