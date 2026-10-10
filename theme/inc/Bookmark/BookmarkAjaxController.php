<?php
declare(strict_types=1);

namespace ImperialFuture\Bookmark;

use ImperialFuture\Security\RateLimiter;

if (! defined('ABSPATH')) {
    exit;
}

final class BookmarkAjaxController
{
    public static function registerHooks(): void
    {
        add_action('wp_ajax_imperial_toggle_bookmark', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_imperial_toggle_bookmark', [self::class, 'loginRequired']);
    }

    public static function loginRequired(): void
    {
        wp_send_json_error([
            'message' => __('Войдите, чтобы сохранить публикацию.', 'imperial-future'),
            'loginRequired' => true,
        ], 401);
    }

    public static function handle(): void
    {
        check_ajax_referer('imperial_toggle_bookmark', 'nonce');

        if (! is_user_logged_in()) {
            self::loginRequired();
        }

        if (! RateLimiter::allow('bookmark_toggle', RateLimiter::userOrClientIdentity(), 90, MINUTE_IN_SECONDS)) {
            wp_send_json_error(['message' => __('Слишком много быстрых действий с закладками. Попробуйте чуть позже.', 'imperial-future')], 429);
        }

        $postId = (int) ($_POST['post_id'] ?? 0);

        if ($postId <= 0 || get_post_status($postId) !== 'publish') {
            wp_send_json_error(['message' => __('Материал не найден.', 'imperial-future')], 404);
        }

        wp_send_json_success([
            'bookmarked' => BookmarkService::toggle(get_current_user_id(), $postId),
        ]);
    }
}
