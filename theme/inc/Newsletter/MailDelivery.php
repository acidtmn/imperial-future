<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

final class MailDelivery
{
    public static function send(string $email, string $subject, string $message): bool
    {
        try {
            $sent = wp_mail($email, $subject, $message);
        } catch (\Throwable $exception) {
            $sent = false;
        }

        if (! $sent) {
            error_log('Imperial newsletter: wp_mail failed; recipient ' . hash('sha256', $email));
        }

        return (bool) $sent;
    }
}
