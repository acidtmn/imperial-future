<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class SeedCoordinator
{
    private const OPTION_KEY = 'imperial_future_seeded';

    public static function registerHooks(): void
    {
        // Активация оформления не должна менять содержимое действующего сайта.
    }

    public static function seed(): void
    {
        if (get_transient('imperial_demo_import_running')) {
            return;
        }

        set_transient('imperial_demo_import_running', '1', 5 * MINUTE_IN_SECONDS);

        try {
            $categories = (new CategorySeeder())->seed();
            $media = (new MediaSeeder())->seed();
            $pages = (new PageSeeder())->seed();
            \ImperialFuture\Auth\AccountPageRegistrar::ensurePage();
            $posts = (new PostSeeder($categories, $media))->seed();
            EditorialComments::seed($posts);
            (new DemoContentSeeder($categories, $media))->seed();
            (new CommentSeeder())->seed();
            (new NavigationSeeder($pages))->seed();
            (new WidgetSeeder())->seed();

            update_option(self::OPTION_KEY, current_time('mysql'));
        } catch (\Throwable $exception) {
            throw new \RuntimeException(__('Не удалось импортировать демо-контент.', 'imperial-future'), 0, $exception);
        } finally {
            delete_transient('imperial_demo_import_running');
        }
    }
}
