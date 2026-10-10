<?php
declare(strict_types=1);

use ImperialFuture\Comments\ImperialCommentWalker;
use ImperialFuture\Support\RussianPluralizer;

if (! defined('ABSPATH')) {
    exit;
}

if (post_password_required()) {
    return;
}
?>
<section class="comments-area" id="comments">
    <div class="container">
        <span class="section-kicker"><?php esc_html_e('Архивные пометки', 'imperial-future'); ?></span>
        <h2 class="comments-title">
            <?php echo esc_html(RussianPluralizer::comments((int) get_comments_number())); ?>
        </h2>

        <?php if (have_comments()) : ?>
            <ol class="comment-list">
                <?php
                wp_list_comments([
                    'style' => 'ol',
                    'short_ping' => true,
                    'avatar_size' => 56,
                    'walker' => new ImperialCommentWalker(),
                ]);
                ?>
            </ol>

            <?php the_comments_navigation(); ?>
        <?php endif; ?>

        <?php if (! comments_open() && get_comments_number()) : ?>
            <p class="no-comments"><?php esc_html_e('Канцелярия закрыла обсуждение этой публикации.', 'imperial-future'); ?></p>
        <?php endif; ?>

        <?php comment_form(); ?>
    </div>
</section>
