<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

final class CommentNotificationService
{
    public static function registerHooks(): void
    {
        add_action('comment_post', [self::class, 'notify'], 20, 3);
        add_action('transition_comment_status', [self::class, 'transition'], 20, 3);
    }

    public static function transition(string $newStatus, string $oldStatus, \WP_Comment $comment): void
    {
        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            self::notify((int) $comment->comment_ID, 1, []);
        }
    }

    public static function notify(int $commentId, int|string $approved, array $commentData): void
    {
        if ((string) $approved !== '1' || get_transient('imperial_demo_import_running') || (defined('WP_IMPORTING') && WP_IMPORTING) || wp_installing()) {
            return;
        }

        $comment = get_comment($commentId);

        if (! $comment instanceof \WP_Comment || (string) $comment->comment_approved !== '1' || ! in_array($comment->comment_type, ['', 'comment'], true) || get_comment_meta($commentId, '_imperial_seed_comment', true)) {
            return;
        }

        $post = get_post((int) $comment->comment_post_ID);

        if (! $post instanceof \WP_Post || ! is_post_publicly_viewable($post) || $post->post_password !== '') {
            return;
        }

        $lock = NewsletterLock::acquire('comment:' . $commentId);

        if ($lock === false) {
            return;
        }

        try {
            $deliveries = get_comment_meta($commentId, '_imperial_notification_deliveries', true);
            $deliveries = is_array($deliveries) ? $deliveries : [];

            foreach (self::recipients($comment) as $email) {
                $key = hash('sha256', $email);
                $delivery = $deliveries[$key] ?? ['status' => 'pending', 'attempts' => 0];

                if ($delivery['status'] === 'sending') {
                    $delivery['status'] = 'uncertain';
                    $delivery['error'] = 'interrupted_delivery';
                    $deliveries[$key] = $delivery;
                    update_comment_meta($commentId, '_imperial_notification_deliveries', $deliveries);
                    error_log('Imperial newsletter: interrupted comment notification ' . $commentId);
                }

                if (in_array($delivery['status'], ['sent', 'sending', 'uncertain'], true) || $delivery['attempts'] >= 3) {
                    continue;
                }

                // Сохраняем попытку до передачи транспорту, чтобы не дублировать после обрыва.
                $delivery['status'] = 'sending';
                ++$delivery['attempts'];
                $deliveries[$key] = $delivery;
                update_comment_meta($commentId, '_imperial_notification_deliveries', $deliveries);

                if (get_comment_meta($commentId, '_imperial_notification_deliveries', true) !== $deliveries) {
                    error_log('Imperial newsletter: could not persist comment notification.');
                    return;
                }

                $sent = MailDelivery::send(
                    $email,
                    sprintf(__('Новый отклик в архиве: %s', 'imperial-future'), get_the_title($post)),
                    self::message($comment, $post)
                );
                $delivery['status'] = $sent ? 'sent' : 'failed';
                $delivery['error'] = $sent ? '' : 'wp_mail_failed';
                $deliveries[$key] = $delivery;
                update_comment_meta($commentId, '_imperial_notification_deliveries', $deliveries);

                if (get_comment_meta($commentId, '_imperial_notification_deliveries', true) !== $deliveries) {
                    error_log('Imperial newsletter: could not persist comment notification result.');
                    return;
                }
            }
        } finally {
            NewsletterLock::release('comment:' . $commentId, $lock);
        }
    }

    private static function recipients(\WP_Comment $comment): array
    {
        $emails = [];
        $authorEmail = strtolower(sanitize_email((string) $comment->comment_author_email));

        foreach (get_users(['meta_key' => SubscriptionService::COMMENTS_META, 'meta_value' => '1']) as $user) {
            if (! $user instanceof \WP_User || ! SubscriptionService::userCommentsEnabled((int) $user->ID)) {
                continue;
            }

            $email = strtolower(sanitize_email((string) $user->user_email));

            if ($email !== '' && $email !== $authorEmail) {
                $emails[$email] = $email;
            }
        }

        if ((int) $comment->comment_parent > 0) {
            $parent = get_comment((int) $comment->comment_parent);

            if ($parent instanceof \WP_Comment && (int) $parent->user_id > 0 && SubscriptionService::userRepliesEnabled((int) $parent->user_id)) {
                $parentUser = get_userdata((int) $parent->user_id);
                $email = $parentUser instanceof \WP_User ? strtolower(sanitize_email((string) $parentUser->user_email)) : '';

                if ($email !== '' && $email !== $authorEmail) {
                    $emails[$email] = $email;
                }
            }
        }

        return array_values($emails);
    }

    private static function message(\WP_Comment $comment, \WP_Post $post): string
    {
        $excerpt = wp_trim_words(wp_strip_all_tags((string) $comment->comment_content), 34);

        return sprintf(
            "%s\n\n%s\n\n%s",
            get_the_title($post),
            $excerpt,
            get_comment_link($comment)
        );
    }
}
