<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$categoryIds = wp_get_post_categories(get_the_ID());
$related = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post__not_in' => [get_the_ID()],
    'category__in' => $categoryIds,
    'ignore_sticky_posts' => true,
]);

if (! $related->have_posts()) {
    return;
}
?>
<section class="related-posts">
    <div class="container">
        <span class="section-kicker"><?php esc_html_e('Ещё почитать', 'imperial-future'); ?></span>
        <h2 class="section-title"><?php esc_html_e('Материалы из соседних папок архива', 'imperial-future'); ?></h2>
        <div class="post-grid">
            <?php
            while ($related->have_posts()) :
                $related->the_post();
                get_template_part('parts/content/card');
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</section>
