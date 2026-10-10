<?php
declare(strict_types=1);

namespace ImperialFuture\Rating;

use ImperialFuture\Security\RateLimiter;
use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class RatingAjaxController
{
    public static function registerHooks(): void
    {
        add_action('wp_ajax_imperial_rate_post', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_imperial_rate_post', [self::class, 'loginRequired']);
    }

    public static function loginRequired(): void
    {
        wp_send_json_error([
            'message' => __('Войдите в кабинет, чтобы поставить оценку.', 'imperial-future'),
            'loginRequired' => true,
        ], 401);
    }

    public static function handle(): void
    {
        if (! ThemeOptions::enabled('imperial_rating_enabled')) {
            wp_send_json_error(['message' => __('Оценки сейчас отключены.', 'imperial-future')], 403);
        }

        check_ajax_referer('imperial_rate_post', 'nonce');

        if (! is_user_logged_in()) {
            self::loginRequired();
        }

        if (! RateLimiter::allow('post_rating', RateLimiter::userOrClientIdentity(), 90, MINUTE_IN_SECONDS)) {
            wp_send_json_error(['message' => __('Слишком много оценок подряд. Попробуйте чуть позже.', 'imperial-future')], 429);
        }

        $postId = (int) ($_POST['post_id'] ?? 0);
        $value = sanitize_text_field((string) ($_POST['value'] ?? ''));

        if ($postId <= 0 || get_post_status($postId) !== 'publish') {
            wp_send_json_error(['message' => __('Материал не найден.', 'imperial-future')], 404);
        }

        if (! array_key_exists($value, RatingService::options())) {
            wp_send_json_error(['message' => __('Такой реакции в рейтинге нет.', 'imperial-future')], 422);
        }

        $counts = RatingService::setUserVote($postId, get_current_user_id(), $value);
        $voted = RatingService::votedValue($postId);

        wp_send_json_success([
            'counts' => $counts,
            'total' => RatingService::total($counts),
            'average' => RatingService::average($counts),
            'voted' => $voted,
            'votedLabel' => RatingService::label($voted),
            'voters' => RatingService::voters($postId),
        ]);
    }
}
