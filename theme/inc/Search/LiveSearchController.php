<?php
declare(strict_types=1);

namespace ImperialFuture\Search;

use ImperialFuture\Security\RateLimiter;
use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Template\TemplateTags;
use WP_Query;

if (! defined('ABSPATH')) {
    exit;
}

final class LiveSearchController
{
    public static function registerHooks(): void
    {
        // Подключаем один AJAX-вход для гостей и авторизованных читателей: бизнес-логика поиска остаётся в этом контроллере,
        // а шаблон и JavaScript получают уже нормализованный JSON.
        add_action('wp_ajax_imperial_live_search', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_imperial_live_search', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        // Сразу закрываем поиск, если владелец сайта выключил всплывающее окно в настройках темы.
        if (! ThemeOptions::enabled('imperial_search_enabled')) {
            wp_send_json_error(['message' => __('Поиск временно закрыт архивариусом.', 'imperial-future')], 403);
        }

        // Проверяем nonce до запроса к базе, чтобы живой поиск нельзя было дёргать как открытый endpoint.
        check_ajax_referer('imperial_live_search', 'nonce');

        if (! RateLimiter::allow('live_search', RateLimiter::clientIdentity(), 45, MINUTE_IN_SECONDS)) {
            wp_send_json_error(['message' => __('Поиск получил слишком много запросов. Попробуйте чуть позже.', 'imperial-future')], 429);
        }

        // Нормализуем строку поиска и ограничиваем минимальную длину тремя символами, как просил пользователь.
        $term = sanitize_text_field((string) ($_REQUEST['term'] ?? ''));

        if (mb_strlen($term) < 3) {
            wp_send_json_success(['items' => []]);
        }

        // Берём только опубликованные статьи, потому что фронтовой поиск не должен раскрывать черновики.
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            's' => $term,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);

        $items = [];

        // Превращаем WP_Post в компактный контракт для фронта, чтобы JavaScript не знал деталей WordPress.
        while ($query->have_posts()) {
            $query->the_post();
            $postId = get_the_ID();

            $items[] = [
                'title' => html_entity_decode(get_the_title($postId), ENT_QUOTES, get_bloginfo('charset')),
                'url' => get_permalink($postId),
                'excerpt' => html_entity_decode(wp_strip_all_tags(wp_trim_words(get_the_excerpt($postId), 18)), ENT_QUOTES, get_bloginfo('charset')),
                'date' => get_the_date('', $postId),
                'category' => TemplateTags::primaryCategory($postId),
                'image' => TemplateTags::featuredImageUrl($postId, 'medium_large'),
            ];
        }

        wp_reset_postdata();

        wp_send_json_success(['items' => $items]);
    }
}
