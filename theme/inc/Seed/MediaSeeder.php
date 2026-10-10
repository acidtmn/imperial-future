<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class MediaSeeder
{
    public const KEY_META = '_imperial_editorial_image_key';

    public function seed(bool $includeHero = false): array
    {
        $this->validateSources($includeHero);
        $ids = [];

        foreach (EditorialCatalog::images() as $key => $image) {
            if ($key === 'hero' && ! $includeHero) {
                continue;
            }
            $ids[$key] = $this->findOrCreate($key, $image);
        }

        // Имена иллюстраций, используемые демонстрацией блоков.
        foreach (['airship' => 'digital-workshop', 'terminal' => 'home-laboratory', 'archive' => 'independent-archive', 'city' => 'readable-interface', 'observatory' => 'access-keys', 'letter' => 'quiet-telegraph'] as $alias => $key) {
            $ids[$alias] = $ids[$key];
        }

        return $ids;
    }

    public function validateSources(bool $includeHero = false): void
    {
        foreach (EditorialCatalog::images() as $key => $image) {
            if ($key === 'hero' && ! $includeHero) {
                continue;
            }

            $path = $this->sourcePath($key);

            if (! is_file($path) || ! is_readable($path) || wp_get_image_mime($path) !== 'image/webp' || ! wp_getimagesize($path)) {
                throw new \RuntimeException(sprintf(__('Нет читаемого WebP-изображения: %s', 'imperial-future'), $path));
            }
        }

        if (! wp_image_editor_supports(['mime_type' => 'image/webp'])) {
            throw new \RuntimeException(__('Обработчик изображений WordPress не поддерживает WebP.', 'imperial-future'));
        }
    }

    private function findOrCreate(string $key, array $image): int
    {
        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 2,
            'fields' => 'ids',
            'meta_key' => self::KEY_META,
            'meta_value' => $key,
        ]);

        if (count($existing) > 1) {
            throw new \RuntimeException(sprintf(__('Найдено несколько изображений с ключом %s.', 'imperial-future'), $key));
        }

        if ($existing !== []) {
            $id = (int) $existing[0];
            $metadata = wp_get_attachment_metadata($id);

            if (! is_readable((string) get_attached_file($id)) || get_post_mime_type($id) !== 'image/webp' || ! is_array($metadata) || empty($metadata['width']) || empty($metadata['height'])) {
                throw new \RuntimeException(sprintf(__('Редакционное изображение %s повреждено или не имеет метаданных.', 'imperial-future'), $key));
            }

            return $id;
        }

        $contents = file_get_contents($this->sourcePath($key));

        if ($contents === false) {
            throw new \RuntimeException(__('Не удалось прочитать редакционное изображение.', 'imperial-future'));
        }

        $upload = wp_upload_bits($key . '.webp', null, $contents);

        if (! empty($upload['error'])) {
            throw new \RuntimeException((string) $upload['error']);
        }

        $id = wp_insert_attachment([
            'guid' => $upload['url'],
            'post_mime_type' => 'image/webp',
            'post_title' => $image['title'],
            'post_status' => 'inherit',
        ], $upload['file'], 0, true);

        if (is_wp_error($id) || ! $id) {
            wp_delete_file($upload['file']);
            throw new \RuntimeException(__('Не удалось импортировать редакционное изображение.', 'imperial-future'));
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata((int) $id, $upload['file']);
        wp_update_attachment_metadata((int) $id, $metadata);
        $savedMetadata = wp_get_attachment_metadata((int) $id);

        if (empty($metadata['width']) || empty($metadata['height']) || $savedMetadata !== $metadata) {
            wp_delete_attachment((int) $id, true);
            throw new \RuntimeException(__('Не удалось создать размеры редакционного изображения.', 'imperial-future'));
        }

        update_post_meta((int) $id, self::KEY_META, $key);
        update_post_meta((int) $id, '_wp_attachment_image_alt', $image['alt']);

        if (get_post_meta((int) $id, self::KEY_META, true) !== $key || get_post_meta((int) $id, '_wp_attachment_image_alt', true) !== $image['alt']) {
            throw new \RuntimeException(__('Не удалось сохранить метки редакционного изображения.', 'imperial-future'));
        }

        return (int) $id;
    }

    private function sourcePath(string $key): string
    {
        return get_template_directory() . '/assets/images/editorial/' . $key . '.webp';
    }
}
