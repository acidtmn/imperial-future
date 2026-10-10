<?php
declare(strict_types=1);

namespace ImperialFuture\Share;

if (! defined('ABSPATH')) {
    exit;
}

final class ShareLinks
{
    public static function currentPost(): array
    {
        $url = rawurlencode((string) get_permalink());
        $title = rawurlencode(wp_strip_all_tags((string) get_the_title()));

        return [
            [
                'label' => __('ВК', 'imperial-future'),
                'url' => "https://vk.com/share.php?url={$url}&title={$title}",
                'icon' => SocialIcon::url('vk'),
            ],
            [
                'label' => __('ОК', 'imperial-future'),
                'url' => "https://connect.ok.ru/offer?url={$url}&title={$title}",
                'icon' => SocialIcon::url('ok'),
            ],
            [
                'label' => __('Телеграм', 'imperial-future'),
                'url' => "https://t.me/share/url?url={$url}&text={$title}",
                'icon' => SocialIcon::url('telegram'),
            ],
            [
                'label' => __('МАКС', 'imperial-future'),
                'url' => "https://max.ru/:share?text={$title}%20{$url}",
                'icon' => SocialIcon::url('max'),
            ],
            [
                'label' => __('Почта', 'imperial-future'),
                'url' => "mailto:?subject={$title}&body={$url}",
                'icon' => SocialIcon::url('email'),
            ],
        ];
    }
}
