<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<header class="archive-header">
    <div class="container">
        <span class="section-kicker"><?php esc_html_e('Архивная лента', 'imperial-future'); ?></span>
        <h1 class="archive-header__title"><?php esc_html_e('Все публикации', 'imperial-future'); ?></h1>
        <p class="section-lead"><?php esc_html_e('Полная полоса заметок, хроник и писем редактора.', 'imperial-future'); ?></p>
    </div>
</header>
<section class="section">
    <div class="container archive-grid">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                get_template_part('parts/content/card');
            endwhile;
        else :
            ?>
            <p><?php esc_html_e('Пока архив молчит.', 'imperial-future'); ?></p>
            <?php
        endif;
        ?>
    </div>
    <div class="pagination container"><?php the_posts_pagination(); ?></div>
</section>
<?php
get_footer();
