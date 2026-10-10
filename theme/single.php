<?php
declare(strict_types=1);

use ImperialFuture\Ads\AdRenderer;
use ImperialFuture\Reading\ArticleContentSplitter;
use ImperialFuture\Reading\TableOfContents;
use ImperialFuture\SEO\Breadcrumbs;
use ImperialFuture\Template\TemplateTags;
use ImperialFuture\Support\Icon;
use ImperialFuture\Support\RussianPluralizer;

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <?php
    $postId = get_the_ID();
    $authorId = (int) get_the_author_meta('ID');
    $categories = get_the_category($postId);
    $primaryCategory = $categories[0] ?? null;
    $tags = array_slice(get_the_tags($postId) ?: [], 0, 5);
    $preparedContent = TableOfContents::build(apply_filters('the_content', get_the_content()));
    $contentParts = ArticleContentSplitter::afterFirstParagraph($preparedContent['content']);
    ?>
    <header class="single-hero single-hero--card">
        <div class="container">
            <?php Breadcrumbs::render(); ?>

            <article class="post-card post-card--single" data-reveal>
                <div class="post-card__body post-card__body--single">
                    <div class="post-card__byline">
                        <a class="post-card__avatar" href="<?php echo esc_url(get_author_posts_url($authorId)); ?>" aria-label="<?php echo esc_attr(get_the_author()); ?>">
                            <img src="<?php echo esc_url(get_avatar_url($authorId, ['size' => 72])); ?>" alt="" loading="lazy" width="36" height="36">
                        </a>
                        <div>
                            <a class="post-card__author" href="<?php echo esc_url(get_author_posts_url($authorId)); ?>"><?php echo esc_html(get_the_author()); ?></a>
                            <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(TemplateTags::postMeta($postId)); ?></time>
                        </div>
                    </div>

                    <h1 class="post-card__title post-card__title--single"><?php the_title(); ?></h1>

                    <div class="post-card__tags" aria-label="<?php esc_attr_e('Метки публикации', 'imperial-future'); ?>">
                        <?php if ($primaryCategory instanceof WP_Term) : ?>
                            <a href="<?php echo esc_url(get_category_link((int) $primaryCategory->term_id)); ?>"><?php echo esc_html($primaryCategory->name); ?></a>
                        <?php else : ?>
                            <span><?php echo esc_html(TemplateTags::primaryCategory($postId)); ?></span>
                        <?php endif; ?>
                        <?php foreach ($tags as $tag) : ?>
                            <a href="<?php echo esc_url(get_tag_link((int) $tag->term_id)); ?>">#<?php echo esc_html($tag->name); ?></a>
                        <?php endforeach; ?>
                    </div>

                    <p class="post-card__summary post-card__summary--single"><?php echo esc_html(get_the_excerpt()); ?></p>
                </div>

                <figure class="post-card__image single-hero__image" data-parallax="-6">
                    <?php echo TemplateTags::featuredImage($postId, 'imperial-hero', ['loading' => 'eager', 'fetchpriority' => 'high']); ?>
                </figure>
            </article>
        </div>
    </header>

    <section class="article-content">
        <div class="single-layout container">
            <article <?php post_class('entry-content'); ?> data-reveal>
                <?php AdRenderer::render('before_content'); ?>

                <?php echo $contentParts['intro']; // Контент уже обработан фильтрами WordPress, включая oEmbed. ?>

                <?php if (! empty($preparedContent['items'])) : ?>
                    <details class="toc toc--inline" data-reveal>
                        <summary>
                            <span class="toc__heading"><?php esc_html_e('В этой статье', 'imperial-future'); ?><small><?php echo esc_html(RussianPluralizer::format(count($preparedContent['items']), [__('пункт', 'imperial-future'), __('пункта', 'imperial-future'), __('пунктов', 'imperial-future')])); ?></small></span>
                            <?php echo Icon::render('chevron-down', 'toc__chevron'); ?>
                        </summary>
                        <ol class="toc__list">
                            <?php foreach ($preparedContent['items'] as $index => $item) : ?>
                                <li>
                                    <a class="toc__link toc__link--<?php echo esc_attr($item['level']); ?>" data-toc-link href="#<?php echo esc_attr($item['id']); ?>">
                                        <span class="toc__number" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                                        <span><?php echo esc_html($item['text']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                <?php endif; ?>

                <?php echo $contentParts['body']; ?>
                <?php wp_link_pages(['before' => '<nav class="page-links" aria-label="' . esc_attr__('Страницы публикации', 'imperial-future') . '">', 'after' => '</nav>']); ?>

                <?php get_template_part('parts/content/tags'); ?>
                <div class="article-engagement" data-reveal>
                    <?php get_template_part('parts/rating/panel'); ?>
                    <?php get_template_part('parts/share/actions'); ?>
                </div>
                <?php AdRenderer::render('after_content'); ?>
            </article>

            <aside class="article-sidebar sticky-panel">
                <?php AdRenderer::render('sidebar'); ?>
                <?php if (is_active_sidebar('imperial_sidebar')) : ?>
                    <?php dynamic_sidebar('imperial_sidebar'); ?>
                <?php endif; ?>
            </aside>
        </div>
    </section>

    <?php
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
    ?>

    <?php get_template_part('parts/content/related'); ?>
<?php endwhile; ?>
<?php
get_footer();
