<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

if (! defined('ABSPATH')) {
    exit;
}

final class AdminAccessGuard
{
    public static function registerHooks(): void
    {
        add_action('admin_init', [self::class, 'redirectSubscribers']);
        add_filter('show_admin_bar', [self::class, 'showAdminBar']);
    }

    public static function redirectSubscribers(): void
    {
        global $pagenow;

        if (wp_doing_ajax() || $pagenow === 'admin-post.php' || current_user_can('edit_posts')) {
            return;
        }

        wp_safe_redirect(AccountPageRegistrar::url());
        exit;
    }

    public static function showAdminBar(bool $show): bool
    {
        return current_user_can('edit_posts') ? $show : false;
    }
}
