<?php
declare(strict_types=1);

use ImperialFuture\Bookmark\BookmarkService;
use ImperialFuture\Comments\PopularCommentService;
use ImperialFuture\Rating\RatingService;
use ImperialFuture\Support\Icon;
use ImperialFuture\Support\RussianPluralizer;
use ImperialFuture\Template\TemplateTags;

if (! defined('ABSPATH')) {
    exit;
}

$postId = get_the_ID();
$authorId = (int) get_the_author_meta('ID');
$categories = get_the_category($postId);
$primaryCategory = $categories[0] ?? null;
$tags = array_slice(get_the_tags($postId) ?: [], 0, 3);
$ratingCounts = RatingService::counts($postId);
$ratingTotal = RatingService::total($ratingCounts);
$visibleReactions = array_filter($ratingCounts, static fn (int $count): bool => $count > 0);
arsort($visibleReactions);
$visibleReactions = array_slice($visibleReactions, 0, 5, true);
$commentsCount = (int) get_comments_number($postId);
$popularComment = PopularCommentService::forPost($postId);
$isLoggedIn = is_user_logged_in();
$isBookmarked = $isLoggedIn && BookmarkService::isBookmarked(get_current_user_id(), $postId);
?>
<article <?php post_class('post-card post-card--dtf'); ?> data-reveal>
    <div class="post-card__body">
        <header class="post-card__topline">
            <div class="post-card__byline">
                <a class="post-card__avatar" href="<?php echo esc_url(get_author_posts_url($authorId)); ?>" aria-label="<?php echo esc_attr(get_the_author()); ?>">
                    <img src="<?php echo esc_url(get_avatar_url($authorId, ['size' => 72])); ?>" alt="" loading="lazy" width="36" height="36">
                </a>
                <div>
                    <a class="post-card__author" href="<?php echo esc_url(get_author_posts_url($authorId)); ?>"><?php echo esc_html(get_the_author()); ?></a>
                    <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(TemplateTags::postMeta($postId)); ?></time>
                </div>
            </div>

            <a class="post-card__more" href="<?php the_permalink(); ?>" aria-label="<?php esc_attr_e('Открыть публикацию', 'imperial-future'); ?>">
                <?php echo Icon::render('more'); ?>
            </a>
        </header>

        <h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

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
    </div>

    <a class="post-card__image" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <?php echo TemplateTags::featuredImage($postId, 'imperial-card', ['loading' => 'lazy']); ?>
    </a>

    <div class="post-card__summary">
        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26)); ?></p>
        <a class="post-card__read-more" href="<?php the_permalink(); ?>">
            <?php esc_html_e('Показать полностью', 'imperial-future'); ?>
            <?php echo Icon::render('chevron-down'); ?>
        </a>
    </div>

    <div class="post-card__footer" data-bookmark-panel data-post-id="<?php echo esc_attr((string) $postId); ?>">
        <div class="post-card__reactions" aria-label="<?php esc_attr_e('Реакции', 'imperial-future'); ?>">
            <?php if ($visibleReactions !== []) : ?>
                <?php foreach ($visibleReactions as $value => $count) : ?>
                    <a class="post-card__reaction" href="<?php the_permalink(); ?>#rating" aria-label="<?php echo esc_attr(RatingService::label((string) $value)); ?>">
                        <span aria-hidden="true"><?php echo esc_html(RatingService::label((string) $value)); ?></span>
                        <strong><?php echo esc_html(number_format_i18n((int) $count)); ?></strong>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <a class="post-card__reaction" href="<?php the_permalink(); ?>#rating">
                    <span aria-hidden="true">♥</span>
                    <strong><?php echo esc_html(number_format_i18n($ratingTotal)); ?></strong>
                </a>
            <?php endif; ?>
        </div>

        <div class="post-card__actions">
            <a class="post-card__stat" href="<?php echo esc_url(get_comments_link($postId)); ?>" aria-label="<?php echo esc_attr(RussianPluralizer::comments($commentsCount)); ?>">
                <?php echo Icon::render('comment'); ?>
                <span><?php echo esc_html(number_format_i18n($commentsCount)); ?></span>
            </a>

            <button
                class="post-card__save <?php echo $isBookmarked ? 'is-selected' : ''; ?>"
                type="button"
                data-bookmark-toggle
                data-bookmark-locked="<?php echo esc_attr($isLoggedIn ? '0' : '1'); ?>"
                aria-pressed="<?php echo esc_attr($isBookmarked ? 'true' : 'false'); ?>"
                aria-label="<?php echo esc_attr($isBookmarked ? __('Удалить из закладок', 'imperial-future') : __('Добавить в закладки', 'imperial-future')); ?>"
            >
                <?php echo Icon::render('bookmark'); ?>
                <span class="screen-reader-text" data-bookmark-label><?php echo esc_html($isBookmarked ? __('В закладках', 'imperial-future') : __('Закладка', 'imperial-future')); ?></span>
                <span hidden data-bookmark-icon><?php echo esc_html($isBookmarked ? '★' : '☆'); ?></span>
            </button>

            <button class="post-card__share" type="button" data-card-share data-share-url="<?php echo esc_url(get_permalink($postId)); ?>" aria-label="<?php esc_attr_e('Поделиться', 'imperial-future'); ?>">
                <?php echo Icon::render('share'); ?>
            </button>
        </div>

        <?php if ($popularComment instanceof WP_Comment) : ?>
            <a class="post-card__popular-comment" href="<?php echo esc_url(get_comment_link($popularComment)); ?>">
                <span class="post-card__popular-avatar"><?php echo get_avatar($popularComment, 34); ?></span>
                <span class="post-card__popular-text"><?php echo esc_html(PopularCommentService::excerpt($popularComment)); ?></span>
            </a>
        <?php endif; ?>

        <a class="post-card__comment-row" href="<?php echo esc_url(get_comments_link($postId)); ?>">
            <span><?php echo esc_html(RussianPluralizer::comments($commentsCount)); ?></span>
            <?php echo Icon::render('chevron-right'); ?>
        </a>
    </div>
</article>
