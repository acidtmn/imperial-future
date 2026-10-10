<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

if (! defined('ABSPATH')) {
    exit;
}

final class AccountPageRegistrar
{
    private const OPTION_KEY = 'imperial_account_page_id';

    public static function url(): string
    {
        $page = get_post((int) get_option(self::OPTION_KEY, 0));
        if (! $page instanceof \WP_Post || $page->post_status !== 'publish') {
            $page = get_page_by_path('account');
        }
        return $page instanceof \WP_Post ? (string) get_permalink($page) : home_url('/account/');
    }

    public static function registerHooks(): void
    {
        // Страницу создаёт только явное действие настройки сайта.
    }

    public static function ensurePage(): void
    {
        if (get_post((int) get_option(self::OPTION_KEY, 0)) instanceof \WP_Post) {
            return;
        }

        $existing = get_page_by_path('account');
        $pageId = $existing ? (int) $existing->ID : wp_insert_post([
            'post_type' => 'page',
            'post_name' => 'account',
            'post_title' => 'Кабинет читателя',
            'post_content' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
        ]);

        if (is_wp_error($pageId) || (int) $pageId <= 0) {
            return;
        }

        if (! $existing) {
            update_post_meta((int) $pageId, '_wp_page_template', 'page-account.php');
        }
        update_option(self::OPTION_KEY, (string) $pageId, false);
    }
}
