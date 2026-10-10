<?php
declare(strict_types=1);

namespace ImperialFuture\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class ThemeOptions
{
    public static function defaults(): array
    {
        return [
            'imperial_color_dark_void' => '#0f0f12',
            'imperial_color_dark_panel' => '#1b1b22',
            'imperial_color_dark_gold' => '#d4af37',
            'imperial_color_dark_paper' => '#f4e7c5',
            'imperial_color_dark_bronze' => '#7a5c2e',
            'imperial_color_dark_parchment' => '#efe3c2',
            'imperial_color_dark_red' => '#6f2024',
            'imperial_color_light_void' => '#efe3c2',
            'imperial_color_light_panel' => '#f7edcf',
            'imperial_color_light_text' => '#201813',
            'imperial_color_light_gold' => '#8a6416',
            'imperial_font_heading' => 'Oranienbaum',
            'imperial_font_body' => 'Inter',
            'imperial_logo_dark' => '',
            'imperial_logo_light' => '',
            'imperial_favicon' => '',
            'imperial_auto_featured_image_enabled' => '1',
            'imperial_hero_eyebrow' => __('Элитная цифровая газета', 'imperial-future'),
            'imperial_hero_title' => '',
            'imperial_hero_lead' => __('Персональный блог как имперский архив будущего: технологии, культура, письма редактора и спокойная роскошь дореволюционной типографики.', 'imperial-future'),
            'imperial_hero_primary_label' => __('Читать хронику', 'imperial-future'),
            'imperial_hero_primary_url' => '#chronicle',
            'imperial_hero_secondary_label' => __('Связаться', 'imperial-future'),
            'imperial_hero_secondary_url' => '/contacts/',
            'imperial_hero_ticker_enabled' => '1',
            'imperial_hero_ticker_items' => __("Петроград будущего передаёт сигнал\nЛатунные протоколы готовы к чтению\nАрхивные машины сортируют новые заметки\nГазетная полоса оживает в браузере", 'imperial-future'),
            'imperial_posts_kicker' => __('Лента статей', 'imperial-future'),
            'imperial_posts_title' => __('Новые листы из цифровой типографии', 'imperial-future'),
            'imperial_posts_lead' => __('Большие материалы, заметки и письма собраны в плотную, но спокойную редакционную сетку.', 'imperial-future'),
            'imperial_posts_count' => '6',
            'imperial_telegraph_kicker' => __('Имперская хроника', 'imperial-future'),
            'imperial_telegraph_title' => __('Телеграфная лента последних публикаций', 'imperial-future'),
            'imperial_telegraph_count' => '8',
            'imperial_quote_1_text' => __('Будущее выглядит убедительнее, когда у него есть бумажная память, ясная сетка и немного золотого света.', 'imperial-future'),
            'imperial_quote_1_cite' => __('Из редакционного устава', 'imperial-future'),
            'imperial_quote_2_text' => __('Скорость страницы — это тоже форма уважения. Красота не должна заставлять ждать.', 'imperial-future'),
            'imperial_quote_2_cite' => __('Пометка на полях', 'imperial-future'),
            'imperial_author_seal' => __('А', 'imperial-future'),
            'imperial_author_kicker' => __('Авторский кабинет', 'imperial-future'),
            'imperial_author_title' => __('Блог о коде, культуре и внимании', 'imperial-future'),
            'imperial_author_text' => __('Здесь личный опыт превращается в аккуратные заметки: без суеты, с практической пользой и визуальным характером.', 'imperial-future'),
            'imperial_author_button_label' => __('Открыть кабинет', 'imperial-future'),
            'imperial_author_button_url' => '/about/',
            'imperial_effect_preloader_enabled' => '1',
            'imperial_effect_noise_enabled' => '1',
            'imperial_effect_dust_enabled' => '1',
            'imperial_contact_kicker' => __('Канцелярия связи', 'imperial-future'),
            'imperial_contact_lead' => __('Для писем, предложений и аккуратных сигналов из будущего.', 'imperial-future'),
            'imperial_contact_form_title' => __('Написать в редакцию', 'imperial-future'),
            'imperial_contact_note_title' => __('Редакционный адрес', 'imperial-future'),
            'imperial_contact_button_label' => __('Отправить письмо', 'imperial-future'),
            'imperial_contact_recipient_email' => get_option('admin_email'),
            'imperial_contact_from_name' => get_bloginfo('name'),
            'imperial_contact_from_email' => get_option('admin_email'),
            'imperial_contact_subject_prefix' => __('Письмо из Имперского архива', 'imperial-future'),
            'imperial_contact_captcha_question' => __('Сколько будет 2 + 3?', 'imperial-future'),
            'imperial_contact_captcha_answer' => '5',
            'imperial_contact_success_message' => __('Письмо принято канцелярией. Спасибо, что написали.', 'imperial-future'),
            'imperial_contact_error_message' => __('Письмо не удалось отправить. Проверьте поля и попробуйте ещё раз.', 'imperial-future'),
            'imperial_contact_captcha_enabled' => '1',
            'imperial_social_vk_url' => '',
            'imperial_social_ok_url' => '',
            'imperial_social_telegram_url' => '',
            'imperial_social_max_url' => '',
            'imperial_social_email' => get_option('admin_email'),
            'imperial_ad_after_hero_enabled' => '0',
            'imperial_ad_after_hero_code' => '',
            'imperial_ad_before_content_enabled' => '0',
            'imperial_ad_before_content_code' => '',
            'imperial_ad_after_content_enabled' => '0',
            'imperial_ad_after_content_code' => '',
            'imperial_ad_sidebar_enabled' => '0',
            'imperial_ad_sidebar_code' => '',
            'imperial_ad_footer_enabled' => '0',
            'imperial_ad_footer_code' => '',
            'imperial_counter_head_code' => '',
            'imperial_counter_body_code' => '',
            'imperial_seo_enabled' => '1',
            'imperial_seo_default_description' => __('Персональный блог о технологиях, культуре и будущем в эстетике Имперского архива.', 'imperial-future'),
            'imperial_seo_title_suffix' => '',
            'imperial_seo_og_image' => '',
            'imperial_seo_yandex_verification' => '',
            'imperial_seo_noindex_search' => '1',
            'imperial_seo_noindex_archives' => '0',
            'imperial_yandex_metrika_id' => '',
            'imperial_vk_url' => '',
            'imperial_telegram_url' => '',
            'imperial_ok_url' => '',
            'imperial_max_url' => '',
            'imperial_newsletter_enabled' => '1',
            'imperial_newsletter_title' => __('Еженедельный имперский дайджест', 'imperial-future'),
            'imperial_newsletter_text' => __('Раз в неделю: лучшие статьи, заметки из архива и спокойный редакционный шум без спама.', 'imperial-future'),
            'imperial_newsletter_button_label' => __('Подписаться', 'imperial-future'),
            'imperial_auth_enabled' => '1',
            'imperial_auth_vk_enabled' => '0',
            'imperial_auth_vk_client_id' => '',
            'imperial_auth_vk_client_secret' => '',
            'imperial_auth_yandex_enabled' => '0',
            'imperial_auth_yandex_client_id' => '',
            'imperial_auth_yandex_client_secret' => '',
            'imperial_search_enabled' => '1',
            'imperial_search_examples' => __("интерфейсы\nархив\nмашины", 'imperial-future'),
            'imperial_hero_mode' => 'static',
            'imperial_hero_slider_count' => '4',
            'imperial_hero_slider_background_mode' => 'preset',
            'imperial_hero_slider_background_preset' => 'brass',
            'imperial_hero_slider_background_image' => '',
            'imperial_hero_slider_background_css' => '',
            'imperial_rating_enabled' => '1',
            'imperial_rating_mode' => 'emoji',
            'imperial_rating_emoji_set' => '♥,🔥,👏,🤔,😂,😮,💡,⚜️',
            'imperial_external_exit_enabled' => '1',
            'imperial_external_exit_delay' => '10',
            'imperial_external_exit_bypass_masks' => "vk.com\nok.ru\nt.me\ntelegram.me\nmax.ru",
            'imperial_external_exit_warning_text' => __('Вы переходите на внешний сайт. Проверьте адрес и продолжайте только если доверяете источнику.', 'imperial-future'),
        ];
    }

