<?php
declare(strict_types=1);

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

?>
<section class="newsletter-band">
    <div class="newsletter-band__inner container">
        <div>
            <span class="section-kicker"><?php esc_html_e('Подписка', 'imperial-future'); ?></span>
            <h2><?php echo esc_html(ThemeOptions::get('imperial_newsletter_title')); ?></h2>
            <p><?php echo esc_html(ThemeOptions::get('imperial_newsletter_text')); ?></p>
        </div>
        <form class="newsletter-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="imperial_newsletter_subscribe">
            <?php wp_nonce_field('imperial_newsletter'); ?>
            <label>
                <span class="screen-reader-text"><?php esc_html_e('Email для дайджеста', 'imperial-future'); ?></span>
                <input type="email" name="newsletter_email" autocomplete="email" placeholder="<?php esc_attr_e('Ваш email', 'imperial-future'); ?>" required>
            </label>
            <button type="submit"><?php echo esc_html(ThemeOptions::get('imperial_newsletter_button_label')); ?></button>
        </form>
    </div>
</section>
