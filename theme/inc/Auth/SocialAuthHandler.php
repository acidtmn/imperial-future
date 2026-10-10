<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class SocialAuthHandler
{
    public static function registerHooks(): void
    {
        add_action('admin_post_imperial_social_start', [self::class, 'start']);
        add_action('admin_post_nopriv_imperial_social_start', [self::class, 'start']);
        add_action('init', [self::class, 'handleCallback']);
    }

    public static function start(): never
    {
        $provider = sanitize_key((string) ($_GET['provider'] ?? ''));

        if (! self::enabled($provider) || ! wp_verify_nonce((string) ($_GET['_wpnonce'] ?? ''), 'imperial_social_start')) {
            self::redirect('social_missing');
        }

        $state = OAuthStateService::issue($provider);

        $url = match ($provider) {
            'vk' => add_query_arg([
                'client_id' => ThemeOptions::get('imperial_auth_vk_client_id'),
                'redirect_uri' => self::callbackUrl('vk'),
                'response_type' => 'code',
                'scope' => 'email',
                'state' => $state,
                'v' => '5.199',
            ], 'https://oauth.vk.com/authorize'),
            'yandex' => add_query_arg([
                'client_id' => ThemeOptions::get('imperial_auth_yandex_client_id'),
                'redirect_uri' => self::callbackUrl('yandex'),
                'response_type' => 'code',
                'scope' => 'login:email login:info',
                'state' => $state,
            ], 'https://oauth.yandex.ru/authorize'),
            default => '',
        };

        if ($url === '') {
            self::redirect('social_missing');
        }

        // Адрес выбран из фиксированного списка OAuth-провайдеров.
        wp_redirect($url);
        exit;
    }

    public static function handleCallback(): void
    {
        $provider = sanitize_key((string) ($_GET['imperial_oauth'] ?? ''));
        $code = sanitize_text_field((string) ($_GET['code'] ?? ''));
        $state = sanitize_key((string) ($_GET['state'] ?? ''));

        if ($provider === '') {
            return;
        }

        if (! self::enabled($provider) || $code === '' || ! OAuthStateService::consume($provider, $state)) {
            self::redirect('social_error');
        }

        $profile = self::profile($provider, $code);

        if ($profile === []) {
            self::redirect('social_error');
        }

        $linking = is_user_logged_in();
        $userId = $linking
            ? self::linkCurrentUser($provider, $profile)
            : self::userId($provider, $profile);

        if ($userId <= 0) {
            self::redirect('social_error');
        }

        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);
        self::redirect($linking ? 'social_linked' : 'logged_in');
    }

    public static function startUrl(string $provider): string
    {
        return wp_nonce_url(add_query_arg([
            'action' => 'imperial_social_start',
            'provider' => $provider,
        ], admin_url('admin-post.php')), 'imperial_social_start');
    }

    public static function buttonUrl(string $provider): string
    {
        if (self::testMode($provider)) {
            return '#';
        }

        return self::startUrl($provider);
    }

    public static function visible(string $provider): bool
    {
        return self::enabledFlag($provider) || self::testMode($provider);
    }

    private static function enabled(string $provider): bool
    {
        return self::enabledFlag($provider) && self::clientId($provider) !== '' && ! self::testMode($provider);
    }

    private static function enabledFlag(string $provider): bool
    {
        return match ($provider) {
            'vk' => ThemeOptions::enabled('imperial_auth_vk_enabled'),
            'yandex' => ThemeOptions::enabled('imperial_auth_yandex_enabled'),
            default => false,
        };
    }

    private static function clientId(string $provider): string
    {
        return match ($provider) {
            'vk' => ThemeOptions::get('imperial_auth_vk_client_id'),
            'yandex' => ThemeOptions::get('imperial_auth_yandex_client_id'),
            default => '',
        };
    }

    private static function testMode(string $provider): bool
    {
        return self::clientId($provider) === '#';
    }

    private static function callbackUrl(string $provider): string
    {
        return add_query_arg('imperial_oauth', $provider, home_url('/'));
    }

    private static function profile(string $provider, string $code): array
    {
        return match ($provider) {
            'vk' => self::vkProfile($code),
            'yandex' => self::yandexProfile($code),
            default => [],
        };
    }

    private static function vkProfile(string $code): array
    {
        $response = wp_remote_get(add_query_arg([
            'client_id' => ThemeOptions::get('imperial_auth_vk_client_id'),
            'client_secret' => ThemeOptions::get('imperial_auth_vk_client_secret'),
            'redirect_uri' => self::callbackUrl('vk'),
            'code' => $code,
        ], 'https://oauth.vk.com/access_token'));
        $body = self::json($response);

        if (empty($body['access_token']) || empty($body['user_id'])) {
            return [];
        }

        return [
            'id' => (string) $body['user_id'],
            'email' => sanitize_email((string) ($body['email'] ?? '')),
            'name' => 'vk_' . (string) $body['user_id'],
        ];
    }

    private static function yandexProfile(string $code): array
    {
        $response = wp_remote_post('https://oauth.yandex.ru/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => ThemeOptions::get('imperial_auth_yandex_client_id'),
                'client_secret' => ThemeOptions::get('imperial_auth_yandex_client_secret'),
                'redirect_uri' => self::callbackUrl('yandex'),
            ],
        ]);
        $token = self::json($response);

        if (empty($token['access_token'])) {
            return [];
        }

        $profile = self::json(wp_remote_get('https://login.yandex.ru/info?format=json', [
            'headers' => [
                'Authorization' => 'OAuth ' . (string) $token['access_token'],
            ],
        ]));

        if (empty($profile['id'])) {
            return [];
        }

        return [
            'id' => (string) $profile['id'],
            'email' => sanitize_email((string) ($profile['default_email'] ?? '')),
            'name' => sanitize_user((string) ($profile['login'] ?? 'yandex_' . $profile['id']), true),
        ];
    }

    private static function userId(string $provider, array $profile): int
    {
        $metaKey = 'imperial_social_' . $provider . '_id';
        $users = get_users([
            'meta_key' => $metaKey,
            'meta_value' => $profile['id'],
            'number' => 1,
            'fields' => 'ID',
        ]);

        if ($users !== []) {
            return (int) $users[0];
        }

        $email = sanitize_email((string) ($profile['email'] ?? ''));
        // Совпадение почты не доказывает право привязать существующий аккаунт.
        if (! get_option('users_can_register') || ! is_email($email) || email_exists($email)) {
            return 0;
        }

        $userId = 0;

        if ($userId <= 0) {
            $login = sanitize_user((string) ($profile['name'] ?? $provider . '_' . $profile['id']), true);
            $created = wp_create_user($login, wp_generate_password(20), $email);
            $userId = is_wp_error($created) ? 0 : (int) $created;
        }

        if ($userId > 0) {
            update_user_meta($userId, $metaKey, (string) $profile['id']);
        }

        return $userId;
    }

    private static function linkCurrentUser(string $provider, array $profile): int
    {
        $metaKey = 'imperial_social_' . $provider . '_id';
        $userId = get_current_user_id();
        $linkedUsers = get_users([
            'meta_key' => $metaKey,
            'meta_value' => $profile['id'],
            'number' => 1,
            'fields' => 'ID',
        ]);

        if ($linkedUsers !== [] && (int) $linkedUsers[0] !== $userId) {
            return 0;
        }

        update_user_meta($userId, $metaKey, (string) $profile['id']);

        return $userId;
    }

    private static function json(array|\WP_Error $response): array
    {
        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($body) ? $body : [];
    }

    private static function redirect(string $status): never
    {
        $url = in_array($status, ['logged_in', 'social_linked'], true) ? AccountPageRegistrar::url() : home_url('/');
        wp_safe_redirect(add_query_arg('imperial_auth_status', $status, $url));
        exit;
    }
}
