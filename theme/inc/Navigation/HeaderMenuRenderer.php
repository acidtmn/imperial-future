<?php
declare(strict_types=1);

namespace ImperialFuture\Navigation;

use WP_Post;

if (! defined('ABSPATH')) {
    exit;
}

final class HeaderMenuRenderer
{
    public static function renderDesktop(): void
    {
        echo '<ul class="menu">';
        self::renderItems(false);
        echo '</ul>';
    }

    public static function renderMobile(): void
    {
        echo '<ul class="menu">';
        self::renderItems(true);
        echo '</ul>';
    }

    private static function renderItems(bool $mobile): void
    {
        $items = self::menuItems();
        $categories = [];
        $regular = [];

        foreach ($items as $item) {
            if (self::isCategoryItem($item)) {
                $categories[] = $item;
                continue;
            }

            $regular[] = $item;
        }

        if ($regular === []) {
            self::renderRegularLink(home_url('/'), __('Главная', 'imperial-future'), ['current-menu-item']);
        }

        foreach ($regular as $index => $item) {
            self::renderRegularLink((string) $item->url, (string) $item->title, (array) $item->classes);

            if ($index === 0) {
                self::renderCategories($categories, $mobile);
            }
        }

        if ($regular !== []) {
            return;
        }

        self::renderCategories($categories, $mobile);
    }

    private static function renderRegularLink(string $url, string $title, array $classes): void
    {
        $class = trim('menu-item ' . implode(' ', array_map('sanitize_html_class', $classes)));

        printf(
            '<li class="%1$s"><a href="%2$s">%3$s</a></li>',
            esc_attr($class),
            esc_url($url),
            esc_html($title)
        );
    }

    private static function renderCategories(array $menuCategories, bool $mobile): void
    {
        $categories = self::categoryLinks($menuCategories);

        if ($categories === []) {
            return;
        }

        if ($mobile) {
            echo '<li class="menu-item imperial-categories-menu imperial-categories-menu--mobile">';
            echo '<details><summary>' . esc_html__('Рубрики', 'imperial-future') . '</summary>';
            self::renderCategoryList($categories);
            echo '</details></li>';
            return;
        }

        echo '<li class="menu-item menu-item-has-children imperial-categories-menu">';
        echo '<button type="button" aria-haspopup="true">' . esc_html__('Рубрики', 'imperial-future') . '</button>';
        self::renderCategoryList($categories);
        echo '</li>';
    }

    private static function renderCategoryList(array $categories): void
    {
        echo '<ul class="sub-menu">';

        foreach ($categories as $category) {
            printf(
                '<li><a href="%1$s">%2$s</a></li>',
                esc_url($category['url']),
                esc_html($category['title'])
            );
        }

        echo '</ul>';
    }

    /**
     * @return WP_Post[]
     */
    private static function menuItems(): array
    {
        $locations = get_nav_menu_locations();
        $menuId = (int) ($locations['primary'] ?? 0);

        if ($menuId <= 0) {
            return [];
        }

        $items = wp_get_nav_menu_items($menuId, ['update_post_term_cache' => false]);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn ($item): bool => $item instanceof WP_Post && (int) $item->menu_item_parent === 0));
    }

    private static function isCategoryItem(WP_Post $item): bool
    {
        return $item->type === 'taxonomy' && $item->object === 'category';
    }

    private static function categoryLinks(array $menuCategories): array
    {
        if ($menuCategories !== []) {
            return array_map(static fn (WP_Post $item): array => [
                'title' => (string) $item->title,
                'url' => (string) $item->url,
            ], $menuCategories);
        }

        $categories = get_categories([
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        return array_map(static fn ($category): array => [
            'title' => $category->name,
            'url' => get_category_link($category),
        ], is_array($categories) ? $categories : []);
    }
}
