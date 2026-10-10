<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class PostSeeder
{
    public const KEY_META = '_imperial_editorial_key';

    public function __construct(
        private readonly array $categories,
        private readonly array $media
    ) {
    }

    public function seed(): array
    {
        return $this->publish(false);
    }

    public function refresh(): array
    {
        return $this->publish(true);
    }

    public static function findExisting(string $key): int
    {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => array_keys(get_post_stati()),
            'posts_per_page' => 2,
            'fields' => 'ids',
            'meta_key' => self::KEY_META,
            'meta_value' => $key,
            'suppress_filters' => true,
        ]);

        if (count($posts) > 1) {
            throw new \RuntimeException(sprintf(__('Найдено несколько статей с редакционным ключом %s.', 'imperial-future'), $key));
        }

        return (int) ($posts[0] ?? 0);
    }

    private function publish(bool $overwrite): array
    {
        $ids = [];

        foreach (EditorialCatalog::posts() as $post) {
            $existing = self::findExisting($post['key']);

            if ($existing > 0 && ! $overwrite) {
                $ids[$post['key']] = $existing;
                continue;
            }

            $image = (int) ($this->media[$post['key']] ?? 0);
            $categoryIds = [];

            foreach ($post['categories'] as $category) {
                $id = (int) ($this->categories[$category] ?? 0);
                if ($id <= 0) {
                    throw new \RuntimeException(sprintf(__('Не найдена категория %s.', 'imperial-future'), $category));
                }
                $categoryIds[] = $id;
            }

            if ($image <= 0 || ! wp_attachment_is_image($image)) {
                throw new \RuntimeException(sprintf(__('Не найдена обложка статьи %s.', 'imperial-future'), $post['key']));
            }

            if ($existing > 0 && get_post_status($existing) === 'trash' && ! wp_untrash_post($existing)) {
                throw new \RuntimeException(__('Не удалось вернуть редакционную статью из корзины.', 'imperial-future'));
            }

            $data = [
                'post_type' => 'post',
                'post_title' => $post['title'],
                'post_name' => $post['slug'],
                'post_excerpt' => $post['excerpt'],
                'post_content' => $post['content'],
                'post_status' => 'publish',
                'post_author' => $existing > 0 ? (int) get_post_field('post_author', $existing) : get_current_user_id(),
                'post_category' => array_values(array_unique($categoryIds)),
                'comment_status' => 'open',
                'post_date' => $post['date'],
                'post_date_gmt' => get_gmt_from_date($post['date']),
                // Обложка существует до save_post: автоматический генератор не запускается.
                'meta_input' => [self::KEY_META => $post['key'], '_thumbnail_id' => $image],
            ];

            if ($data['post_author'] <= 0) {
                $authors = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
                $data['post_author'] = (int) ($authors[0] ?? 0);
            }

            if ($data['post_author'] <= 0) {
                throw new \RuntimeException(__('Не найден автор для редакционных статей.', 'imperial-future'));
            }

            if ($existing > 0) {
                $data['ID'] = $existing;
            }

            $postId = $existing > 0 ? wp_update_post(wp_slash($data), true) : wp_insert_post(wp_slash($data), true);

            if (is_wp_error($postId) || ! $postId) {
                throw new \RuntimeException(__('Не удалось сохранить редакционную статью.', 'imperial-future') . (is_wp_error($postId) ? ' ' . $postId->get_error_message() : ''));
            }

            $tags = wp_set_post_tags((int) $postId, $post['tags'], false);

            if (is_wp_error($tags) || get_post_meta((int) $postId, self::KEY_META, true) !== $post['key'] || get_post_thumbnail_id((int) $postId) !== $image || get_post_status((int) $postId) !== 'publish') {
                throw new \RuntimeException(__('Сохранённая статья не прошла проверку меток, статуса или обложки.', 'imperial-future'));
            }

            $ids[$post['key']] = (int) $postId;
        }

        return $ids;
    }
}
