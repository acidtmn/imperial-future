<?php
declare(strict_types=1);

use ImperialFuture\Auth\SocialAuthHandler;
use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Share\SocialIcon;

if (! defined('ABSPATH') || ! ThemeOptions::enabled('imperial_auth_enabled')) {
    return;
}

$hasSocialAuth = SocialAuthHandler::visible('vk') || SocialAuthHandler::visible('yandex');
?>
<div class="imperial-modal" data-auth-modal hidden>
    <div class="imperial-modal__backdrop" data-auth-close></div>
    <div class="imperial-modal__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Вход в кабинет', 'imperial-future'); ?>">
        <button class="imperial-modal__close" type="button" data-auth-close aria-label="<?php esc_attr_e('Закрыть', 'imperial-future'); ?>">×</button>
        <span class="section-kicker"><?php esc_html_e('Личный архив', 'imperial-future'); ?></span>
        <h2><?php esc_html_e('Кабинет читателя', 'imperial-future'); ?></h2>

        <?php if ($hasSocialAuth) : ?>
            <div class="auth-social auth-social--top">
                <?php if (SocialAuthHandler::visible('vk')) : ?>
                    <a class="social-link" href="<?php echo esc_url(SocialAuthHandler::buttonUrl('vk')); ?>">
                        <span class="social-link__icon" aria-hidden="true"><img src="<?php echo esc_url(SocialIcon::url('vk')); ?>" alt="" width="22" height="22"></span>
                        <span class="social-link__label"><?php esc_html_e('Войти через VK', 'imperial-future'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if (SocialAuthHandler::visible('yandex')) : ?>
                    <a class="social-link" href="<?php echo esc_url(SocialAuthHandler::buttonUrl('yandex')); ?>">
                        <span class="social-link__icon" aria-hidden="true">Я</span>
                        <span class="social-link__label"><?php esc_html_e('Войти через Яндекс', 'imperial-future'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="auth-tabs">
            <button type="button" data-auth-tab="login"><?php esc_html_e('Вход', 'imperial-future'); ?></button>
            <?php if (get_option('users_can_register')) : ?>
                <button type="button" data-auth-tab="register"><?php esc_html_e('Регистрация', 'imperial-future'); ?></button>
            <?php endif; ?>
            <button type="button" data-auth-tab="lost"><?php esc_html_e('Пароль', 'imperial-future'); ?></button>
        </div>

        <form class="auth-form is-active" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-auth-panel="login">
            <input type="hidden" name="action" value="imperial_login">
            <?php wp_nonce_field('imperial_auth'); ?>
            <label><?php esc_html_e('Логин или email', 'imperial-future'); ?><input type="text" name="login" autocomplete="username" required></label>
            <label><?php esc_html_e('Пароль', 'imperial-future'); ?><input type="password" name="password" autocomplete="current-password" required></label>
            <label class="imperial-check"><input type="checkbox" name="remember" value="1"><?php esc_html_e('Запомнить меня', 'imperial-future'); ?></label>
            <button type="submit"><?php esc_html_e('Войти', 'imperial-future'); ?></button>
        </form>

        <?php if (get_option('users_can_register')) : ?>
        <form class="auth-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-auth-panel="register">
            <input type="hidden" name="action" value="imperial_register">
            <?php wp_nonce_field('imperial_auth'); ?>
            <label><?php esc_html_e('Логин', 'imperial-future'); ?><input type="text" name="name" autocomplete="username" required></label>
            <label><?php esc_html_e('Email', 'imperial-future'); ?><input type="email" name="email" autocomplete="email" required></label>
            <label><?php esc_html_e('Пароль от 12 символов', 'imperial-future'); ?><input type="password" name="password" minlength="12" autocomplete="new-password" data-password-input required></label>
            <div class="imperial-password-meter" data-password-meter aria-live="polite">
                <span></span>
                <strong><?php esc_html_e('Сила пароля: не указана', 'imperial-future'); ?></strong>
            </div>
            <button type="submit"><?php esc_html_e('Зарегистрироваться', 'imperial-future'); ?></button>
        </form>
        <?php endif; ?>

        <form class="auth-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-auth-panel="lost">
            <input type="hidden" name="action" value="imperial_lost_password">
            <?php wp_nonce_field('imperial_auth'); ?>
            <label><?php esc_html_e('Логин или email', 'imperial-future'); ?><input type="text" name="user_login" autocomplete="username" required></label>
            <button type="submit"><?php esc_html_e('Восстановить пароль', 'imperial-future'); ?></button>
        </form>
    </div>
</div>
