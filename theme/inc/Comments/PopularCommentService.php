<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use WP_Comment;

if (! defined('ABSPATH')) {
    exit;
}

final class PopularCommentService
{
    public static function forPost(int $postId): ?WP_Comment
    {
        $comments = get_comments([
            'post_id' => $postId,
            'status' => 'approve',
            'number' => 10,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
        ]);

        if (! is_array($comments) || $comments === []) {
            return null;
        }

        usort($comments, static function (WP_Comment $left, WP_Comment $right): int {
            $scoreCompare = CommentVoteService::score((int) $right->comment_ID) <=> CommentVoteService::score((int) $left->comment_ID);

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strtotime((string) $right->comment_date_gmt) <=> strtotime((string) $left->comment_date_gmt);
        });

        return $comments[0] instanceof WP_Comment ? $comments[0] : null;
    }

    public static function excerpt(WP_Comment $comment, int $words = 26): string
    {
        $text = wp_trim_words(wp_strip_all_tags(MarkdownService::render((string) $comment->comment_content)), $words);
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
