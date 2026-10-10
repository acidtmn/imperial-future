<?php
declare(strict_types=1);

use ImperialFuture\Ads\AdRenderer;

if (! defined('ABSPATH')) {
    exit;
}

get_header();
$authorId = (int) get_query_var('author');
?>
<header class="archive-header">
    <div class="container">
        <span class="section-kicker"><?php esc_html_e('Авторский кабинет', 'imperial-future'); ?></span>
        <h1 class="archive-header__title"><?php echo esc_html(get_the_author_meta('display_name', $authorId)); ?></h1>
        <p class="section-lead"><?php echo esc_html(get_the_author_meta('description', $authorId) ?: __('Публикации автора из цифрового архива.', 'imperial-future')); ?></p>
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
        endif;
        ?>
        <?php AdRenderer::render('after_content'); ?>
    </div>
    <div class="pagination container"><?php the_posts_pagination(); ?></div>
</section>
<?php
get_footer();
