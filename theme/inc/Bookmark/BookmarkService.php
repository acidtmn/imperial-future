<?php
declare(strict_types=1);

namespace ImperialFuture\Bookmark;

if (! defined('ABSPATH')) {
    exit;
}

final class BookmarkService
{
    private const META_KEY = 'imperial_bookmarked_posts';

    public static function ids(int $userId): array
    {
        $stored = get_user_meta($userId, self::META_KEY, true);
        $ids = is_array($stored) ? array_map('intval', $stored) : [];

        return array_values(array_filter(array_unique($ids), static fn (int $postId): bool => $postId > 0));
    }

    public static function isBookmarked(int $userId, int $postId): bool
    {
        return in_array($postId, self::ids($userId), true);
    }

    public static function toggle(int $userId, int $postId): bool
    {
        $ids = self::ids($userId);

        if (in_array($postId, $ids, true)) {
            $ids = array_values(array_diff($ids, [$postId]));
            update_user_meta($userId, self::META_KEY, $ids);

            return false;
        }

        array_unshift($ids, $postId);
        update_user_meta($userId, self::META_KEY, array_slice(array_values(array_unique($ids)), 0, 120));

        return true;
    }

    public static function posts(int $userId, int $limit = 12): array
    {
        $ids = array_slice(self::ids($userId), 0, max(1, $limit));

        if ($ids === []) {
            return [];
        }

        $posts = get_posts([
            'post__in' => $ids,
            'orderby' => 'post__in',
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => count($ids),
        ]);

        return is_array($posts) ? $posts : [];
    }
}
