<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

use ImperialFuture\Auth\AccountPageRegistrar;
use ImperialFuture\Security\RateLimiter;
use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class NewsletterHandler
{
    public static function registerHooks(): void
    {
        add_action('admin_post_imperial_newsletter_subscribe', [self::class, 'subscribe']);
        add_action('admin_post_nopriv_imperial_newsletter_subscribe', [self::class, 'subscribe']);
        add_action('admin_post_imperial_newsletter_confirm', [self::class, 'confirm']);
        add_action('admin_post_nopriv_imperial_newsletter_confirm', [self::class, 'confirm']);
        add_action('admin_post_imperial_newsletter_unsubscribe', [self::class, 'unsubscribe']);
        add_action('admin_post_nopriv_imperial_newsletter_unsubscribe', [self::class, 'unsubscribe']);
    }

    public static function subscribe(): never
    {
        $redirectUrl = wp_get_referer() ?: home_url('/');

        self::redirect($redirectUrl, self::subscriptionStatus(wp_unslash($_POST)));
    }

    public static function subscriptionStatus(array $input): string
    {
        $purpose = is_string($input['purpose'] ?? null) ? $input['purpose'] : 'newsletter';
        if (! in_array($purpose, ['email', 'newsletter'], true)
            || ($purpose === 'newsletter' && ! ThemeOptions::enabled('imperial_newsletter_enabled'))
            || ! isset($input['_wpnonce']) || ! is_string($input['_wpnonce']) || ! wp_verify_nonce($input['_wpnonce'], 'imperial_newsletter')) {
            return 'error';
        }

        if (! RateLimiter::allow('newsletter_subscribe', RateLimiter::clientIdentity(), 6, HOUR_IN_SECONDS)) {
            return 'error';
        }

        $email = is_string($input['newsletter_email'] ?? null) ? strtolower(sanitize_email($input['newsletter_email'])) : '';
        $user = wp_get_current_user();

        if ($purpose === 'newsletter' && $user->exists() && strtolower($user->user_email) === $email && SubscriptionService::isEmailVerified((int) $user->ID)) {
            if (! SubscriptionService::subscribe($email, (int) $user->ID)) {
                return 'error';
            }

            update_user_meta((int) $user->ID, SubscriptionService::DIGEST_META, '1');

            return 'subscribed';
        }

        return SubscriptionService::requestConfirmation($email, $purpose)
            ? ($purpose === 'email' ? 'email_confirmation_sent' : 'confirmation_sent') : 'error';
    }

    public static function confirm(): never
    {
        $token = is_string($_GET['token'] ?? null) ? wp_unslash($_GET['token']) : '';
        $purpose = SubscriptionService::confirmationPurpose($token);
        $confirmed = SubscriptionService::confirm($token);
        $url = is_user_logged_in() ? AccountPageRegistrar::url() : home_url('/');
        self::redirect($url, $confirmed ? ($purpose === 'email' ? 'email_confirmed' : 'subscribed') : 'error');
    }

    public static function unsubscribe(): never
    {
        $email = is_string($_GET['email'] ?? null) ? wp_unslash($_GET['email']) : '';
        $signature = is_string($_GET['signature'] ?? null) ? wp_unslash($_GET['signature']) : '';
        $result = SubscriptionService::unsubscribeSigned($email, $signature);

        // Ошибочная и уже использованная ссылка не раскрывают наличие подписки.
        self::redirect(home_url('/'), $result === null ? 'error' : 'unsubscribed');
    }

    private static function redirect(string $redirectUrl, string $status): never
    {
        $cleanUrl = remove_query_arg(['newsletter_status'], $redirectUrl);

        wp_safe_redirect(add_query_arg('newsletter_status', $status, $cleanUrl));
        exit;
    }
}
