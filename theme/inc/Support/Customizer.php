<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;
use WP_Customize_Manager;

if (! defined('ABSPATH')) {
    exit;
}

final class Customizer
{
    public static function registerHooks(): void
    {
        add_action('customize_register', [self::class, 'registerControls']);
    }

    public static function registerControls(WP_Customize_Manager $customizer): void
    {
        $customizer->add_panel('imperial_future_panel', [
            'title' => __('Imperial Future: настройки темы', 'imperial-future'),
            'description' => __('Премиум-настройки бренда, главной страницы, рекламы, SEO, контактов и интеграций.', 'imperial-future'),
            'priority' => 30,
        ]);

        self::addSection($customizer, 'imperial_future_palette', __('Палитра и светлая тема', 'imperial-future'), 10);
        self::addSection($customizer, 'imperial_future_branding', __('Бренд, логотипы и шрифты', 'imperial-future'), 15);
        self::addSection($customizer, 'imperial_future_hero', __('Главная: hero и CTA', 'imperial-future'), 20);
        self::addSection($customizer, 'imperial_future_home', __('Главная: блоки и цитаты', 'imperial-future'), 30);
        self::addSection($customizer, 'imperial_future_effects', __('Эффекты и анимации', 'imperial-future'), 40);
        self::addSection($customizer, 'imperial_future_contacts', __('Контакты и капча', 'imperial-future'), 50);
        self::addSection($customizer, 'imperial_future_socials', __('Социальные сети автора', 'imperial-future'), 55);
        self::addSection($customizer, 'imperial_future_integrations', __('Интеграции', 'imperial-future'), 60);
        self::addSection($customizer, 'imperial_future_ads', __('Рекламные блоки', 'imperial-future'), 70);
        self::addSection($customizer, 'imperial_future_counters', __('Счётчики и внешние скрипты', 'imperial-future'), 80);
        self::addSection($customizer, 'imperial_future_seo', __('SEO, OpenGraph и Яндекс', 'imperial-future'), 90);

        self::registerPaletteControls($customizer);
        self::registerBrandingControls($customizer);
        self::registerHeroControls($customizer);
        self::registerHomeControls($customizer);
        self::registerEffectControls($customizer);
        self::registerContactControls($customizer);
        self::registerSocialControls($customizer);
        self::registerIntegrationControls($customizer);
        self::registerAdControls($customizer);
        self::registerCounterControls($customizer);
        self::registerSeoControls($customizer);
    }