    public static function get(string $key): string
    {
        $defaults = self::defaults();

        return (string) get_theme_mod($key, $defaults[$key] ?? '');
    }

    public static function enabled(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function number(string $key, int $min, int $max): int
    {
        $value = (int) self::get($key);

        return max($min, min($max, $value));
    }

    public static function lines(string $key): array
    {
        $lines = preg_split('/\r\n|\r|\n/', self::get($key));

        return array_values(array_filter(array_map('trim', is_array($lines) ? $lines : [])));
    }

    public static function link(string $key): string
    {
        $value = self::get($key);

        if ($value === '') {
            return '#';
        }

        if (str_starts_with($value, '#')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            $page = get_page_by_path(trim($value, '/'));
            if ($page instanceof \WP_Post && $page->post_status === 'publish') {
                return (string) get_permalink($page);
            }
            return home_url($value);
        }

        return $value;
    }

    public static function code(string $key): string
    {
        return (string) get_theme_mod($key, self::defaults()[$key] ?? '');
    }

    public static function mediaUrl(string $key, string $size = 'full'): string
    {
        $attachmentId = (int) self::get($key);

        if ($attachmentId <= 0) {
            return '';
        }

        $url = $size === 'full' ? wp_get_attachment_url($attachmentId) : wp_get_attachment_image_url($attachmentId, $size);

        return is_string($url) ? $url : '';
    }
}
