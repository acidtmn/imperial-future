<?php
declare(strict_types=1);

use ImperialFuture\Bookmark\BookmarkService;
use ImperialFuture\Share\ShareLinks;
use ImperialFuture\Support\Icon;
use ImperialFuture\Support\RussianPluralizer;

if (! defined('ABSPATH')) {
    exit;
}

$postId = get_the_ID();
$isLoggedIn = is_user_logged_in();
$isBookmarked = $isLoggedIn && BookmarkService::isBookmarked(get_current_user_id(), $postId);
$commentsCount = (int) get_comments_number();
?>
<section class="article-actionbar" data-bookmark-panel data-post-id="<?php echo esc_attr((string) $postId); ?>" aria-label="<?php esc_attr_e('Действия публикации', 'imperial-future'); ?>">
    <a class="article-actionbar__button" href="<?php echo esc_url(get_comments_link()); ?>" aria-label="<?php echo esc_attr(RussianPluralizer::comments($commentsCount)); ?>">
        <span class="article-actionbar__icon" aria-hidden="true"><?php echo Icon::render('comment'); ?></span>
        <strong class="article-actionbar__badge"><?php echo esc_html(number_format_i18n($commentsCount)); ?></strong>
    </a>

    <button
        class="article-actionbar__button <?php echo $isBookmarked ? 'is-selected' : ''; ?>"
        type="button"
        data-bookmark-toggle
        data-bookmark-locked="<?php echo esc_attr($isLoggedIn ? '0' : '1'); ?>"
        aria-pressed="<?php echo esc_attr($isBookmarked ? 'true' : 'false'); ?>"
        aria-label="<?php echo esc_attr($isBookmarked ? __('Удалить из закладок', 'imperial-future') : __('Добавить в закладки', 'imperial-future')); ?>"
    >
        <span class="article-actionbar__icon" aria-hidden="true"><?php echo Icon::render('bookmark'); ?></span>
        <strong class="article-actionbar__sr" data-bookmark-label><?php echo esc_html($isBookmarked ? __('В закладках', 'imperial-future') : __('Закладка', 'imperial-future')); ?></strong>
        <span hidden data-bookmark-icon><?php echo esc_html($isBookmarked ? '★' : '☆'); ?></span>
    </button>

    <div class="article-share-menu" data-share-menu>
        <button class="article-actionbar__button" type="button" aria-label="<?php esc_attr_e('Поделиться', 'imperial-future'); ?>" aria-haspopup="true">
            <span class="article-actionbar__icon" aria-hidden="true"><?php echo Icon::render('share'); ?></span>
            <strong class="article-actionbar__sr"><?php esc_html_e('Поделиться', 'imperial-future'); ?></strong>
        </button>
        <div class="article-share-menu__panel">
            <?php foreach (ShareLinks::currentPost() as $link) : ?>
                <?php get_template_part('parts/social/link', null, ['link' => $link]); ?>
            <?php endforeach; ?>
            <button class="social-link article-actionbar__copy" type="button" data-share-copy="<?php echo esc_url(get_permalink()); ?>" data-social-label="<?php esc_attr_e('Скопировать ссылку', 'imperial-future'); ?>">
                <span class="social-link__icon" aria-hidden="true"><?php echo Icon::render('link'); ?></span>
                <span class="social-link__label"><?php esc_html_e('Ссылка', 'imperial-future'); ?></span>
            </button>
        </div>
    </div>
</section>
