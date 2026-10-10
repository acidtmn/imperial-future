<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class CommentSeeder
{
    public function seed(): void
    {
        $post = get_page_by_path('ministry-blog', OBJECT, 'post');

        if (! $post || $this->alreadySeeded((int) $post->ID)) {
            return;
        }

        $firstCommentId = wp_insert_comment([
            'comment_post_ID' => (int) $post->ID,
            'comment_author' => 'Редактор типографии',
            'comment_author_email' => 'editor@example.test',
            'comment_content' => 'Очень нравится идея архива как интерфейса: сразу понятно, что у сайта есть собственный голос.',
            'comment_approved' => 1,
        ]);

        if (! $firstCommentId) {
            return;
        }

        add_comment_meta((int) $firstCommentId, '_imperial_seed_comment', '1', true);

        $replyId = wp_insert_comment([
            'comment_post_ID' => (int) $post->ID,
            'comment_parent' => (int) $firstCommentId,
            'comment_author' => 'Автор архива',
            'comment_author_email' => 'author@example.test',
            'comment_content' => 'Да, хотелось сделать не витрину, а место с характером: чтобы каждая публикация ощущалась как найденный документ.',
            'comment_approved' => 1,
        ]);

        if ($replyId) {
            add_comment_meta((int) $replyId, '_imperial_seed_comment', '1', true);
        }
    }

    private function alreadySeeded(int $postId): bool
    {
        $comments = get_comments([
            'post_id' => $postId,
            'meta_key' => '_imperial_seed_comment',
            'meta_value' => '1',
            'count' => true,
        ]);

        return (int) $comments > 0;
    }
}
