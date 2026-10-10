<?php
declare(strict_types=1);

namespace ImperialFuture\Rating;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class RatingService
{
    private const META_KEY = '_imperial_rating_counts';
    private const USER_META_PREFIX = 'imperial_rating_post_';

    public static function mode(): string
    {
        $mode = ThemeOptions::get('imperial_rating_mode');

        return in_array($mode, ['stars', 'likes', 'emoji'], true) ? $mode : 'stars';
    }

    public static function options(): array
    {
        $mode = self::mode();

        if ($mode === 'stars') {
            return [
                '1' => '★',
                '2' => '★',
                '3' => '★',
                '4' => '★',
                '5' => '★',
            ];
        }

        if ($mode === 'likes') {
            return [
                'up' => '👍',
                'down' => '👎',
            ];
        }

        $items = array_filter(array_map('trim', explode(',', ThemeOptions::get('imperial_rating_emoji_set'))));
        $items = $items !== [] ? $items : ['♥', '🔥', '👏', '🤔', '😂', '😮', '💡', '⚜️'];

        return array_combine($items, $items) ?: [];
    }

    public static function counts(int $postId): array
    {
        $stored = get_post_meta($postId, self::META_KEY, true);
        $counts = is_array($stored) ? $stored : [];
        $normalized = [];

        foreach (array_keys(self::options()) as $value) {
            $normalized[(string) $value] = max(0, (int) ($counts[(string) $value] ?? 0));
        }

        return $normalized;
    }

    public static function setUserVote(int $postId, int $userId, string $value): array
    {
        $counts = self::counts($postId);

        if (! array_key_exists($value, self::options())) {
            return $counts;
        }

        $previous = self::userVote($postId, $userId);

        if ($previous === $value) {
            return $counts;
        }

        if ($previous !== '' && isset($counts[$previous])) {
            $counts[$previous] = max(0, (int) $counts[$previous] - 1);
        }

        $counts[$value] = max(0, (int) ($counts[$value] ?? 0)) + 1;

        update_post_meta($postId, self::META_KEY, $counts);
        update_user_meta($userId, self::userMetaKey($postId), $value);

        return $counts;
    }

    public static function total(array $counts): int
    {
        return array_sum(array_map('intval', $counts));
    }

    public static function average(array $counts): float
    {
        $sum = 0;
        $votes = 0;

        foreach ($counts as $value => $count) {
            if (! is_numeric($value)) {
                continue;
            }

            $votes += (int) $count;
            $sum += (int) $value * (int) $count;
        }

        return $votes > 0 ? round($sum / $votes, 1) : 0.0;
    }

    public static function votedValue(int $postId): string
    {
        if (! is_user_logged_in()) {
            return '';
        }

        return self::userVote($postId, get_current_user_id());
    }

    public static function voters(int $postId, int $limit = 8): array
    {
        $users = get_users([
            'meta_key' => self::userMetaKey($postId),
            'number' => max(1, $limit),
            'orderby' => 'ID',
            'order' => 'DESC',
        ]);

        return array_values(array_filter(array_map(static function ($user) use ($postId): array {
            if (! $user instanceof \WP_User) {
                return [];
            }

            $reaction = self::userVote($postId, (int) $user->ID);

            if ($reaction === '') {
                return [];
            }

            return [
                'id' => (int) $user->ID,
                'name' => $user->display_name ?: $user->user_login,
                'url' => get_author_posts_url((int) $user->ID),
                'avatar' => get_avatar_url($user->ID, ['size' => 72]),
                'reaction' => self::label($reaction),
            ];
        }, is_array($users) ? $users : [])));
    }

    public static function label(string $value): string
    {
        $options = self::options();

        return (string) ($options[$value] ?? $value);
    }

    public static function userVote(int $postId, int $userId): string
    {
        if ($postId <= 0 || $userId <= 0) {
            return '';
        }

        $value = sanitize_text_field((string) get_user_meta($userId, self::userMetaKey($postId), true));

        return array_key_exists($value, self::options()) ? $value : '';
    }

    public static function userMetaKey(int $postId): string
    {
        return self::USER_META_PREFIX . max(0, $postId);
    }
}
