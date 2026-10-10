<?php
declare(strict_types=1);

namespace ImperialFuture\Contact;

use ImperialFuture\Security\RateLimiter;
use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class ContactFormHandler
{
    public static function registerHooks(): void
    {
        add_action('admin_post_imperial_contact_submit', [self::class, 'handle']);
        add_action('admin_post_nopriv_imperial_contact_submit', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        $redirectUrl = self::redirectUrl();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::redirect($redirectUrl, 'error');
        }

        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce((string) $_POST['_wpnonce'], 'imperial_contact_form')) {
            self::redirect($redirectUrl, 'error');
        }

        if (! RateLimiter::allow('contact_form', RateLimiter::clientIdentity(), 5, 15 * MINUTE_IN_SECONDS)) {
            self::redirect($redirectUrl, 'rate_limited');
        }

        $data = self::sanitize(wp_unslash($_POST));

        if ($data['website'] !== '') {
            self::redirect($redirectUrl, 'sent');
        }

        if (! self::isValid($data)) {
            self::redirect($redirectUrl, 'error');
        }

        if (! self::captchaIsValid($data['captcha_token'], $data['captcha'], $data['captcha_drag'])) {
            self::redirect($redirectUrl, 'captcha');
        }

        $recipient = sanitize_email(ThemeOptions::get('imperial_contact_recipient_email')) ?: get_option('admin_email');
        $fromName = sanitize_text_field(ThemeOptions::get('imperial_contact_from_name')) ?: get_bloginfo('name');
        $fromEmail = sanitize_email(ThemeOptions::get('imperial_contact_from_email')) ?: $recipient;
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
        ];
        $sent = wp_mail($recipient, ContactMessageBuilder::subject($data['subject']), ContactMessageBuilder::body($data), $headers);

        self::redirect($redirectUrl, $sent ? 'sent' : 'mail_error');
    }

    private static function sanitize(array $input): array
    {
        return [
            'name' => sanitize_text_field((string) ($input['name'] ?? '')),
            'email' => sanitize_email((string) ($input['email'] ?? '')),
            'subject' => sanitize_text_field((string) ($input['subject'] ?? '')),
            'message' => sanitize_textarea_field((string) ($input['message'] ?? '')),
            'captcha' => sanitize_text_field((string) ($input['captcha'] ?? '')),
            'captcha_token' => sanitize_key((string) ($input['captcha_token'] ?? '')),
            'captcha_drag' => sanitize_text_field((string) ($input['captcha_drag'] ?? '')),
            'website' => sanitize_text_field((string) ($input['website'] ?? '')),
            'source_url' => esc_url_raw((string) ($input['source_url'] ?? '')),
        ];
    }

    private static function isValid(array $data): bool
    {
        return $data['name'] !== ''
            && is_email($data['email'])
            && $data['message'] !== ''
            && strlen($data['message']) <= 20000;
    }

    private static function captchaIsValid(string $token, string $answer, string $dragValue): bool
    {
        if (! ThemeOptions::enabled('imperial_contact_captcha_enabled')) {
            return true;
        }

        return CaptchaService::validate($token, $answer, $dragValue);
    }

    private static function redirectUrl(): string
    {
        $referer = wp_get_referer();

        return $referer ?: home_url('/contacts/');
    }

    private static function redirect(string $redirectUrl, string $status): never
    {
        $cleanUrl = remove_query_arg(['contact_status'], $redirectUrl);

        wp_safe_redirect(add_query_arg('contact_status', $status, $cleanUrl));
        exit;
    }
}
