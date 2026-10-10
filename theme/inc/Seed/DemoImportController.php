<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class DemoImportController
{
    public static function registerHooks(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_post_imperial_import_demo', [self::class, 'import']);
    }

    public static function menu(): void
    {
        add_management_page(__('Демо Imperial Future', 'imperial-future'), __('Демо Imperial Future', 'imperial-future'), 'manage_options', 'imperial-demo', [self::class, 'page']);
    }

    public static function page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        get_template_part('parts/admin/demo-import');
    }

    public static function import(): never
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Недостаточно прав.', 'imperial-future'), '', ['response' => 403]);
        }
        check_admin_referer('imperial_import_demo');
        $status = 'success';
        try {
            SeedCoordinator::seed();
        } catch (\Throwable $exception) {
            $status = 'error';
        }
        wp_safe_redirect(add_query_arg(['page' => 'imperial-demo', 'import_status' => $status], admin_url('tools.php')));
        exit;
    }
}
