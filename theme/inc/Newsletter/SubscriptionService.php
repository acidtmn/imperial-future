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
    public const CONFIRMATION_TTL = DAY_IN_SECONDS;

    public static function subscribe(string $email): bool
    {
        $email = self::normalizeEmail($email);

        if (! is_email($email)) {
            return false;
        }

        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            return false;
        }

        try {
            $subscribers = self::subscribers();
            $subscribers[$email] = self::confirmedRecord($email, $subscribers[$email] ?? []);
            update_option(self::OPTION_KEY, $subscribers, false);

            return get_option(self::OPTION_KEY) === $subscribers;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function requestConfirmation(string $email): bool
    {
        $email = self::normalizeEmail($email);

        if (! is_email($email) || ! ThemeOptions::enabled('imperial_newsletter_enabled')) {
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
            $record = $subscribers[$email] ?? ['email' => $email, 'created_at' => current_time('mysql')];
            $record['confirmed'] = ! empty($record['confirmed']);
            $token = bin2hex(random_bytes(32));
            $record['confirmation_hash'] = hash('sha256', $token);
            $record['confirmation_expires'] = time() + self::CONFIRMATION_TTL;
            $subscribers[$email] = $record;
            update_option(self::OPTION_KEY, $subscribers, false);

            if (get_option(self::OPTION_KEY) !== $subscribers) {
                return false;
            }

            $url = add_query_arg(['action' => 'imperial_newsletter_confirm', 'token' => $token], admin_url('admin-post.php'));
            $sent = MailDelivery::send(
                $email,
                __('Подтверди подписку на дайджест', 'imperial-future'),
                sprintf(__("Подтверди подписку по ссылке в течение 24 часов:\n%s\n\nЕсли ты не запрашивал подписку, просто проигнорируй письмо.", 'imperial-future'), $url)
            );

            if (! $sent) {
                unset($record['confirmation_hash'], $record['confirmation_expires']);
                $subscribers[$email] = $record;
                update_option(self::OPTION_KEY, $subscribers, false);
            }

            return $sent;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function confirm(string $token): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $token) || ! ThemeOptions::enabled('imperial_newsletter_enabled')) {
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
                unset($record['confirmation_hash'], $record['confirmation_expires']);
                $subscribers[$email] = $valid ? self::confirmedRecord($email, $record) : $record;
                update_option(self::OPTION_KEY, $subscribers, false);

                return $valid && get_option(self::OPTION_KEY) === $subscribers;
            }

            return false;
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
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

            self::disableUserDigest($email);

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

            // Старая явная подписка из кабинета уже имеет согласие владельца.
            foreach (get_users(['meta_key' => self::DIGEST_META, 'meta_value' => '1']) as $user) {
                $email = self::normalizeEmail((string) $user->user_email);

                if (isset($subscribers[$email]) && ! array_key_exists('confirmed', $subscribers[$email])) {
                    $subscribers[$email] = self::confirmedRecord($email, $subscribers[$email]);
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
        ];
    }

    private static function disableUserDigest(string $email): void
    {
        $user = get_user_by('email', $email);

        if ($user instanceof \WP_User) {
            update_user_meta((int) $user->ID, self::DIGEST_META, '0');
        }
    }

    private static function normalizeEmail(string $email): string
    {
        return strtolower(sanitize_email($email));
    }

    public static function unsubscribe(string $email): void
    {
        $email = self::normalizeEmail($email);
        $lock = NewsletterLock::acquire('subscriptions');

        if ($lock === false) {
            error_log('Imperial newsletter: unsubscribe blocked by subscription lock.');
            return;
        }

        try {
            $subscribers = self::subscribers();
            unset($subscribers[$email]);
            update_option(self::OPTION_KEY, $subscribers, false);

            if (get_option(self::OPTION_KEY) !== $subscribers) {
                error_log('Imperial newsletter: could not persist unsubscribe.');
                return;
            }

            self::disableUserDigest($email);
        } finally {
            NewsletterLock::release('subscriptions', $lock);
        }
    }

    public static function updateUserPreferences(int $userId, bool $digest, bool $comments, bool $replies = false): void
    {
        update_user_meta($userId, self::DIGEST_META, $digest ? '1' : '0');
        update_user_meta($userId, self::COMMENTS_META, $comments ? '1' : '0');
        update_user_meta($userId, self::REPLIES_META, $replies ? '1' : '0');

        $user = get_userdata($userId);

        if ($digest && $user instanceof \WP_User) {
            if (! self::subscribe((string) $user->user_email)) {
                error_log('Imperial newsletter: could not persist digest preference.');
            }
        } elseif ($user instanceof \WP_User) {
            self::unsubscribe((string) $user->user_email);
        }
    }

    public static function userDigestEnabled(int $userId): bool
    {
        return get_user_meta($userId, self::DIGEST_META, true) === '1';
    }

    public static function userCommentsEnabled(int $userId): bool
    {
        return get_user_meta($userId, self::COMMENTS_META, true) === '1';
    }

    public static function userRepliesEnabled(int $userId): bool
    {
        return get_user_meta($userId, self::REPLIES_META, true) === '1';
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
