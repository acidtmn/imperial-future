<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

if (! defined('ABSPATH')) {
    exit;
}

final class CommentSupport
{
    public static function registerHooks(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueReplyScript']);
        add_filter('comment_form_default_fields', [self::class, 'decorateDefaultFields']);
        add_filter('comment_form_defaults', [self::class, 'decorateFormDefaults']);
    }

    public static function enqueueReplyScript(): void
    {
        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }

    public static function decorateDefaultFields(array $fields): array
    {
        $fields['author'] = '<label>' . esc_html__('Имя', 'imperial-future') . '<input name="author" type="text" autocomplete="name" required></label>';
        $fields['email'] = '<label>' . esc_html__('Email', 'imperial-future') . '<input name="email" type="email" autocomplete="email" required></label>';
        $fields['url'] = '<label>' . esc_html__('Сайт', 'imperial-future') . '<input name="url" type="url" autocomplete="url"></label>';

        return $fields;
    }

    public static function decorateFormDefaults(array $defaults): array
    {
        $defaults['class_form'] = 'comment-form imperial-comment-form';
        $defaults['title_reply'] = __('Оставить архивную пометку', 'imperial-future');
        $defaults['title_reply_before'] = '<h2 id="reply-title" class="comment-reply-title">';
        $defaults['title_reply_after'] = '</h2>';
        $defaults['comment_field'] = self::markdownField();
        $defaults['label_submit'] = __('Отправить пометку', 'imperial-future');
        $defaults['comment_notes_before'] = '<p class="comment-notes">' . esc_html__('Комментарий можно писать обычным текстом или Markdown-разметкой.', 'imperial-future') . '</p>';

        return $defaults;
    }

    private static function markdownField(): string
    {
        return '<div class="imperial-md-editor" data-md-editor>' .
            '<label for="comment">' . esc_html__('Комментарий', 'imperial-future') . '</label>' .
            '<textarea id="comment" name="comment" rows="8" required data-md-textarea></textarea>' .
            '<p class="imperial-md-editor__hint">' . esc_html__('Редактор поддерживает жирный текст, курсив, ссылки, цитаты, списки, код и предпросмотр.', 'imperial-future') . '</p>' .
            '</div>';
    }
}
