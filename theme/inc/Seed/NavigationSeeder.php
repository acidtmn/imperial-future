<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class NavigationSeeder
{
    public function __construct(private readonly array $pages)
    {
    }

    public function seed(): void
    {
        $menuId = $this->menuId('Имперская навигация');

        $this->addMenuItem($menuId, 'Главная', home_url('/'));
        $this->addCategoryItem($menuId, 'Имперская хроника', 'chronicles');
        $this->addCategoryItem($menuId, 'Машины', 'machines');
        $this->addMenuItem($menuId, 'Контакты', get_permalink((int) ($this->pages['contacts'] ?? 0)));

        $locations = (array) get_theme_mod('nav_menu_locations', []);
        foreach (['primary', 'footer'] as $location) {
            if (empty($locations[$location])) {
                $locations[$location] = $menuId;
            }
        }
        set_theme_mod('nav_menu_locations', $locations);
    }

    private function menuId(string $name): int
    {
        $menu = wp_get_nav_menu_object($name);

        if ($menu) {
            return (int) $menu->term_id;
        }

        $created = wp_create_nav_menu($name);

        if (! is_wp_error($created)) {
            return (int) $created;
        }

        $fallback = get_term_by('name', $name, 'nav_menu');

        return $fallback ? (int) $fallback->term_id : 0;
    }

    private function addMenuItem(int $menuId, string $title, string $url): void
    {
        if ($menuId <= 0 || $url === '' || $this->menuItemExists($menuId, $title)) {
            return;
        }

        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => $title,
            'menu-item-url' => $url,
            'menu-item-status' => 'publish',
        ]);
    }

    private function addCategoryItem(int $menuId, string $title, string $slug): void
    {
        $category = get_category_by_slug($slug);

        if ($menuId <= 0 || ! $category || $this->menuItemExists($menuId, $title)) {
            return;
        }

        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => $title,
            'menu-item-object' => 'category',
            'menu-item-object-id' => (int) $category->term_id,
            'menu-item-type' => 'taxonomy',
            'menu-item-status' => 'publish',
        ]);
    }

    private function menuItemExists(int $menuId, string $title): bool
    {
        $items = wp_get_nav_menu_items($menuId);

        if (! is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            if ($item->title === $title) {
                return true;
            }
        }

        return false;
    }
}
