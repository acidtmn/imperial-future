<?php
declare(strict_types=1);

namespace ImperialFuture\Share;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class SocialLinks
{
    public static function author(): array
    {
        $links = [
            'vk' => ['label' => __('ВК', 'imperial-future'), 'url' => self::socialUrl(ThemeOptions::get('imperial_social_vk_url')), 'icon' => SocialIcon::url('vk')],
            'ok' => ['label' => __('ОК', 'imperial-future'), 'url' => self::socialUrl(ThemeOptions::get('imperial_social_ok_url')), 'icon' => SocialIcon::url('ok')],
            'telegram' => ['label' => __('Телеграм', 'imperial-future'), 'url' => self::telegramUrl(ThemeOptions::get('imperial_social_telegram_url')), 'icon' => SocialIcon::url('telegram')],
            'max' => ['label' => __('МАКС', 'imperial-future'), 'url' => self::socialUrl(ThemeOptions::get('imperial_social_max_url')), 'icon' => SocialIcon::url('max')],
            'email' => ['label' => __('Почта', 'imperial-future'), 'url' => self::emailUrl(ThemeOptions::get('imperial_social_email')), 'icon' => SocialIcon::url('email')],
        ];

        return array_values(array_filter($links, static fn (array $link): bool => trim($link['url']) !== ''));
    }

    public static function contact(): array
    {
        $links = [
            'vk' => ['label' => __('ВК', 'imperial-future'), 'url' => self::socialUrl(ThemeOptions::get('imperial_vk_url')), 'icon' => SocialIcon::url('vk')],
            'ok' => ['label' => __('ОК', 'imperial-future'), 'url' => self::socialUrl(ThemeOptions::get('imperial_ok_url')), 'icon' => SocialIcon::url('ok')],
            'telegram' => ['label' => __('Телеграм', 'imperial-future'), 'url' => self::telegramUrl(ThemeOptions::get('imperial_telegram_url')), 'icon' => SocialIcon::url('telegram')],
            'max' => ['label' => __('МАКС', 'imperial-future'), 'url' => self::socialUrl(ThemeOptions::get('imperial_max_url')), 'icon' => SocialIcon::url('max')],
        ];

        return array_values(array_filter($links, static fn (array $link): bool => trim($link['url']) !== ''));
    }

    private static function emailUrl(string $email): string
    {
        $email = sanitize_email($email);

        return $email !== '' ? 'mailto:' . $email : '';
    }

    private static function telegramUrl(string $url): string
    {
        $url = trim($url);

        if (str_starts_with($url, '@')) {
            return 'https://t.me/' . ltrim($url, '@');
        }

        return self::socialUrl($url);
    }

    private static function socialUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $url) === 1) {
            return $url;
        }

        return 'https://' . ltrim($url, '/');
    }
}
