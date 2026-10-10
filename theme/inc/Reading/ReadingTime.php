<?php
declare(strict_types=1);

namespace ImperialFuture\Reading;

if (! defined('ABSPATH')) {
    exit;
}

final class ReadingTime
{
    public static function forPost(?int $postId = null): int
    {
        $postId = $postId ?: get_the_ID();
        $content = (string) get_post_field('post_content', $postId);
        $wordCount = str_word_count(wp_strip_all_tags($content), 0, 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя');

        return max(1, (int) ceil($wordCount / 180));
    }
}
