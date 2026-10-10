<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use ImperialFuture\Support\Icon;
use Walker_Comment;
use WP_Comment;

if (! defined('ABSPATH')) {
    exit;
}

final class ImperialCommentWalker extends Walker_Comment
{
    protected function html5_comment($comment, $depth, $args)
    {
        $GLOBALS['comment'] = $comment;
        $commentId = (int) $comment->comment_ID;
        $counts = CommentVoteService::counts($commentId);
        $voted = is_user_logged_in() ? CommentVoteService::userVote($commentId, get_current_user_id()) : '';
        ?>
        <li id="comment-<?php echo esc_attr((string) $commentId); ?>" <?php comment_class('imperial-comment'); ?>>
            <article id="div-comment-<?php echo esc_attr((string) $commentId); ?>" class="imperial-comment__body">
                <header class="imperial-comment__header">
                    <div class="imperial-comment__avatar"><?php echo get_avatar($comment, 56); ?></div>
                    <div class="imperial-comment__meta">
                        <h3 class="imperial-comment__author"><?php echo esc_html(get_comment_author($comment)); ?></h3>
                        <a class="imperial-comment__date" href="<?php echo esc_url(get_comment_link($comment)); ?>">
                            <time datetime="<?php comment_time('c'); ?>"><?php printf(esc_html__('%1$s в %2$s', 'imperial-future'), esc_html(get_comment_date('j F Y')), esc_html(get_comment_time('H:i'))); ?></time>
                        </a>
                    </div>
                </header>

                <?php if ($comment instanceof WP_Comment && $comment->comment_approved === '0') : ?>
                    <p class="imperial-comment__moderation"><?php esc_html_e('Комментарий ожидает модерации редакции.', 'imperial-future'); ?></p>
                <?php endif; ?>

                <div class="imperial-comment__content">
                    <?php comment_text(); ?>
                </div>

                <footer class="imperial-comment__footer">
                    <div class="imperial-comment-votes" data-comment-vote-panel data-comment-id="<?php echo esc_attr((string) $commentId); ?>" data-comment-vote-locked="<?php echo esc_attr(is_user_logged_in() ? '0' : '1'); ?>">
                        <button class="imperial-comment-votes__button <?php echo $voted === 'up' ? 'is-selected' : ''; ?>" type="button" data-comment-vote="up" aria-pressed="<?php echo esc_attr($voted === 'up' ? 'true' : 'false'); ?>" aria-label="<?php esc_attr_e('Нравится', 'imperial-future'); ?>">
                            <?php echo Icon::render('thumb-up'); ?>
                            <span data-comment-vote-count="up"><?php echo esc_html(number_format_i18n((int) $counts['up'])); ?></span>
                        </button>
                        <button class="imperial-comment-votes__button <?php echo $voted === 'down' ? 'is-selected' : ''; ?>" type="button" data-comment-vote="down" aria-pressed="<?php echo esc_attr($voted === 'down' ? 'true' : 'false'); ?>" aria-label="<?php esc_attr_e('Не нравится', 'imperial-future'); ?>">
                            <?php echo Icon::render('thumb-down'); ?>
                            <span data-comment-vote-count="down"><?php echo esc_html(number_format_i18n((int) $counts['down'])); ?></span>
                        </button>
                        <span class="imperial-comment-votes__message" data-comment-vote-message></span>
                    </div>

                    <?php
                    comment_reply_link(array_merge($args, [
                        'add_below' => 'div-comment',
                        'depth' => $depth,
                        'max_depth' => $args['max_depth'],
                        'reply_text' => __('Ответить', 'imperial-future'),
                    ]));
                    edit_comment_link(__('Править', 'imperial-future'), '<span class="imperial-comment__edit">', '</span>');
                    ?>
                </footer>
            </article>
        <?php
    }
}
