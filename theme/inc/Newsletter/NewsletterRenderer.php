<?php
declare(strict_types=1);

namespace ImperialFuture\Newsletter;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class NewsletterRenderer
{
    public static function renderGlobal(): void
    {
        if (! ThemeOptions::enabled('imperial_newsletter_enabled')) {
            return;
        }

        get_template_part('parts/newsletter/form');
    }
}
