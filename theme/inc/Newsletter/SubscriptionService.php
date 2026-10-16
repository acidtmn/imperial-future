<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

use ImperialFuture\Security\RateLimiter;
use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class SubscriptionService
{
    private const OPTION_KEY = 'imperial_newsletter_subscribers';
    public const DIGEST_META = 'imperial_digest_subscribed';
    public const COMMENTS_META = 'imperial_comment_subscribed';
    public const REPLIES_META = 'imperial_reply_subscribed';
    public const VERIFIED_EMAIL_META = 'imperial_verified_email';
    public const CONFIRMATION_TTL = DAY_IN_SECONDS;

    public static function subscribe(string $email, int $userId = 0): bool
    {
        $email = self::normalizeEmail($email);

        if (! is_email($email) || ($userId > 0 && ! self::isEmailVerified($userId, $email))) {
            return false;
        }

        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            return false;
        }

        try {
            $subscribers = self::subscribers();
            $owner = (int) ($subscribers[$email]['user_id'] ?? 0);
            if ($userId > 0 && $owner > 0 && $owner !== $userId) {
                return false;
            }
            $subscribers[$email] = self::confirmedRecord($email, $subscribers[$email] ?? []);
            if ($userId > 0) {
                $subscribers[$email]['user_id'] = $userId;
            }
            update_option(self::OPTION_KEY, $subscribers, false);

            return get_option(self::OPTION_KEY) === $subscribers;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function requestConfirmation(string $email, string $purpose = 'newsletter'): bool
    {
        $email = self::normalizeEmail($email);
        $user = wp_get_current_user();
        $userId = $user->exists() && self::normalizeEmail((string) $user->user_email) === $email ? (int) $user->ID : 0;

        if (! is_email($email) || ! in_array($purpose, ['email', 'newsletter'], true)
            || ($purpose === 'newsletter' && ! ThemeOptions::enabled('imperial_newsletter_enabled'))
            || ($purpose === 'email' && $userId === 0)) {
            return false;
        }

        // Одинаковый ответ не выдаёт наличие адреса и ограничение по адресу.
        if (! RateLimiter::allow('newsletter_email', $email, 3, DAY_IN_SECONDS)) {
            return true;
        }

        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            return false;
        }

        try {
            $subscribers = self::subscribers();
            $previous = $subscribers[$email] ?? null;
            $record = $subscribers[$email] ?? ['email' => $email, 'created_at' => current_time('mysql')];
            $record['confirmed'] = ! empty($record['confirmed']);
            $record['confirmation_created_record'] = $record['confirmation_created_record'] ?? $previous === null;
            $token = bin2hex(random_bytes(32));
            $record['confirmation_hash'] = hash('sha256', $token);
            $record['confirmation_expires'] = time() + self::CONFIRMATION_TTL;
            $record['confirmation_user_id'] = $userId;
            $record['confirmation_purpose'] = $purpose;
            $subscribers[$email] = $record;
            update_option(self::OPTION_KEY, $subscribers, false);

            if (get_option(self::OPTION_KEY) !== $subscribers) {
                return false;
            }

            $url = add_query_arg(['action' => 'imperial_newsletter_confirm', 'token' => $token], admin_url('admin-post.php'));
            $sent = MailDelivery::send(
                $email,
                $purpose === 'email' ? __('Подтверди email', 'imperial-future') : __('Подтверди подписку на дайджест', 'imperial-future'),
                sprintf($purpose === 'email'
                    ? __("Подтверди email для уведомлений по ссылке в течение 24 часов:\n%s\n\nЭто не подписывает тебя на дайджест. Если ты не запрашивал подтверждение, проигнорируй письмо.", 'imperial-future')
                    : __("Подтверди подписку по ссылке в течение 24 часов:\n%s\n\nЕсли ты не запрашивал подписку, просто проигнорируй письмо.", 'imperial-future'), $url)
            );

            if (! $sent) {
                if ($previous === null) {
                    unset($subscribers[$email]);
                } else {
                    $subscribers[$email] = $previous;
                }
                update_option(self::OPTION_KEY, $subscribers, false);
            }

            return $sent;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function confirm(string $token): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return false;
        }

        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            return false;
        }

        try {
            $subscribers = self::subscribers();

            foreach ($subscribers as $email => $record) {
                if (! isset($record['confirmation_hash']) || ! hash_equals($record['confirmation_hash'], hash('sha256', $token))) {
                    continue;
                }

                $valid = (int) ($record['confirmation_expires'] ?? 0) > time();
                $purpose = (string) ($record['confirmation_purpose'] ?? 'newsletter');
                if (! in_array($purpose, ['email', 'newsletter'], true)
                    || ($purpose === 'newsletter' && ! ThemeOptions::enabled('imperial_newsletter_enabled'))) {
                    return false;
                }
                $userId = (int) ($record['confirmation_user_id'] ?? 0);
                $user = get_userdata($userId);
                if (($purpose === 'email' || $userId > 0)
                    && (! $user instanceof \WP_User || self::normalizeEmail((string) $user->user_email) !== $email)) {
                    $valid = false;
                }
                $created = ! empty($record['confirmation_created_record']);
                unset($record['confirmation_hash'], $record['confirmation_expires'], $record['confirmation_user_id'], $record['confirmation_purpose'], $record['confirmation_created_record']);
                if ($valid && $purpose === 'newsletter' && $userId > 0) {
                    $record['user_id'] = $userId;
                }
                if ($purpose === 'email' && $created) {
                    unset($subscribers[$email]);
                } else {
                    $subscribers[$email] = $valid && $purpose === 'newsletter' ? self::confirmedRecord($email, $record) : $record;
                }
                update_option(self::OPTION_KEY, $subscribers, false);

                if (! $valid || get_option(self::OPTION_KEY) !== $subscribers) {
                    return false;
                }

                // Только владение одноразовой ссылкой подтверждает адрес для аккаунта.
                if ($userId > 0) {
                    update_user_meta($userId, self::VERIFIED_EMAIL_META, $email);
                    if (! self::isEmailVerified($userId, $email)) {
                        return false;
                    }
                    if ($purpose === 'newsletter') {
                        update_user_meta($userId, self::DIGEST_META, '1');
                    }
                }

                return true;
            }

            return false;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function confirmationPurpose(string $token): string
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return '';
        }
        foreach (self::subscribers() as $record) {
            if (isset($record['confirmation_hash']) && hash_equals($record['confirmation_hash'], hash('sha256', $token))) {
                return (string) ($record['confirmation_purpose'] ?? 'newsletter');
            }
        }
        return '';
    }

    public static function unsubscribeUrl(string $email): string
    {
        $email = self::normalizeEmail($email);
        $record = self::subscribers()[$email] ?? [];

        if (empty($record['confirmed']) || empty($record['subscription_key'])) {
            return '';
        }

        $signature = hash_hmac('sha256', $email . '|' . $record['subscription_key'], wp_salt('auth'));

        return add_query_arg(['action' => 'imperial_newsletter_unsubscribe', 'email' => $email, 'signature' => $signature], admin_url('admin-post.php'));
    }

    public static function unsubscribeSigned(string $email, string $signature): ?bool
    {
        $email = self::normalizeEmail($email);
        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            return null;
        }

        try {
            $subscribers = self::subscribers();
            $record = $subscribers[$email] ?? [];

            if (empty($record['subscription_key']) || ! hash_equals(hash_hmac('sha256', $email . '|' . $record['subscription_key'], wp_salt('auth')), $signature)) {
                return false;
            }

            unset($subscribers[$email]);
            update_option(self::OPTION_KEY, $subscribers, false);
            if (get_option(self::OPTION_KEY) !== $subscribers) {
                error_log('Imperial newsletter: could not persist unsubscribe.');
                return null;
            }

            self::disableUserDigest((int) ($record['user_id'] ?? 0));

            return true;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function confirmedSubscribers(): array
    {
        return array_filter(self::subscribers(), static fn (array $record): bool => ! empty($record['confirmed']) && ! empty($record['subscription_key']));
    }

    public static function migrateUserPreferences(): void
    {
        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            return;
        }

        try {
            $subscribers = self::subscribers();
            $changed = false;

            // Восстанавливаем старую подписку только после доказанного владения адресом.
            foreach (get_users(['meta_key' => self::DIGEST_META, 'meta_value' => '1']) as $user) {
                if (! self::isEmailVerified((int) $user->ID)) {
                    continue;
                }
                $email = self::normalizeEmail((string) $user->user_email);

                if (isset($subscribers[$email]) && ! array_key_exists('confirmed', $subscribers[$email])) {
                    $subscribers[$email] = self::confirmedRecord($email, $subscribers[$email]);
                    $subscribers[$email]['user_id'] = (int) $user->ID;
                    $changed = true;
                }
            }

            if ($changed) {
                update_option(self::OPTION_KEY, $subscribers, false);

                if (get_option(self::OPTION_KEY) !== $subscribers) {
                    error_log('Imperial newsletter: could not migrate digest preferences.');
                }
            }
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    private static function confirmedRecord(string $email, array $record): array
    {
        return [
            'email' => $email,
            'created_at' => $record['created_at'] ?? current_time('mysql'),
            'confirmed' => true,
            'subscription_key' => $record['subscription_key'] ?? bin2hex(random_bytes(16)),
            'user_id' => (int) ($record['user_id'] ?? 0),
        ];
    }

    private static function disableUserDigest(int $userId): void
    {
        if ($userId > 0) {
            update_user_meta($userId, self::DIGEST_META, '0');
        }
    }

    private static function normalizeEmail(string $email): string
    {
        return strtolower(sanitize_email($email));
    }

    public static function unsubscribe(string $email, int $userId = 0): bool
    {
        $email = self::normalizeEmail($email);
        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            error_log('Imperial newsletter: unsubscribe blocked by subscription lock.');
            return false;
        }

        try {
            $subscribers = self::subscribers();
            $record = $subscribers[$email] ?? [];
            $owner = (int) ($record['user_id'] ?? 0);
            if ($userId > 0 && $owner !== $userId
                && ! ($owner === 0 && self::isEmailVerified($userId, $email))) {
                return true;
            }
            unset($subscribers[$email]);
            update_option(self::OPTION_KEY, $subscribers, false);

            if (get_option(self::OPTION_KEY) !== $subscribers) {
                error_log('Imperial newsletter: could not persist unsubscribe.');
                return false;
            }

            self::disableUserDigest((int) ($record['user_id'] ?? 0));
            return true;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function updateUserPreferences(int $userId, bool $digest, bool $comments, bool $replies = false): bool
    {
        $user = get_userdata($userId);
        if (! $user instanceof \WP_User || (($digest || $comments || $replies) && ! self::isEmailVerified($userId))) {
            return false;
        }

        $saved = $digest
            ? self::subscribe((string) $user->user_email, $userId)
            : self::unsubscribe((string) $user->user_email, $userId);
        if (! $saved) {
            return false;
        }

        update_user_meta($userId, self::DIGEST_META, $digest ? '1' : '0');
        update_user_meta($userId, self::COMMENTS_META, $comments ? '1' : '0');
        update_user_meta($userId, self::REPLIES_META, $replies ? '1' : '0');

        return true;
    }

    /**
     * Проверяем доказанное владение адресом, а не совпадение с полем профиля.
     *
     * @param int $userId ID владельца одноразового запроса подтверждения.
     * @param string|null $email Проверяемый адрес; null для текущего email профиля.
     * @return bool Адрес подтверждён через confirm именно для этого аккаунта.
     */
    public static function isEmailVerified(int $userId, ?string $email = null): bool
    {
        $user = get_userdata($userId);
        if (! $user instanceof \WP_User) {
            return false;
        }
        $email = self::normalizeEmail($email ?? (string) $user->user_email);

        return is_email($email) && get_user_meta($userId, self::VERIFIED_EMAIL_META, true) === $email;
    }

    public static function userDigestEnabled(int $userId): bool
    {
        $user = get_userdata($userId);
        return $user instanceof \WP_User && self::isEmailVerified($userId)
            && (int) (self::subscribers()[self::normalizeEmail((string) $user->user_email)]['user_id'] ?? 0) === $userId
            && get_user_meta($userId, self::DIGEST_META, true) === '1';
    }

    public static function userCommentsEnabled(int $userId): bool
    {
        return self::isEmailVerified($userId) && get_user_meta($userId, self::COMMENTS_META, true) === '1';
    }

    public static function userRepliesEnabled(int $userId): bool
    {
        return self::isEmailVerified($userId) && get_user_meta($userId, self::REPLIES_META, true) === '1';
    }

    public static function subscribers(): array
    {
        $subscribers = get_option(self::OPTION_KEY, []);

        $records = [];

        foreach (is_array($subscribers) ? $subscribers : [] as $email => $record) {
            $email = self::normalizeEmail((string) $email);

            if (is_email($email) && is_array($record)) {
                $records[$email] = $record;
            }
        }

        return $records;
    }
}
