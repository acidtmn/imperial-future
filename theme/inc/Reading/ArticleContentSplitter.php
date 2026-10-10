<?php
declare(strict_types=1);

namespace ImperialFuture\Reading;

if (! defined('ABSPATH')) {
    exit;
}

final class ArticleContentSplitter
{
    public static function afterFirstParagraph(string $content): array
    {
        if (! preg_match('/^\s*(<p\b[^>]*>.*?<\/p>)/is', $content, $matches)) {
            return [
                'intro' => '',
                'body' => $content,
            ];
        }

        $intro = (string) $matches[1];

        return [
            'intro' => $intro,
            'body' => ltrim(substr($content, strlen($matches[0]))),
        ];
    }
}
