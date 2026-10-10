<?php
declare(strict_types=1);

namespace ImperialFuture\External;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class ExternalExitController
{
    public static function registerHooks(): void
    {
        add_filter('query_vars', [self::class, 'queryVars']);
        add_action('template_redirect', [self::class, 'render']);
    }

    public static function queryVars(array $vars): array
    {
        $vars[] = 'imperial_external_exit';
        $vars[] = 'to';

        return $vars;
    }

    public static function render(): void
    {
        if (get_query_var('imperial_external_exit') !== '1') {
            return;
        }

        $target = esc_url_raw(rawurldecode((string) get_query_var('to')));

        if ($target === '' || ! ExternalLinkService::shouldWrap($target)) {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        status_header(200);
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow', true);

        $delay = ThemeOptions::number('imperial_external_exit_delay', 0, 60);
        $host = ExternalLinkService::host($target);

        get_header();
        ?>
        <main class="external-exit-page" data-external-exit data-external-target="<?php echo esc_url($target); ?>" data-external-delay="<?php echo esc_attr((string) $delay); ?>">
            <section class="external-exit-card container">
                <p class="section-kicker"><?php esc_html_e('Внешний переход', 'imperial-future'); ?></p>
                <h1><?php echo esc_html(sprintf(__('Вы покидаете %s', 'imperial-future'), get_bloginfo('name'))); ?></h1>
                <p><?php echo esc_html(ThemeOptions::get('imperial_external_exit_warning_text')); ?></p>
                <strong class="external-exit-card__domain"><?php echo esc_html($host); ?></strong>
                <div class="external-exit-card__timer">
                    <span data-external-countdown><?php echo esc_html((string) $delay); ?></span>
                    <small><?php esc_html_e('сек. до перехода', 'imperial-future'); ?></small>
                </div>
                <a class="button button--primary is-disabled" href="<?php echo esc_url($target); ?>" rel="noopener noreferrer nofollow external" data-external-continue>
                    <?php esc_html_e('Продолжить переход', 'imperial-future'); ?>
                </a>
            </section>
        </main>
        <?php
        get_footer();
        exit;
    }
}
