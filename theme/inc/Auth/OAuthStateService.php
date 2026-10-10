<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

if (! defined('ABSPATH')) {
    exit;
}

final class OAuthStateService
{
    private const COOKIE = 'imperial_oauth_state';

    /**
     * Привязываем одноразовый OAuth-запрос к браузеру и текущему пользователю.
     *
     * @param string $provider Провайдер vk или yandex.
     * @return string Случайное значение для параметра state.
     */
    public static function issue(string $provider): string
    {
        $state = bin2hex(random_bytes(32));
        set_transient('imperial_oauth_' . $state, [
            'provider' => $provider,
            'user_id' => get_current_user_id(),
        ], 10 * MINUTE_IN_SECONDS);
        self::cookie($state, time() + 10 * MINUTE_IN_SECONDS);

        return $state;
    }

    /**
     * Проверяем браузер, провайдера и сессию до обмена кода на токен.
     *
     * @return bool Состояние действительно и погашено ровно один раз.
     */
    public static function consume(string $provider, string $state): bool
    {
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');

        if (! preg_match('/^[a-f0-9]{64}$/D', $state) || ! hash_equals($state, $cookie)) {
            return false;
        }

        $request = get_transient('imperial_oauth_' . $state);
        delete_transient('imperial_oauth_' . $state);
        self::cookie('', time() - HOUR_IN_SECONDS);

        return is_array($request)
            && ($request['provider'] ?? '') === $provider
            && (int) ($request['user_id'] ?? -1) === get_current_user_id();
    }

    private static function cookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => COOKIEPATH ?: '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
