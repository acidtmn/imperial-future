<?php
declare(strict_types=1);

use ImperialFuture\Ads\AdRenderer;
use ImperialFuture\Newsletter\NewsletterRenderer;

if (! defined('ABSPATH')) {
    exit;
}
?>
    </main>
    <?php NewsletterRenderer::renderGlobal(); ?>
    <?php AdRenderer::render('footer'); ?>
    <footer class="site-footer">
        <div class="site-footer__grid container">
            <div class="site-footer__brand">
                <span class="section-kicker"><?php esc_html_e('Архив будущего', 'imperial-future'); ?></span>
                <h2><?php bloginfo('name'); ?></h2>
                <p><?php bloginfo('description'); ?></p>
                <?php if (is_active_sidebar('imperial_footer')) : ?>
                    <?php dynamic_sidebar('imperial_footer'); ?>
                <?php endif; ?>
            </div>
            <nav class="site-footer__nav" aria-label="<?php esc_attr_e('Нижняя навигация', 'imperial-future'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container' => false,
                    'fallback_cb' => false,
                    'items_wrap' => '<ul class="site-footer__links">%3$s</ul>',
                    'depth' => 1,
                ]);
                ?>
            </nav>
        </div>
    </footer>
</div>
<?php get_template_part('parts/auth/modal'); ?>
<?php get_template_part('parts/search/modal'); ?>
<?php get_template_part('parts/site/notices'); ?>
<?php wp_footer(); ?>
</body>
</html>
