<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

if (! defined('ABSPATH')) {
    exit;
}

final class CommentReadAccess
{
    public static function forPost(int $postId): bool
    {
        $post = $postId > 0 ? get_post($postId) : null;

        if (! $post instanceof \WP_Post || post_password_required($post)) {
            return false;
        }

        return is_post_publicly_viewable($post) || current_user_can('read_post', $postId);
    }
}
