<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

use ImperialFuture\Newsletter\SubscriptionService;
use ImperialFuture\Security\RateLimiter;

if (! defined('ABSPATH')) {
    exit;
}

final class AuthHandler
{
    public static function registerHooks(): void
    {
        add_action('admin_post_nopriv_imperial_login', [self::class, 'login']);
        add_action('admin_post_nopriv_imperial_register', [self::class, 'register']);
        add_action('admin_post_nopriv_imperial_lost_password', [self::class, 'lostPassword']);
        add_action('admin_post_imperial_profile_save', [self::class, 'saveProfile']);
        add_action('admin_post_imperial_avatar_upload', [self::class, 'uploadAvatar']);
        add_action('admin_post_imperial_password_change', [self::class, 'changePassword']);
    }

    public static function login(): never
    {
        self::checkNonce('imperial_auth');
        self::throttle('login', 10, 10 * MINUTE_IN_SECONDS);

        $user = wp_signon([
            'user_login' => sanitize_text_field((string) ($_POST['login'] ?? '')),
            'user_password' => (string) wp_unslash($_POST['password'] ?? ''),
            'remember' => ! empty($_POST['remember']),
        ], is_ssl());

        self::redirect(is_wp_error($user) ? self::referer() : AccountPageRegistrar::url(), is_wp_error($user) ? 'login_error' : 'logged_in');
    }

    public static function register(): never
    {
        self::checkNonce('imperial_auth');
        self::throttle('register', 5, HOUR_IN_SECONDS);

        if (! get_option('users_can_register')) {
            self::redirect(self::referer(), 'register_error');
        }

        $email = sanitize_email((string) ($_POST['email'] ?? ''));
        $name = sanitize_user((string) ($_POST['name'] ?? ''), true);
        $password = (string) wp_unslash($_POST['password'] ?? '');

        if (! is_email($email) || $name === '' || ! validate_username($name) || strlen($password) < 12 || email_exists($email) || username_exists($name)) {
            self::redirect(self::referer(), 'register_error');
        }

        $userId = wp_create_user($name, $password, $email);

        if (is_wp_error($userId)) {
            self::redirect(self::referer(), 'register_error');
        }

        wp_set_current_user((int) $userId);
        wp_set_auth_cookie((int) $userId, true);
        SubscriptionService::updateUserPreferences((int) $userId, false, false, false);

        self::redirect(AccountPageRegistrar::url(), 'registered');
    }

    public static function lostPassword(): never
    {
        self::checkNonce('imperial_auth');
        self::throttle('lost_password', 6, HOUR_IN_SECONDS);

        $_POST['user_login'] = sanitize_text_field((string) ($_POST['user_login'] ?? ''));
        retrieve_password();

        // Одинаковый ответ не раскрывает наличие адреса в базе пользователей.
        self::redirect(self::referer(), 'lost_sent');
    }

    public static function saveProfile(): never
    {
        self::checkNonce('imperial_profile');

        if (! is_user_logged_in()) {
            self::redirect(home_url('/'), 'login_required');
        }

        $saved = ProfileService::save(get_current_user_id(), wp_unslash($_POST));
        self::redirect(self::referer(), $saved ? 'profile_saved' : 'profile_error');
    }

    public static function uploadAvatar(): never
    {
        self::checkNonce('imperial_profile');

        if (! is_user_logged_in() || empty($_FILES['avatar']['tmp_name'])) {
            self::redirect(self::referer(), 'avatar_error');
        }

        $avatarId = AvatarService::storeUploaded((int) get_current_user_id(), $_FILES['avatar']);

        self::redirect(self::referer(), $avatarId > 0 ? 'avatar_saved' : 'avatar_error');
    }

    public static function changePassword(): never
    {
        self::checkNonce('imperial_profile');

        if (! is_user_logged_in()) {
            self::redirect(home_url('/'), 'login_required');
        }

        $user = wp_get_current_user();
        $currentPassword = (string) wp_unslash($_POST['current_password'] ?? '');
        $newPassword = (string) wp_unslash($_POST['new_password'] ?? '');
        $repeatPassword = (string) wp_unslash($_POST['repeat_password'] ?? '');

        if (! $user instanceof \WP_User || ! wp_check_password($currentPassword, $user->user_pass, $user->ID)) {
            self::redirect(self::referer(), 'password_current_error');
        }

        if ($newPassword !== $repeatPassword || strlen($newPassword) < 12) {
            self::redirect(self::referer(), 'password_strength_error');
        }

        wp_set_password($newPassword, $user->ID);
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true, is_ssl());

        self::redirect(self::referer(), 'password_saved');
    }

    private static function checkNonce(string $action): void
    {
        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce((string) $_POST['_wpnonce'], $action)) {
            self::redirect(self::referer(), 'security_error');
        }
    }

    private static function throttle(string $scope, int $limit, int $windowSeconds): void
    {
        if (! RateLimiter::allow('auth_' . $scope, RateLimiter::clientIdentity(), $limit, $windowSeconds)) {
            self::redirect(self::referer(), 'too_many_requests');
        }
    }

    private static function referer(): string
    {
        return wp_get_referer() ?: home_url('/');
    }

    private static function redirect(string $url, string $status): never
    {
        wp_safe_redirect(add_query_arg('imperial_auth_status', $status, remove_query_arg('imperial_auth_status', $url)));
        exit;
    }
}
