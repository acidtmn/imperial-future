<?php
declare(strict_types=1);

namespace ImperialFuture\Template;

use ImperialFuture\Reading\ReadingTime;

if (! defined('ABSPATH')) {
    exit;
}

final class TemplateTags
{
    public static function postMeta(?int $postId = null): string
    {
        $postId = $postId ?: get_the_ID();
        $date = get_the_date('j F Y', $postId);
        $minutes = ReadingTime::forPost($postId);

        return sprintf(
            __('%1$s · %2$d мин. чтения', 'imperial-future'),
            $date,
            $minutes
        );
    }

    public static function primaryCategory(?int $postId = null): string
    {
        $postId = $postId ?: get_the_ID();
        $categories = get_the_category($postId);

        return $categories[0]->name ?? __('Архив', 'imperial-future');
    }

    public static function featuredImageUrl(?int $postId = null, string $size = 'imperial-card'): string
    {
        $postId = $postId ?: get_the_ID();

        if (has_post_thumbnail($postId)) {
            $image = wp_get_attachment_image_url((int) get_post_thumbnail_id($postId), $size);

            if ($image) {
                return $image;
            }

            $fullImage = wp_get_attachment_image_url((int) get_post_thumbnail_id($postId), 'full');

            if ($fullImage) {
                return $fullImage;
            }
        }

        return get_template_directory_uri() . '/assets/images/og-imperial.svg';
    }

    /**
     * Формируем обложку с размерами и адаптивными источниками WordPress.
     *
     * @param int|null $postId ID публикации, например 42; null для текущей записи.
     * @param string $size Зарегистрированный размер, например imperial-card.
     * @param array<string,string|bool> $attributes Атрибуты, например ['loading' => 'lazy'].
     * @return string Готовый img; без вложения используем изображение темы 1600x1000.
     */
    public static function featuredImage(?int $postId = null, string $size = 'imperial-card', array $attributes = []): string
    {
        $postId = $postId ?: get_the_ID();
        $attributes = array_merge([
            'alt' => wp_strip_all_tags(get_the_title($postId)),
            'decoding' => 'async',
            'sizes' => $size === 'imperial-hero'
                ? '(max-width: 1172px) calc(100vw - 32px), 1140px'
                : '(max-width: 952px) calc(100vw - 32px), 920px',
        ], $attributes);
        $attachmentId = (int) get_post_thumbnail_id($postId);

        if ($attachmentId > 0) {
            $image = wp_get_attachment_image($attachmentId, $size, false, $attributes);
            if ($image !== '') {
                if (! empty($attributes['data-deferred-image'])) {
                    $processor = new \WP_HTML_Tag_Processor($image);
                    $processor->next_tag('IMG');
                    foreach (['src', 'srcset'] as $name) {
                        $value = $processor->get_attribute($name);
                        if (is_string($value)) {
                            $processor->set_attribute('data-deferred-' . $name, $value);
                            $processor->remove_attribute($name);
                        }
                    }
                    $processor->set_attribute('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
                    return $processor->get_updated_html();
                }
                return $image;
            }
        }

        // Запасная SVG не имеет миниатюр, но резервирует место до загрузки.
        $processor = new \WP_HTML_Tag_Processor('<img>');
        $processor->next_tag('IMG');
        foreach (array_merge($attributes, [
            'src' => self::featuredImageUrl($postId, $size),
            'width' => '1600',
            'height' => '1000',
        ]) as $name => $value) {
            if ($value !== false) {
                $processor->set_attribute($name, (string) $value);
            }
        }

        return $processor->get_updated_html();
    }

    public static function archiveLead(): string
    {
        if (is_category() || is_tag() || is_tax()) {
            return term_description() ?: __('Подборка материалов из имперского цифрового архива.', 'imperial-future');
        }

        if (is_author()) {
            return __('Все публикации автора, собранные в единую редакционную ленту.', 'imperial-future');
        }

        if (is_search()) {
            return sprintf(__('Результаты поиска по запросу «%s».', 'imperial-future'), get_search_query());
        }

        return __('Свежие записи, хроники и заметки из будущего архива.', 'imperial-future');
    }
}
