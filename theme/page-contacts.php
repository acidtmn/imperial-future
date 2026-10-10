<?php
declare(strict_types=1);

use ImperialFuture\Ads\AdRenderer;
use ImperialFuture\Contact\CaptchaService;
use ImperialFuture\SEO\Breadcrumbs;
use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Share\SocialLinks;

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$status = sanitize_key((string) ($_GET['contact_status'] ?? ''));
$statusMessages = [
    'sent' => ThemeOptions::get('imperial_contact_success_message'),
    'captcha' => __('Печать не совпала с латунной меткой. Перетащите её на отмеченное место и отправьте письмо ещё раз.', 'imperial-future'),
    'mail_error' => __('WordPress принял форму, но почтовый транспорт сервера не отправил письмо.', 'imperial-future'),
    'error' => ThemeOptions::get('imperial_contact_error_message'),
];
$captcha = ThemeOptions::enabled('imperial_contact_captcha_enabled') ? CaptchaService::create() : [];
?>
<?php while (have_posts()) : the_post(); ?>
    <header class="archive-header">
        <div class="container">
            <?php Breadcrumbs::render(); ?>
            <span class="section-kicker"><?php echo esc_html(ThemeOptions::get('imperial_contact_kicker')); ?></span>
            <h1 class="archive-header__title"><?php the_title(); ?></h1>
            <p class="section-lead"><?php echo esc_html(ThemeOptions::get('imperial_contact_lead')); ?></p>
        </div>
    </header>
    <section class="contact-grid container">
        <?php AdRenderer::render('before_content'); ?>
        <form class="contact-panel" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-reveal>
            <h2><?php echo esc_html(ThemeOptions::get('imperial_contact_form_title')); ?></h2>

            <?php if ($status !== '' && isset($statusMessages[$status])) : ?>
                <div class="contact-alert contact-alert--<?php echo esc_attr($status); ?>">
                    <?php echo esc_html($statusMessages[$status]); ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="action" value="imperial_contact_submit">
            <input type="hidden" name="source_url" value="<?php echo esc_url(get_permalink()); ?>">
            <?php wp_nonce_field('imperial_contact_form'); ?>

            <label>
                <?php esc_html_e('Ваше имя', 'imperial-future'); ?>
                <input type="text" name="name" autocomplete="name" required>
            </label>
            <label>
                <?php esc_html_e('Email для ответа', 'imperial-future'); ?>
                <input type="email" name="email" autocomplete="email" required>
            </label>
            <label>
                <?php esc_html_e('Тема письма', 'imperial-future'); ?>
                <input type="text" name="subject" autocomplete="off">
            </label>
            <label>
                <?php esc_html_e('Сообщение', 'imperial-future'); ?>
                <textarea name="message" required></textarea>
            </label>
            <?php if ($captcha !== []) : ?>
                <div class="contact-panel__captcha">
                    <strong id="captcha-label"><?php esc_html_e('Перетащи печать в круг', 'imperial-future'); ?></strong>
                    <input type="hidden" name="captcha_token" value="<?php echo esc_attr($captcha['token']); ?>">
                    <input type="hidden" name="captcha_drag" value="0" data-captcha-value>
                    <div class="imperial-drag-captcha" data-drag-captcha>
                        <div class="imperial-drag-captcha__track">
                            <img src="<?php echo esc_url($captcha['url']); ?>" alt="<?php esc_attr_e('Круглая метка для печати', 'imperial-future'); ?>" width="360" height="112" loading="lazy" draggable="false">
                            <button class="imperial-drag-captcha__seal" type="button" role="slider" aria-orientation="horizontal" aria-valuemin="0" aria-valuemax="100" aria-valuenow="12" aria-labelledby="captcha-label" aria-describedby="captcha-hint" data-drag-handle>И</button>
                        </div>
                        <small id="captcha-hint"><?php esc_html_e('Совмести центры печати и круга. Можно двигать пальцем или стрелками на клавиатуре.', 'imperial-future'); ?></small>
                        <small data-captcha-status role="status"><?php esc_html_e('Печать ещё не перемещена.', 'imperial-future'); ?></small>
                    </div>
                </div>
            <?php endif; ?>
            <label class="contact-panel__trap" aria-hidden="true">
                <?php esc_html_e('Не заполняйте это поле', 'imperial-future'); ?>
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </label>
            <button type="submit"><?php echo esc_html(ThemeOptions::get('imperial_contact_button_label')); ?></button>
        </form>
        <aside class="contact-note" data-reveal>
            <h2><?php echo esc_html(ThemeOptions::get('imperial_contact_note_title')); ?></h2>
            <?php the_content(); ?>
            <?php if (SocialLinks::contact() !== []) : ?>
                <div class="social-links">
                    <?php foreach (SocialLinks::contact() as $socialLink) : ?>
                        <?php get_template_part('parts/social/link', null, ['link' => $socialLink]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>
        <?php AdRenderer::render('after_content'); ?>
    </section>
<?php endwhile; ?>
<?php
get_footer();
