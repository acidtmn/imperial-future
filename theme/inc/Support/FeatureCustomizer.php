<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

use ImperialFuture\Settings\ThemeOptions;
use WP_Customize_Manager;

if (! defined('ABSPATH')) {
    exit;
}

final class FeatureCustomizer
{
    public static function registerHooks(): void
    {
        add_action('customize_register', [self::class, 'registerControls']);
    }

    public static function registerControls(WP_Customize_Manager $customizer): void
    {
        $customizer->add_section('imperial_future_features', [
            'title' => __('Функции публикаций', 'imperial-future'),
            'panel' => 'imperial_future_panel',
            'priority' => 35,
        ]);

        $customizer->add_section('imperial_future_access', [
            'title' => __('Кабинет, вход и поиск', 'imperial-future'),
            'panel' => 'imperial_future_panel',
            'priority' => 65,
        ]);

        self::checkbox($customizer, 'imperial_auto_featured_image_enabled', __('Автоматически создавать обложку, если у статьи нет изображения', 'imperial-future'), 'imperial_future_features');
        self::checkbox($customizer, 'imperial_newsletter_enabled', __('Показывать блок еженедельного дайджеста', 'imperial-future'), 'imperial_future_features');
        self::text($customizer, 'imperial_newsletter_title', __('Дайджест: заголовок', 'imperial-future'), 'imperial_future_features');
        self::textarea($customizer, 'imperial_newsletter_text', __('Дайджест: описание', 'imperial-future'), 'imperial_future_features');
        self::text($customizer, 'imperial_newsletter_button_label', __('Дайджест: текст кнопки', 'imperial-future'), 'imperial_future_features');

        self::checkbox($customizer, 'imperial_rating_enabled', __('Включить оценки материалов', 'imperial-future'), 'imperial_future_features');
        self::select($customizer, 'imperial_rating_mode', __('Тип оценки статьи', 'imperial-future'), 'imperial_future_features', [
            'stars' => __('Звёзды', 'imperial-future'),
            'likes' => __('Палец вверх / вниз', 'imperial-future'),
            'emoji' => __('Эмодзи', 'imperial-future'),
        ]);
        self::text($customizer, 'imperial_rating_emoji_set', __('Эмодзи через запятую', 'imperial-future'), 'imperial_future_features');

        self::select($customizer, 'imperial_hero_mode', __('Hero: режим первого экрана', 'imperial-future'), 'imperial_future_hero', [
            'static' => __('Статичный редакционный блок', 'imperial-future'),
            'slider' => __('Слайдер избранных статей', 'imperial-future'),
        ]);
        self::number($customizer, 'imperial_hero_slider_count', __('Hero: количество статей в слайдере', 'imperial-future'), 'imperial_future_hero', 2, 8);
        self::select($customizer, 'imperial_hero_slider_background_mode', __('Hero-слайдер: источник фона', 'imperial-future'), 'imperial_future_hero', [
            'preset' => __('Готовый пресет', 'imperial-future'),
            'image' => __('Своя картинка', 'imperial-future'),
            'css' => __('CSS background', 'imperial-future'),
        ]);
        self::select($customizer, 'imperial_hero_slider_background_preset', __('Hero-слайдер: пресет фона', 'imperial-future'), 'imperial_future_hero', [
            'brass' => __('Латунный архив', 'imperial-future'),
            'ruby' => __('Бордовая канцелярия', 'imperial-future'),
            'aurora' => __('Северное сияние', 'imperial-future'),
            'graphite' => __('Графит и золото', 'imperial-future'),
        ]);
        self::media($customizer, 'imperial_hero_slider_background_image', __('Hero-слайдер: своя фоновая картинка', 'imperial-future'), 'imperial_future_hero');
        self::textarea($customizer, 'imperial_hero_slider_background_css', __('Hero-слайдер: CSS background', 'imperial-future'), 'imperial_future_hero');

        self::email($customizer, 'imperial_contact_from_email', __('Почта: From email отправителя', 'imperial-future'), 'imperial_future_contacts');
        self::text($customizer, 'imperial_contact_from_name', __('Почта: имя отправителя', 'imperial-future'), 'imperial_future_contacts');

        self::checkbox($customizer, 'imperial_search_enabled', __('Включить всплывающий живой поиск', 'imperial-future'), 'imperial_future_access');
        self::textarea($customizer, 'imperial_search_examples', __('Поиск: примеры запросов, каждый с новой строки', 'imperial-future'), 'imperial_future_access');
        self::checkbox($customizer, 'imperial_auth_enabled', __('Включить мини-кабинет и всплывающую форму входа', 'imperial-future'), 'imperial_future_access');

        self::checkbox($customizer, 'imperial_auth_vk_enabled', __('Вход через VK', 'imperial-future'), 'imperial_future_integrations');
        self::text($customizer, 'imperial_auth_vk_client_id', __('VK OAuth Client ID', 'imperial-future'), 'imperial_future_integrations');
        self::text($customizer, 'imperial_auth_vk_client_secret', __('VK OAuth Secret', 'imperial-future'), 'imperial_future_integrations');
        self::checkbox($customizer, 'imperial_auth_yandex_enabled', __('Вход через Яндекс', 'imperial-future'), 'imperial_future_integrations');
        self::text($customizer, 'imperial_auth_yandex_client_id', __('Яндекс OAuth Client ID', 'imperial-future'), 'imperial_future_integrations');
        self::text($customizer, 'imperial_auth_yandex_client_secret', __('Яндекс OAuth Secret', 'imperial-future'), 'imperial_future_integrations');
    }

