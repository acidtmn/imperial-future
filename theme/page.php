<?php
declare(strict_types=1);

use ImperialFuture\SEO\Breadcrumbs;
use ImperialFuture\Ads\AdRenderer;

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <header class="archive-header">
        <div class="container">
            <?php Breadcrumbs::render(); ?>
            <span class="section-kicker"><?php esc_html_e('Страница архива', 'imperial-future'); ?></span>
            <h1 class="archive-header__title"><?php the_title(); ?></h1>
        </div>
    </header>
    <section class="article-content">
        <article class="entry-content content-width" data-reveal>
            <?php AdRenderer::render('before_content'); ?>
            <?php the_content(); ?>
            <?php wp_link_pages(['before' => '<nav class="page-links" aria-label="' . esc_attr__('Страницы публикации', 'imperial-future') . '">', 'after' => '</nav>']); ?>
            <?php AdRenderer::render('after_content'); ?>
        </article>
    </section>
<?php endwhile; ?>
<?php
get_footer();
