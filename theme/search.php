<?php
declare(strict_types=1);

use ImperialFuture\Ads\AdRenderer;

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<header class="archive-header">
    <div class="container">
        <span class="section-kicker"><?php esc_html_e('Поисковый телеграф', 'imperial-future'); ?></span>
        <h1 class="archive-header__title"><?php printf(esc_html__('Поиск: %s', 'imperial-future'), esc_html(get_search_query())); ?></h1>
        <?php get_search_form(); ?>
    </div>
</header>
<section class="section">
    <div class="container archive-grid">
        <?php AdRenderer::render('before_content'); ?>
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                get_template_part('parts/content/card');
            endwhile;
        else :
            ?>
            <p><?php esc_html_e('Архив не нашёл подходящих записей. Попробуйте другой запрос.', 'imperial-future'); ?></p>
            <?php
        endif;
        ?>
        <?php AdRenderer::render('after_content'); ?>
    </div>
    <div class="pagination container"><?php the_posts_pagination(); ?></div>
</section>
<?php
get_footer();