    private static function registerPaletteControls(WP_Customize_Manager $customizer): void
    {
        self::addColorControl($customizer, 'imperial_color_dark_void', __('Тёмная тема: фон', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_dark_panel', __('Тёмная тема: панели', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_dark_gold', __('Тёмная тема: золото', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_dark_paper', __('Тёмная тема: текст-заголовки', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_dark_bronze', __('Тёмная тема: бронза', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_dark_parchment', __('Тёмная тема: пергамент', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_dark_red', __('Тёмная тема: акцент бордо', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_light_void', __('Светлая тема: фон', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_light_panel', __('Светлая тема: панели', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_light_text', __('Светлая тема: основной текст', 'imperial-future'), 'imperial_future_palette');
        self::addColorControl($customizer, 'imperial_color_light_gold', __('Светлая тема: золото', 'imperial-future'), 'imperial_future_palette');
    }

    private static function registerBrandingControls(WP_Customize_Manager $customizer): void
    {
        self::addSelectControl($customizer, 'imperial_font_heading', __('Шрифт заголовков', 'imperial-future'), 'imperial_future_branding', [
            'Oranienbaum' => 'Oranienbaum',
            'Forum' => 'Forum',
            'Prata' => 'Prata',
        ]);
        self::addSelectControl($customizer, 'imperial_font_body', __('Шрифт текста', 'imperial-future'), 'imperial_future_branding', [
            'Inter' => 'Inter',
            'PT Sans' => 'PT Sans',
            'IBM Plex Sans' => 'IBM Plex Sans',
        ]);
        self::addMediaControl($customizer, 'imperial_logo_dark', __('Логотип для тёмной темы', 'imperial-future'), 'imperial_future_branding');
        self::addMediaControl($customizer, 'imperial_logo_light', __('Логотип для светлой темы', 'imperial-future'), 'imperial_future_branding');
        self::addMediaControl($customizer, 'imperial_favicon', __('Favicon', 'imperial-future'), 'imperial_future_branding');
    }

    private static function registerHeroControls(WP_Customize_Manager $customizer): void
    {
        self::addTextControl($customizer, 'imperial_hero_eyebrow', __('Hero: надзаголовок', 'imperial-future'), 'imperial_future_hero');
        self::addTextControl($customizer, 'imperial_hero_title', __('Hero: заголовок (пусто = название сайта)', 'imperial-future'), 'imperial_future_hero');
        self::addTextareaControl($customizer, 'imperial_hero_lead', __('Hero: описание', 'imperial-future'), 'imperial_future_hero');
        self::addTextControl($customizer, 'imperial_hero_primary_label', __('Hero: текст первой кнопки', 'imperial-future'), 'imperial_future_hero');
        self::addTextControl($customizer, 'imperial_hero_primary_url', __('Hero: ссылка первой кнопки', 'imperial-future'), 'imperial_future_hero');
        self::addTextControl($customizer, 'imperial_hero_secondary_label', __('Hero: текст второй кнопки', 'imperial-future'), 'imperial_future_hero');
        self::addTextControl($customizer, 'imperial_hero_secondary_url', __('Hero: ссылка второй кнопки', 'imperial-future'), 'imperial_future_hero');
        self::addCheckboxControl($customizer, 'imperial_hero_ticker_enabled', __('Показывать живую газетную строку', 'imperial-future'), 'imperial_future_hero');
        self::addTextareaControl($customizer, 'imperial_hero_ticker_items', __('Строки бегущей ленты, каждая с новой строки', 'imperial-future'), 'imperial_future_hero');
    }

    private static function registerHomeControls(WP_Customize_Manager $customizer): void
    {
        self::addTextControl($customizer, 'imperial_posts_kicker', __('Лента: надзаголовок', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_posts_title', __('Лента: заголовок', 'imperial-future'), 'imperial_future_home');
        self::addTextareaControl($customizer, 'imperial_posts_lead', __('Лента: описание', 'imperial-future'), 'imperial_future_home');
        self::addNumberControl($customizer, 'imperial_posts_count', __('Лента: количество карточек', 'imperial-future'), 'imperial_future_home', 3, 12);
        self::addTextControl($customizer, 'imperial_telegraph_kicker', __('Телеграф: надзаголовок', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_telegraph_title', __('Телеграф: заголовок', 'imperial-future'), 'imperial_future_home');
        self::addNumberControl($customizer, 'imperial_telegraph_count', __('Телеграф: количество записей', 'imperial-future'), 'imperial_future_home', 3, 16);
        self::addTextareaControl($customizer, 'imperial_quote_1_text', __('Цитата 1: текст', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_quote_1_cite', __('Цитата 1: подпись', 'imperial-future'), 'imperial_future_home');
        self::addTextareaControl($customizer, 'imperial_quote_2_text', __('Цитата 2: текст', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_quote_2_cite', __('Цитата 2: подпись', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_author_seal', __('Автор: символ печати', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_author_kicker', __('Автор: надзаголовок', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_author_title', __('Автор: заголовок', 'imperial-future'), 'imperial_future_home');
        self::addTextareaControl($customizer, 'imperial_author_text', __('Автор: описание', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_author_button_label', __('Автор: текст кнопки', 'imperial-future'), 'imperial_future_home');
        self::addTextControl($customizer, 'imperial_author_button_url', __('Автор: ссылка кнопки', 'imperial-future'), 'imperial_future_home');
    }

    private static function registerEffectControls(WP_Customize_Manager $customizer): void
    {
        self::addCheckboxControl($customizer, 'imperial_effect_preloader_enabled', __('Показывать кинематографичный preload', 'imperial-future'), 'imperial_future_effects');
        self::addCheckboxControl($customizer, 'imperial_effect_noise_enabled', __('Показывать шум плёнки', 'imperial-future'), 'imperial_future_effects');
        self::addCheckboxControl($customizer, 'imperial_effect_dust_enabled', __('Показывать пыль в воздухе', 'imperial-future'), 'imperial_future_effects');
    }

    private static function registerContactControls(WP_Customize_Manager $customizer): void
    {
        self::addTextControl($customizer, 'imperial_contact_kicker', __('Контакты: надзаголовок', 'imperial-future'), 'imperial_future_contacts');
        self::addTextareaControl($customizer, 'imperial_contact_lead', __('Контакты: описание', 'imperial-future'), 'imperial_future_contacts');
        self::addTextControl($customizer, 'imperial_contact_form_title', __('Форма: заголовок', 'imperial-future'), 'imperial_future_contacts');
        self::addTextControl($customizer, 'imperial_contact_note_title', __('Боковой блок: заголовок', 'imperial-future'), 'imperial_future_contacts');
        self::addTextControl($customizer, 'imperial_contact_button_label', __('Форма: текст кнопки', 'imperial-future'), 'imperial_future_contacts');
        self::addEmailControl($customizer, 'imperial_contact_recipient_email', __('Форма: email получателя', 'imperial-future'), 'imperial_future_contacts');
        self::addTextControl($customizer, 'imperial_contact_subject_prefix', __('Форма: префикс темы письма', 'imperial-future'), 'imperial_future_contacts');
        self::addCheckboxControl($customizer, 'imperial_contact_captcha_enabled', __('Включить имперскую капчу-картинку', 'imperial-future'), 'imperial_future_contacts');
        self::addTextareaControl($customizer, 'imperial_contact_success_message', __('Сообщение после отправки', 'imperial-future'), 'imperial_future_contacts');
        self::addTextareaControl($customizer, 'imperial_contact_error_message', __('Сообщение при ошибке', 'imperial-future'), 'imperial_future_contacts');
    }

    private static function registerSocialControls(WP_Customize_Manager $customizer): void
    {
        self::addTextControl($customizer, 'imperial_social_vk_url', __('Автор: ссылка ВК', 'imperial-future'), 'imperial_future_socials');
        self::addTextControl($customizer, 'imperial_social_ok_url', __('Автор: ссылка ОК', 'imperial-future'), 'imperial_future_socials');
        self::addTextControl($customizer, 'imperial_social_telegram_url', __('Автор: ссылка Telegram', 'imperial-future'), 'imperial_future_socials');
        self::addTextControl($customizer, 'imperial_social_max_url', __('Автор: ссылка МАКС', 'imperial-future'), 'imperial_future_socials');
        self::addEmailControl($customizer, 'imperial_social_email', __('Автор: email', 'imperial-future'), 'imperial_future_socials');
    }

    private static function registerIntegrationControls(WP_Customize_Manager $customizer): void
    {
        self::addTextControl($customizer, 'imperial_yandex_metrika_id', __('ID Яндекс.Метрики', 'imperial-future'), 'imperial_future_integrations');
        self::addTextControl($customizer, 'imperial_vk_url', __('Ссылка VK для блока контактов', 'imperial-future'), 'imperial_future_integrations');
        self::addTextControl($customizer, 'imperial_telegram_url', __('Ссылка Telegram для блока контактов', 'imperial-future'), 'imperial_future_integrations');
        self::addTextControl($customizer, 'imperial_ok_url', __('Ссылка ОК для блока контактов', 'imperial-future'), 'imperial_future_integrations');
        self::addTextControl($customizer, 'imperial_max_url', __('Ссылка МАКС для блока контактов', 'imperial-future'), 'imperial_future_integrations');
        self::addCheckboxControl($customizer, 'imperial_external_exit_enabled', __('Показывать страницу предупреждения для внешних ссылок', 'imperial-future'), 'imperial_future_integrations');
        self::addNumberControl($customizer, 'imperial_external_exit_delay', __('Внешние ссылки: задержка перехода в секундах', 'imperial-future'), 'imperial_future_integrations', 0, 60);
        self::addTextareaControl($customizer, 'imperial_external_exit_bypass_masks', __('Внешние ссылки: домены без предупреждения, каждый с новой строки, можно *.site.ru', 'imperial-future'), 'imperial_future_integrations');
        self::addTextareaControl($customizer, 'imperial_external_exit_warning_text', __('Внешние ссылки: текст предупреждения', 'imperial-future'), 'imperial_future_integrations');
    }

    private static function registerAdControls(WP_Customize_Manager $customizer): void
    {
        foreach (self::adPositions() as $position => $label) {
            self::addCheckboxControl($customizer, 'imperial_ad_' . $position . '_enabled', sprintf(__('Реклама: включить позицию «%s»', 'imperial-future'), $label), 'imperial_future_ads');
            self::addCodeControl($customizer, 'imperial_ad_' . $position . '_code', sprintf(__('Реклама: код позиции «%s»', 'imperial-future'), $label), 'imperial_future_ads');
        }
    }

    private static function registerCounterControls(WP_Customize_Manager $customizer): void
    {
        self::addCodeControl($customizer, 'imperial_counter_head_code', __('Код счётчиков перед </head>', 'imperial-future'), 'imperial_future_counters');
        self::addCodeControl($customizer, 'imperial_counter_body_code', __('Код счётчиков сразу после <body>', 'imperial-future'), 'imperial_future_counters');
    }

    private static function registerSeoControls(WP_Customize_Manager $customizer): void
    {
        self::addCheckboxControl($customizer, 'imperial_seo_enabled', __('Включить встроенный SEO-вывод темы', 'imperial-future'), 'imperial_future_seo');
        self::addTextareaControl($customizer, 'imperial_seo_default_description', __('SEO: описание по умолчанию', 'imperial-future'), 'imperial_future_seo');
        self::addTextControl($customizer, 'imperial_seo_title_suffix', __('SEO: суффикс title', 'imperial-future'), 'imperial_future_seo');
        self::addMediaControl($customizer, 'imperial_seo_og_image', __('OpenGraph: изображение по умолчанию', 'imperial-future'), 'imperial_future_seo');
        self::addTextControl($customizer, 'imperial_seo_yandex_verification', __('Яндекс.Вебмастер: verification meta content', 'imperial-future'), 'imperial_future_seo');
        self::addCheckboxControl($customizer, 'imperial_seo_noindex_search', __('Noindex для страницы поиска', 'imperial-future'), 'imperial_future_seo');
        self::addCheckboxControl($customizer, 'imperial_seo_noindex_archives', __('Noindex для архивов', 'imperial-future'), 'imperial_future_seo');
    }

    private static function addSection(WP_Customize_Manager $customizer, string $id, string $title, int $priority): void
    {
        $customizer->add_section($id, [
            'title' => $title,
            'panel' => 'imperial_future_panel',
            'priority' => $priority,
        ]);
    }

    private static function addColorControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, 'sanitize_hex_color');

        $customizer->add_control(new \WP_Customize_Color_Control($customizer, $settingId, [
            'label' => $label,
            'section' => $section,
        ]));
    }

    private static function addTextControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, 'sanitize_text_field');

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'text',
        ]);
    }

    private static function addEmailControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, 'sanitize_email');

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'email',
        ]);
    }

    private static function addTextareaControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, 'sanitize_textarea_field');

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'textarea',
        ]);
    }

    private static function addCodeControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, [ThemeCodeSanitizer::class, 'sanitize']);

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'textarea',
            'input_attrs' => [
                'rows' => 8,
                'spellcheck' => 'false',
            ],
        ]);
    }

    private static function addCheckboxControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, [self::class, 'sanitizeCheckbox']);

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'checkbox',
        ]);
    }

    private static function addMediaControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section): void
    {
        self::addSetting($customizer, $settingId, 'absint');

        $customizer->add_control(new \WP_Customize_Media_Control($customizer, $settingId, [
            'label' => $label,
            'section' => $section,
            'mime_type' => 'image',
        ]));
    }

    private static function addSelectControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section, array $choices): void
    {
        self::addSetting($customizer, $settingId, static fn (mixed $value): string => self::sanitizeSelect($value, $choices));

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'select',
            'choices' => $choices,
        ]);
    }

    private static function addNumberControl(WP_Customize_Manager $customizer, string $settingId, string $label, string $section, int $min, int $max): void
    {
        self::addSetting($customizer, $settingId, 'absint');

        $customizer->add_control($settingId, [
            'label' => $label,
            'section' => $section,
            'type' => 'number',
            'input_attrs' => [
                'min' => $min,
                'max' => $max,
                'step' => 1,
            ],
        ]);
    }

    private static function addSetting(WP_Customize_Manager $customizer, string $settingId, callable|string $sanitizeCallback): void
    {
        $defaults = ThemeOptions::defaults();

        $customizer->add_setting($settingId, [
            'default' => $defaults[$settingId] ?? '',
            'sanitize_callback' => $sanitizeCallback,
            'transport' => 'refresh',
        ]);
    }

    private static function adPositions(): array
    {
        return [
            'after_hero' => __('после hero', 'imperial-future'),
            'before_content' => __('перед контентом', 'imperial-future'),
            'after_content' => __('после контента', 'imperial-future'),
            'sidebar' => __('сайдбар статьи', 'imperial-future'),
            'footer' => __('подвал сайта', 'imperial-future'),
        ];
    }

    public static function sanitizeCheckbox(mixed $value): string
    {
        return (bool) $value ? '1' : '0';
    }

    private static function sanitizeSelect(mixed $value, array $choices): string
    {
        $value = (string) $value;

        return array_key_exists($value, $choices) ? $value : (string) array_key_first($choices);
    }
}
