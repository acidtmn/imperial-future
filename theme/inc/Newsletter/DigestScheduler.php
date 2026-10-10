<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class DigestScheduler
{
    public const WEEKLY_HOOK = 'imperial_newsletter_weekly';
    public const BATCH_HOOK = 'imperial_newsletter_batch';

    public static function registerHooks(): void
    {
        add_action('init', [self::class, 'sync']);
        add_action('update_option_theme_mods_' . get_stylesheet(), [self::class, 'sync']);
        add_action('switch_theme', [self::class, 'clear']);
        add_action('after_switch_theme', [self::class, 'clear']);
        add_action(self::WEEKLY_HOOK, [DigestService::class, 'start']);
        add_action(self::BATCH_HOOK, [DigestService::class, 'sendBatch']);
    }

    public static function sync(): void
    {
        if (! ThemeOptions::enabled('imperial_newsletter_enabled')) {
            self::clear();
            return;
        }

        SubscriptionService::migrateUserPreferences();

        if (! wp_next_scheduled(self::WEEKLY_HOOK)) {
            $next = new \DateTimeImmutable('next monday 09:00', wp_timezone());
            self::check(wp_schedule_event($next->getTimestamp(), 'weekly', self::WEEKLY_HOOK, [], true));
        }

        $issue = get_option(DigestService::ISSUE_OPTION, []);

        if (is_array($issue) && ($issue['status'] ?? '') === 'sending') {
            self::scheduleBatch();
        }
    }

    public static function scheduleBatch(int $delay = MINUTE_IN_SECONDS): void
    {
        if (ThemeOptions::enabled('imperial_newsletter_enabled') && ! wp_next_scheduled(self::BATCH_HOOK)) {
            self::check(wp_schedule_single_event(time() + $delay, self::BATCH_HOOK, [], true));
        }
    }

    public static function clear(): void
    {
        self::check(wp_clear_scheduled_hook(self::WEEKLY_HOOK, [], true));
        self::check(wp_clear_scheduled_hook(self::BATCH_HOOK, [], true));
    }

    private static function check(mixed $result): void
    {
        if ($result === false || is_wp_error($result)) {
            error_log('Imperial newsletter: cron update failed.');
        }
    }
}
