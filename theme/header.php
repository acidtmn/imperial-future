<?php
declare(strict_types=1);

use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Support\Icon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Перейти к содержимому', 'imperial-future'); ?></a>
<div class="reading-progress" data-reading-progress aria-hidden="true"></div>
<?php if (ThemeOptions::enabled('imperial_effect_preloader_enabled')) : ?>
    <div class="preloader" data-preloader aria-hidden="true">
        <div class="preloader__seal">И</div>
    </div>
<?php endif; ?>
<?php if (ThemeOptions::enabled('imperial_effect_noise_enabled')) : ?>
    <div class="film-noise" aria-hidden="true"></div>
<?php endif; ?>
<?php if (ThemeOptions::enabled('imperial_effect_dust_enabled')) : ?>
    <canvas class="dust-canvas" data-dust-canvas aria-hidden="true"></canvas>
<?php endif; ?>
<div class="site-shell">
    <header class="site-header">
        <div class="site-header__inner">
            <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <?php
                $darkLogo = ThemeOptions::mediaUrl('imperial_logo_dark') ?: get_template_directory_uri() . '/assets/images/brand/logo-dark.svg';
                $lightLogo = ThemeOptions::mediaUrl('imperial_logo_light') ?: get_template_directory_uri() . '/assets/images/brand/logo-light.svg';
                ?>
                <?php if ($darkLogo !== '' || $lightLogo !== '') : ?>
                    <span class="site-brand__logo">
                        <?php if ($darkLogo !== '') : ?>
                            <img class="site-brand__logo-image site-brand__logo-image--dark" src="<?php echo esc_url($darkLogo); ?>" alt="" width="48" height="48">
                        <?php endif; ?>
                        <?php if ($lightLogo !== '') : ?>
                            <img class="site-brand__logo-image site-brand__logo-image--light" src="<?php echo esc_url($lightLogo); ?>" alt="" width="48" height="48">
                        <?php endif; ?>
                    </span>
                <?php else : ?>
                    <span class="site-brand__mark">И</span>
                <?php endif; ?>
                <span class="site-brand__text">
                    <span class="site-brand__name"><?php bloginfo('name'); ?></span>
                    <span class="site-brand__tagline"><?php bloginfo('description'); ?></span>
                </span>
            </a>
            <?php get_template_part('parts/site/nav'); ?>
            <?php if (ThemeOptions::enabled('imperial_search_enabled')) : ?>
                <button class="header-icon-button header-icon-button--search" type="button" data-search-open aria-label="<?php esc_attr_e('Открыть поиск', 'imperial-future'); ?>">
                    <?php echo Icon::render('search'); ?>
                </button>
            <?php endif; ?>
            <?php if (ThemeOptions::enabled('imperial_auth_enabled')) : ?>
                <?php if (is_user_logged_in()) : ?>
                    <a class="header-icon-button header-icon-button--account" href="<?php echo esc_url(\ImperialFuture\Auth\AccountPageRegistrar::url()); ?>" aria-label="<?php esc_attr_e('Открыть кабинет', 'imperial-future'); ?>">
                        <?php echo Icon::render('account'); ?>
                    </a>
                <?php else : ?>
                    <button class="header-icon-button header-icon-button--account" type="button" data-auth-open aria-label="<?php esc_attr_e('Открыть вход в кабинет', 'imperial-future'); ?>">
                        <?php echo Icon::render('account'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="<?php esc_attr_e('Переключить тему', 'imperial-future'); ?>">
                <?php echo Icon::render('sun', 'theme-toggle__sun'); ?>
                <?php echo Icon::render('moon', 'theme-toggle__moon'); ?>
            </button>
            <button class="header-icon-button motion-toggle" type="button" data-motion-toggle aria-pressed="false" aria-label="<?php esc_attr_e('Приостановить анимацию', 'imperial-future'); ?>" title="<?php esc_attr_e('Приостановить анимацию', 'imperial-future'); ?>">
                <?php echo Icon::render('pause', 'motion-toggle__pause'); ?>
                <?php echo Icon::render('play', 'motion-toggle__play'); ?>
            </button>
        </div>
    </header>
    <main class="site-main" id="content" tabindex="-1">
