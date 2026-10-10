<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

use ImperialFuture\Newsletter\SubscriptionService;

if (! defined('ABSPATH')) {
    exit;
}

final class ProfileService
{
    /**
     * Сохраняем профиль только после проверки имени и почты.
     *
     * @param array $input Поля формы, например display_name и email.
     * @return bool Удалось ли сохранить профиль и подписки.
     */
    public static function save(int $userId, array $input): bool
    {
        $user = get_userdata($userId);
        $name = sanitize_text_field((string) ($input['display_name'] ?? ''));
        $email = sanitize_email((string) ($input['email'] ?? ''));

        if (! $user instanceof \WP_User || $name === '' || ! is_email($email)) {
            return false;
        }

        $result = wp_update_user(['ID' => $userId, 'display_name' => $name, 'user_email' => $email]);

        if (is_wp_error($result)) {
            return false;
        }

        // Старый адрес исключаем из дайджеста при смене почты.
        if ($email !== $user->user_email) {
            SubscriptionService::unsubscribe((string) $user->user_email);
        }

        SubscriptionService::updateUserPreferences(
            $userId,
            ! empty($input['digest']),
            ! empty($input['comments']),
            ! empty($input['replies'])
        );

        return true;
    }
}
