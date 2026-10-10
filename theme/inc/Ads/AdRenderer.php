<?php
declare(strict_types=1);

namespace ImperialFuture\Ads;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class AdRenderer
{
    public static function render(string $position): void
    {
        $enabledKey = 'imperial_ad_' . $position . '_enabled';
        $codeKey = 'imperial_ad_' . $position . '_code';

        if (! ThemeOptions::enabled($enabledKey)) {
            return;
        }

        $code = trim(ThemeOptions::code($codeKey));

        if ($code === '') {
            return;
        }

        echo '<aside class="imperial-ad imperial-ad--' . esc_attr($position) . '" aria-label="' . esc_attr__('Рекламный блок', 'imperial-future') . '">';
        echo ThemeOptions::code($codeKey);
        echo '</aside>';
    }
}
