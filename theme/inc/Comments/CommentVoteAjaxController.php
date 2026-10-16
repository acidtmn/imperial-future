<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use ImperialFuture\Security\RateLimiter;
use WP_Comment;

if (! defined('ABSPATH')) {
    exit;
}

final class CommentVoteAjaxController
{
    public static function registerHooks(): void
    {
        add_action('wp_ajax_imperial_vote_comment', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_imperial_vote_comment', [self::class, 'loginRequired']);
    }

    public static function loginRequired(): void
    {
        wp_send_json_error([
            'message' => __('Войдите, чтобы оценить комментарий.', 'imperial-future'),
            'loginRequired' => true,
        ], 401);
    }

    public static function handle(): void
    {
        check_ajax_referer('imperial_vote_comment', 'nonce');

        if (! is_user_logged_in()) {
            self::loginRequired();
        }

        if (! RateLimiter::allow('comment_vote', RateLimiter::userOrClientIdentity(), 90, MINUTE_IN_SECONDS)) {
            wp_send_json_error(['message' => __('Слишком много оценок комментариев подряд. Попробуйте чуть позже.', 'imperial-future')], 429);
        }

        $commentId = (int) ($_POST['comment_id'] ?? 0);
        $value = sanitize_key((string) ($_POST['value'] ?? ''));
        $comment = get_comment($commentId);

        if (! $comment instanceof WP_Comment || (string) $comment->comment_approved !== '1'
            || ! CommentReadAccess::forPost((int) $comment->comment_post_ID)) {
            wp_send_json_error(['message' => __('Комментарий не найден.', 'imperial-future')], 404);
        }

        if (! in_array($value, ['up', 'down'], true)) {
            wp_send_json_error(['message' => __('Такой оценки комментария нет.', 'imperial-future')], 422);
        }

        $counts = CommentVoteService::setUserVote($commentId, get_current_user_id(), $value);

        wp_send_json_success([
            'counts' => $counts,
            'score' => CommentVoteService::score($commentId),
            'voted' => CommentVoteService::userVote($commentId, get_current_user_id()),
        ]);
    }
}
