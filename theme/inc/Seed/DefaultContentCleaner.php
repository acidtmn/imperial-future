<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class DefaultContentCleaner
{
    public function clean(): void
    {
        $hello = get_page_by_path('hello-world', OBJECT, 'post');

        if ($hello) {
            wp_delete_post((int) $hello->ID, true);
        }

        $samplePage = get_page_by_path('sample-page');

        if ($samplePage) {
            wp_delete_post((int) $samplePage->ID, true);
        }
    }
}
