<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class DigestService
{
    public const ISSUE_OPTION = 'imperial_newsletter_digest_issue';
    public const PREVIOUS_OPTION = 'imperial_newsletter_digest_previous';
    public const BATCH_SIZE = 10;
    public const MAX_ATTEMPTS = 3;

    public static function start(): void
    {
        if (! ThemeOptions::enabled('imperial_newsletter_enabled')) {
            DigestScheduler::clear();
            return;
        }

        $lock = NewsletterLock::acquire('digest');

        if ($lock === false) {
            return;
        }

        try {
            $now = time();
            $id = wp_date('o-W', $now);
            $previous = get_option(self::ISSUE_OPTION, []);

            // Один выпуск на календарную неделю, в том числе после повторного cron.
            if (is_array($previous) && ($previous['id'] ?? '') === $id) {
                if (($previous['status'] ?? '') === 'sending') {
                    DigestScheduler::scheduleBatch();
                }
                return;
            }

            if (! empty($previous)) {
                if (($previous['status'] ?? '') === 'sending') {
                    $previous['status'] = 'expired';
                    error_log('Imperial newsletter: previous issue expired before completion.');
                }
                update_option(self::PREVIOUS_OPTION, $previous, false);
            }

            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'has_password' => false,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'orderby' => 'date',
                'order' => 'DESC',
                'suppress_filters' => false,
                'date_query' => [[
                    'column' => 'post_date_gmt',
                    'after' => gmdate('Y-m-d H:i:s', $now - WEEK_IN_SECONDS),
                    'before' => gmdate('Y-m-d H:i:s', $now),
                    'inclusive' => true,
                ]],
            ]);
            $recipients = [];

            foreach (SubscriptionService::confirmedSubscribers() as $email => $record) {
                $recipients[$email] = ['key' => $record['subscription_key'], 'status' => 'pending', 'attempts' => 0, 'next_at' => 0];
            }

            $issue = [
                'id' => $id,
                'created_at' => $now,
                'status' => $posts !== [] && $recipients !== [] ? 'sending' : 'empty',
                'posts' => array_map('intval', $posts),
                'recipients' => $recipients,
            ];

            if (self::save($issue) && $issue['status'] === 'sending') {
                DigestScheduler::scheduleBatch();
            }
        } finally {
            NewsletterLock::release('digest', $lock);
        }
    }

    public static function sendBatch(): void
    {
        if (! ThemeOptions::enabled('imperial_newsletter_enabled')) {
            DigestScheduler::clear();
            return;
        }

        $lock = NewsletterLock::acquire('digest');

        if ($lock === false) {
            DigestScheduler::scheduleBatch(5 * MINUTE_IN_SECONDS);
            return;
        }

        try {
            $issue = get_option(self::ISSUE_OPTION, []);

            if (! is_array($issue) || ($issue['status'] ?? '') !== 'sending') {
                return;
            }

            // Следующий запуск остаётся и при аварийном завершении текущей партии.
            DigestScheduler::scheduleBatch(5 * MINUTE_IN_SECONDS);
            $processed = 0;

            foreach ($issue['recipients'] as $email => $recipient) {
                if ($processed >= self::BATCH_SIZE || ! ThemeOptions::enabled('imperial_newsletter_enabled')) {
                    break;
                }

                if ($recipient['status'] === 'sending') {
                    // После обрыва нельзя узнать, принял ли транспорт письмо: не дублируем.
                    $recipient['status'] = 'uncertain';
                    $recipient['error'] = 'interrupted_delivery';
                    $issue['recipients'][$email] = $recipient;
                    error_log('Imperial newsletter: interrupted delivery in issue ' . $issue['id']);
                    ++$processed;
                    continue;
                }

                if (! in_array($recipient['status'], ['pending', 'retry'], true) || $recipient['next_at'] > time()) {
                    continue;
                }

                ++$processed;
                $current = SubscriptionService::confirmedSubscribers()[$email] ?? [];

                if (($current['subscription_key'] ?? '') !== $recipient['key']) {
                    $recipient['status'] = 'skipped';
                    $issue['recipients'][$email] = $recipient;
                    continue;
                }

                $message = self::message($issue);

                if ($message === '') {
                    $recipient['status'] = 'skipped';
                    $issue['recipients'][$email] = $recipient;
                    continue;
                }

                $recipient['status'] = 'sending';
                ++$recipient['attempts'];
                $issue['recipients'][$email] = $recipient;

                if (! self::save($issue)) {
                    return;
                }

                $message .= "\n\n" . __('Отписаться от дайджеста:', 'imperial-future') . "\n" . SubscriptionService::unsubscribeUrl($email);
                $sent = MailDelivery::send($email, sprintf(__('Еженедельный дайджест: %s', 'imperial-future'), wp_date('d.m.Y', $issue['created_at'])), $message);
                $recipient['status'] = $sent ? 'sent' : ($recipient['attempts'] < self::MAX_ATTEMPTS ? 'retry' : 'failed');
                $recipient['next_at'] = $sent ? 0 : time() + 5 * MINUTE_IN_SECONDS;
                $recipient['error'] = $sent ? '' : 'wp_mail_failed';
                $issue['recipients'][$email] = $recipient;

                if (! self::save($issue)) {
                    return;
                }
            }

            $statuses = array_column($issue['recipients'], 'status');

            if (! array_intersect($statuses, ['pending', 'retry', 'sending'])) {
                $issue['status'] = array_intersect($statuses, ['failed', 'uncertain']) ? 'completed_with_errors' : 'completed';
                wp_clear_scheduled_hook(DigestScheduler::BATCH_HOOK);
            }

            self::save($issue);
        } finally {
            NewsletterLock::release('digest', $lock);
        }
    }

    private static function message(array $issue): string
    {
        $items = [];
        $from = (int) $issue['created_at'] - WEEK_IN_SECONDS;

        // Повторно проверяем видимость перед отправкой, а не только при создании выпуска.
        foreach ($issue['posts'] as $postId) {
            $post = get_post($postId);

            if (! $post instanceof \WP_Post || $post->post_type !== 'post' || $post->post_status !== 'publish' || $post->post_password !== '' || ! is_post_publicly_viewable($post)) {
                continue;
            }

            $published = strtotime($post->post_date_gmt . ' UTC');

            if ($published === false || $published < $from || $published > $issue['created_at']) {
                continue;
            }

            $items[] = wp_strip_all_tags(get_the_title($post)) . "\n" . get_permalink($post);
        }

        return implode("\n\n", $items);
    }

    private static function save(array $issue): bool
    {
        update_option(self::ISSUE_OPTION, $issue, false);

        if (get_option(self::ISSUE_OPTION) !== $issue) {
            error_log('Imperial newsletter: could not persist issue progress.');
            return false;
        }

        return true;
    }
}
