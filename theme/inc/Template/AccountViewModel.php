<?php
declare(strict_types=1);

namespace ImperialFuture\Template;

use ImperialFuture\Bookmark\BookmarkService;
use ImperialFuture\Newsletter\SubscriptionService;
use WP_Comment;
use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class AccountViewModel
{
    /**
     * @return array{type:string,message:string}|null
     */
    public static function notice(): ?array
    {
        $status = sanitize_key((string) ($_GET['imperial_auth_status'] ?? ''));

        if ($status === '') {
            return null;
        }

        $messages = [
            'profile_saved' => ['success', __('Профиль обновлён. Архивная карточка приведена в порядок.', 'imperial-future')],
            'profile_error' => ['error', __('Не удалось сохранить профиль. Проверь имя и адрес почты.', 'imperial-future')],
            'avatar_saved' => ['success', __('Аватар обновлён и аккуратно вписан в печать.', 'imperial-future')],
            'password_saved' => ['success', __('Пароль изменён. Сеанс защищён новым ключом.', 'imperial-future')],
            'password_current_error' => ['error', __('Текущий пароль указан неверно.', 'imperial-future')],
            'password_strength_error' => ['error', __('Новый пароль должен совпадать в обоих полях и быть не короче 12 символов.', 'imperial-future')],
            'avatar_error' => ['error', __('Не удалось обновить аватар. Проверьте формат PNG, JPG или WebP.', 'imperial-future')],
            'security_error' => ['error', __('Сессия устарела. Обновите страницу и повторите действие.', 'imperial-future')],
            'too_many_requests' => ['error', __('Слишком много попыток. Подождите немного перед следующим действием.', 'imperial-future')],
        ];

        if (! isset($messages[$status])) {
            return null;
        }

        return [
            'type' => $messages[$status][0],
            'message' => $messages[$status][1],
        ];
    }

    /**
     * @return array<int,array{label:string,value:string,detail:string}>
     */
    public static function stats(WP_User $user): array
    {
        $userId = (int) $user->ID;
        $bookmarkCount = count(BookmarkService::ids($userId));
        $commentCount = (int) get_comments([
            'user_id' => $userId,
            'status' => 'approve',
            'count' => true,
        ]);
        $subscriptionCount = self::subscriptionCount($userId);

        return [
            [
                'label' => __('Закладки', 'imperial-future'),
                'value' => number_format_i18n($bookmarkCount),
                'detail' => __('сохранено', 'imperial-future'),
            ],
            [
                'label' => __('Комментарии', 'imperial-future'),
                'value' => number_format_i18n($commentCount),
                'detail' => __('опубликовано', 'imperial-future'),
            ],
            [
                'label' => __('Подписки', 'imperial-future'),
                'value' => number_format_i18n($subscriptionCount),
                'detail' => __('активно', 'imperial-future'),
            ],
        ];
    }

    /**
     * @return WP_Comment[]
     */
    public static function recentComments(WP_User $user, int $limit = 4): array
    {
        $comments = get_comments([
            'user_id' => (int) $user->ID,
            'status' => 'approve',
            'number' => max(1, $limit),
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
        ]);

        return array_values(array_filter($comments, static fn (mixed $comment): bool => $comment instanceof WP_Comment));
    }

    public static function memberSince(WP_User $user): string
    {
        $timestamp = strtotime((string) $user->user_registered);

        if (! $timestamp) {
            return __('Дата вступления уточняется', 'imperial-future');
        }

        return sprintf(__('В архиве с %s', 'imperial-future'), wp_date('j F Y', $timestamp));
    }

    private static function subscriptionCount(int $userId): int
    {
        return (int) SubscriptionService::userDigestEnabled($userId)
            + (int) SubscriptionService::userCommentsEnabled($userId)
            + (int) SubscriptionService::userRepliesEnabled($userId);
    }
}
