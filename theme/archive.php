<?php
declare(strict_types=1);

use ImperialFuture\Ads\AdRenderer;

if (! defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('parts/layout/archive-header');
?>
<section class="section">
    <div class="container archive-layout">
        <div>
            <?php AdRenderer::render('before_content'); ?>
            <div class="archive-grid">
                <?php
                if (have_posts()) :
                    while (have_posts()) :
                        the_post();
                        get_template_part('parts/content/card');
                    endwhile;
                else :
                    ?>
                    <p><?php esc_html_e('В этой папке пока нет публикаций.', 'imperial-future'); ?></p>
                    <?php
                endif;
                ?>
            </div>
            <div class="pagination"><?php the_posts_pagination(); ?></div>
            <?php AdRenderer::render('after_content'); ?>
        </div>
        <aside class="article-sidebar sticky-panel">
            <?php AdRenderer::render('sidebar'); ?>
            <?php if (is_active_sidebar('imperial_sidebar')) : ?>
                <?php dynamic_sidebar('imperial_sidebar'); ?>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php
get_footer();