    private static function checkbox(WP_Customize_Manager $customizer, string $id, string $label, string $section): void
    {
        // Переключатели сохраняются строками 1/0 для ThemeOptions::enabled().
        self::setting($customizer, $id, static fn ($value): string => $value === '1' ? '1' : '0');
        $customizer->add_control($id, [
            'type' => 'checkbox',
            'section' => $section,
            'label' => $label,
        ]);
    }

    private static function text(WP_Customize_Manager $customizer, string $id, string $label, string $section): void
    {
        self::setting($customizer, $id, 'sanitize_text_field');
        $customizer->add_control($id, [
            'type' => 'text',
            'section' => $section,
            'label' => $label,
        ]);
    }

    private static function email(WP_Customize_Manager $customizer, string $id, string $label, string $section): void
    {
        self::setting($customizer, $id, 'sanitize_email');
        $customizer->add_control($id, [
            'type' => 'email',
            'section' => $section,
            'label' => $label,
        ]);
    }

    private static function textarea(WP_Customize_Manager $customizer, string $id, string $label, string $section): void
    {
        self::setting($customizer, $id, 'sanitize_textarea_field');
        $customizer->add_control($id, [
            'type' => 'textarea',
            'section' => $section,
            'label' => $label,
        ]);
    }

    private static function number(WP_Customize_Manager $customizer, string $id, string $label, string $section, int $min, int $max): void
    {
        self::setting($customizer, $id, static fn ($value): string => (string) max($min, min($max, (int) $value)));
        $customizer->add_control($id, [
            'type' => 'number',
            'section' => $section,
            'label' => $label,
            'input_attrs' => [
                'min' => $min,
                'max' => $max,
            ],
        ]);
    }

    private static function media(WP_Customize_Manager $customizer, string $id, string $label, string $section): void
    {
        self::setting($customizer, $id, static fn ($value): string => (string) absint($value));
        $customizer->add_control(new \WP_Customize_Media_Control($customizer, $id, [
            'section' => $section,
            'label' => $label,
            'mime_type' => 'image',
        ]));
    }

    private static function select(WP_Customize_Manager $customizer, string $id, string $label, string $section, array $choices): void
    {
        self::setting($customizer, $id, static fn ($value): string => array_key_exists((string) $value, $choices) ? (string) $value : (string) array_key_first($choices));
        $customizer->add_control($id, [
            'type' => 'select',
            'section' => $section,
            'label' => $label,
            'choices' => $choices,
        ]);
    }

    private static function setting(WP_Customize_Manager $customizer, string $id, callable|string $sanitizeCallback): void
    {
        $defaults = ThemeOptions::defaults();
        $customizer->add_setting($id, [
            'default' => $defaults[$id] ?? '',
            'transport' => 'refresh',
            'sanitize_callback' => $sanitizeCallback,
        ]);
    }
}
