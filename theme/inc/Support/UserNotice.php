<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Template\AccountViewModel;

if (! defined('ABSPATH')) { exit; }

final class UserNotice
{
    public static function current(): ?array
    {
        $account = AccountViewModel::notice();
        if ($account !== null) { return $account; }

        $groups = [
            'imperial_auth_status' => [
                'login_error' => ['error', __('Не удалось войти. Проверь логин и пароль.', 'imperial-future')],
                'logged_in' => ['success', __('Ты вошёл в личный кабинет.', 'imperial-future')],
                'registered' => ['success', __('Аккаунт создан. Добро пожаловать!', 'imperial-future')],
                'register_error' => ['error', __('Не удалось зарегистрироваться. Проверь данные и доступность регистрации.', 'imperial-future')],
                'login_required' => ['error', __('Для этого действия нужно войти.', 'imperial-future')],
                'lost_sent' => ['success', __('Если такой аккаунт существует, письмо для восстановления отправлено на его почту.', 'imperial-future')],
                'social_error' => ['error', __('Вход через соцсеть не завершён. Попробуй ещё раз или войди с паролем.', 'imperial-future')],
                'social_missing' => ['error', __('Вход через соцсеть пока не настроен.', 'imperial-future')],
                'social_linked' => ['success', __('Социальный аккаунт привязан.', 'imperial-future')],
            ],
            'contact_status' => [
                'sent' => ['success', ThemeOptions::get('imperial_contact_success_message')],
                'captcha' => ['error', __('Перемести печать на отмеченное место и отправь письмо ещё раз.', 'imperial-future')],
                'error' => ['error', ThemeOptions::get('imperial_contact_error_message')],
                'mail_error' => ['error', __('Письмо не отправлено. Попробуй позже или свяжись с автором другим способом.', 'imperial-future')],
            ],
            'newsletter_status' => [
                'subscribed' => ['success', __('Подписка оформлена.', 'imperial-future')],
                'confirmation_sent' => ['success', __('Проверь почту и подтверди подписку.', 'imperial-future')],
                'unsubscribed' => ['success', __('Ты отписался от дайджеста.', 'imperial-future')],
                'error' => ['error', __('Не удалось оформить подписку. Проверь адрес и попробуй позже.', 'imperial-future')],
                'rate_limited' => ['error', __('Слишком много попыток. Попробуй позже.', 'imperial-future')],
            ],
        ];
        foreach ($groups as $parameter => $messages) {
            $status = sanitize_key((string) ($_GET[$parameter] ?? ''));
            if (isset($messages[$status])) {
                return ['type' => $messages[$status][0], 'message' => $messages[$status][1]];
            }
        }
        return null;
    }
}
