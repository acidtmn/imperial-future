<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

if (! defined('ABSPATH')) {
    exit;
}

final class CommentVoteService
{
    private const META_KEY = '_imperial_comment_votes';
    private const USER_META_PREFIX = 'imperial_comment_vote_';

    public static function counts(int $commentId): array
    {
        $stored = get_comment_meta($commentId, self::META_KEY, true);
        $counts = is_array($stored) ? $stored : [];

        return [
            'up' => max(0, (int) ($counts['up'] ?? 0)),
            'down' => max(0, (int) ($counts['down'] ?? 0)),
        ];
    }

    public static function score(int $commentId): int
    {
        $counts = self::counts($commentId);

        return (int) $counts['up'] - (int) $counts['down'];
    }

    public static function userVote(int $commentId, int $userId): string
    {
        if ($commentId <= 0 || $userId <= 0) {
            return '';
        }

        $value = sanitize_key((string) get_user_meta($userId, self::userMetaKey($commentId), true));

        return in_array($value, ['up', 'down'], true) ? $value : '';
    }

    public static function setUserVote(int $commentId, int $userId, string $value): array
    {
        if (! in_array($value, ['up', 'down'], true)) {
            return self::counts($commentId);
        }

        $counts = self::counts($commentId);
        $previous = self::userVote($commentId, $userId);

        if ($previous === $value) {
            $counts[$value] = max(0, (int) $counts[$value] - 1);
            delete_user_meta($userId, self::userMetaKey($commentId));
            update_comment_meta($commentId, self::META_KEY, $counts);

            return $counts;
        }

        if ($previous !== '') {
            $counts[$previous] = max(0, (int) $counts[$previous] - 1);
        }

        $counts[$value] = max(0, (int) $counts[$value]) + 1;

        update_comment_meta($commentId, self::META_KEY, $counts);
        update_user_meta($userId, self::userMetaKey($commentId), $value);

        return $counts;
    }

    private static function userMetaKey(int $commentId): string
    {
        return self::USER_META_PREFIX . max(0, $commentId);
    }
}
