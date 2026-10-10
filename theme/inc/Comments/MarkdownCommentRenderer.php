<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

use WP_Comment;

if (! defined('ABSPATH')) {
    exit;
}

final class MarkdownCommentRenderer
{
    public static function registerHooks(): void
    {
        add_filter('comment_text', [self::class, 'render'], 99, 3);
        add_filter('pre_comment_content', [MarkdownService::class, 'preserveCodeText'], 1);
    }

    public static function render(string $text, ?WP_Comment $comment = null, array $args = []): string
    {
        return $comment instanceof WP_Comment ? MarkdownService::render((string) $comment->comment_content) : $text;
    }
}
