<?php
declare(strict_types=1);

namespace ImperialFuture\SEO;

use WP_Post;
use WP_Term;

if (! defined('ABSPATH')) {
    exit;
}

final class Breadcrumbs
{
    public static function render(): void
    {
        $items = self::items();

        if (count($items) < 2) {
            return;
        }

        echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Хлебные крошки', 'imperial-future') . '">';

        foreach ($items as $index => $item) {
            $isLast = $index === array_key_last($items);

            if ($isLast) {
                echo '<span class="breadcrumbs__item" aria-current="page">' . esc_html($item['name']) . '</span>';
                continue;
            }

            echo '<a class="breadcrumbs__item" href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
        }

        echo '</nav>';
    }

    public static function items(): array
    {
        $items = [
            [
                'name' => __('Главная', 'imperial-future'),
                'url' => home_url('/'),
            ],
        ];

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();

            if ($term instanceof WP_Term) {
                $items[] = [
                    'name' => $term->name,
                    'url' => get_term_link($term),
                ];
            }

            return $items;
        }

        if (is_author()) {
            $items[] = [
                'name' => get_the_author_meta('display_name', (int) get_query_var('author')),
                'url' => get_author_posts_url((int) get_query_var('author')),
            ];

            return $items;
        }

        if (is_search()) {
            $items[] = [
                'name' => sprintf(__('Поиск: %s', 'imperial-future'), get_search_query()),
                'url' => get_search_link(),
            ];

            return $items;
        }

        if (is_singular()) {
            $post = get_queried_object();

            if ($post instanceof WP_Post && $post->post_type === 'post') {
                $category = get_the_category($post->ID)[0] ?? null;

                if ($category instanceof WP_Term) {
                    $items[] = [
                        'name' => $category->name,
                        'url' => get_category_link($category),
                    ];
                }
            }

            $items[] = [
                'name' => get_the_title(),
                'url' => get_permalink(),
            ];
        }

        return $items;
    }
}
