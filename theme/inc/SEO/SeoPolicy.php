<?php
declare(strict_types=1);

namespace ImperialFuture\SEO;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class SeoPolicy
{
    public static function enabled(): bool
    {
        return ThemeOptions::enabled('imperial_seo_enabled')
            && ! defined('WPSEO_VERSION')
            && ! defined('RANK_MATH_VERSION')
            && ! defined('AIOSEO_VERSION')
            && ! defined('SEOPRESS_VERSION');
    }

    public static function privatePage(): bool
    {
        return is_404() || is_page_template('page-account.php') || is_page('account')
            || (is_singular() && post_password_required());
    }
}
