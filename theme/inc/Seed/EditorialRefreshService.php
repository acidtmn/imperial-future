<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class EditorialRefreshService
{
    public const BACKUP_OPTION = 'imperial_editorial_refresh_backup';

    public function refresh(): array
    {
        if (! defined('WP_CLI') || ! WP_CLI || ! current_user_can('edit_others_posts') || ! current_user_can('edit_theme_options')) {
            throw new \RuntimeException(__('Запусти обновление через WP-CLI с параметром --user=admin.', 'imperial-future'));
        }

        if (! defined('EMPTY_TRASH_DAYS') || ! EMPTY_TRASH_DAYS) {
            throw new \RuntimeException(__('Корзина отключена. Обновление остановлено, чтобы не удалить старые статьи навсегда.', 'imperial-future'));
        }

        if (get_transient('imperial_demo_import_running')) {
            throw new \RuntimeException(__('Уже выполняется импорт демонстрационного содержимого.', 'imperial-future'));
        }

        $mediaSeeder = new MediaSeeder();
        $mediaSeeder->validateSources(true);
        $owned = [];

        foreach (EditorialCatalog::posts() as $post) {
            $id = PostSeeder::findExisting($post['key']);
            if ($id > 0) {
                $owned[$post['key']] = $id;
            }
        }

        $backup = get_option(self::BACKUP_OPTION, null);

        // Первую точку восстановления никогда не заменяем результатом повторного запуска.
        if ($backup === null) {
            $backup = $this->snapshot($owned);
            if (! add_option(self::BACKUP_OPTION, $backup, '', false)) {
                throw new \RuntimeException(__('Не удалось сохранить исходную точку восстановления.', 'imperial-future'));
            }
        }

        if (! is_array($backup) || ($backup['version'] ?? 0) !== 1 || ($backup['stylesheet'] ?? '') !== get_stylesheet() || ! isset($backup['archived_posts'], $backup['theme_mods'], $backup['options'])) {
            throw new \RuntimeException(__('Точка восстановления повреждена или относится к другой теме.', 'imperial-future'));
        }

        $run = $this->snapshot($owned);
        $archivedThisRun = [];
        $disableCovers = static fn (): string => '0';
        add_filter('theme_mod_imperial_auto_featured_image_enabled', $disableCovers);
        set_transient('imperial_demo_import_running', '1', 30 * MINUTE_IN_SECONDS);

        try {
            $categories = (new CategorySeeder())->seed();
            $media = $mediaSeeder->seed(true);
            $ids = (new PostSeeder($categories, $media))->refresh();

            if (count($ids) !== 6 || count(array_unique($ids)) !== 6) {
                throw new \RuntimeException(__('Новый набор статей создан не полностью; старые статьи не перемещались.', 'imperial-future'));
            }

            EditorialComments::seed($ids);
            $heroUrl = get_template_directory_uri() . '/assets/images/editorial/hero.webp';
            $mods = EditorialCatalog::themeMods($media['hero']);

            foreach ($mods as $name => $value) {
                set_theme_mod($name, $value);
                if (get_theme_mod($name) !== $value) {
                    throw new \RuntimeException(sprintf(__('Не удалось сохранить настройку %s.', 'imperial-future'), $name));
                }
            }

            $this->saveOption('blogname', __('Имперский архив', 'imperial-future'));
            $this->saveOption('blogdescription', __('Понятные статьи об IT и домашней мастерской', 'imperial-future'));
            $featured = [];
            foreach (EditorialCatalog::posts() as $post) {
                if ($post['featured']) {
                    $featured[] = $ids[$post['key']];
                }
            }
            $this->saveOption('sticky_posts', $featured);
            $this->saveOption('widget_block', EditorialWidgets::replace((array) get_option('widget_block', []), $ids));
            $this->updateAuthor();

            // Только после создания и проверки всего нового набора убираем старые post.
            foreach ($backup['archived_posts'] as $id => $previous) {
                $post = get_post((int) $id);
                if (! $post instanceof \WP_Post) {
                    throw new \RuntimeException(sprintf(__('Архивная статья %d отсутствует; проверь резервную копию.', 'imperial-future'), $id));
                }
                if ($post->post_type !== 'post' || in_array((int) $id, $ids, true)) {
                    throw new \RuntimeException(__('Архивная запись не соответствует границам обновления.', 'imperial-future'));
                }
                if ($post->post_status !== 'trash') {
                    if (! in_array($post->post_status, ['publish', 'draft'], true)) {
                        throw new \RuntimeException(__('Статус архивной статьи изменился; автоматическое перемещение остановлено.', 'imperial-future'));
                    }
                    $archivedThisRun[(int) $id] = $post->post_status;
                    if (! wp_trash_post((int) $id) || get_post_status((int) $id) !== 'trash') {
                        throw new \RuntimeException(__('Не удалось переместить старую статью в корзину.', 'imperial-future'));
                    }
                }

                // У этих архивных записей нет срока автоочистки стандартной корзины WP.
                update_post_meta((int) $id, '_imperial_editorial_archived', '1');
                delete_post_meta((int) $id, '_wp_trash_meta_time');
                if (get_post_meta((int) $id, '_imperial_editorial_archived', true) !== '1' || metadata_exists('post', (int) $id, '_wp_trash_meta_time')) {
                    throw new \RuntimeException(__('Не удалось защитить архивную статью от автоматической очистки.', 'imperial-future'));
                }
            }

            return ['posts' => $ids, 'archived' => array_map('intval', array_keys($backup['archived_posts'])), 'hero_url' => $heroUrl, 'backup_option' => self::BACKUP_OPTION];
        } catch (\Throwable $exception) {
            $errors = $this->rollback($run, $archivedThisRun, array_keys($mods ?? []));
            throw new \RuntimeException($exception->getMessage() . ($errors === [] ? __(' Изменения оформления и перемещения этой попытки отменены.', 'imperial-future') : __(' Откат неполный: ', 'imperial-future') . implode('; ', $errors)) . ' ' . sprintf(__('Исходная точка восстановления: %s.', 'imperial-future'), self::BACKUP_OPTION), 0, $exception);
        } finally {
            delete_transient('imperial_demo_import_running');
            remove_filter('theme_mod_imperial_auto_featured_image_enabled', $disableCovers);
        }
    }

    private function snapshot(array $owned): array
    {
        $archived = [];
        foreach (get_posts(['post_type' => 'post', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => true]) as $post) {
            if (! in_array((int) $post->ID, $owned, true)) {
                $archived[(int) $post->ID] = ['status' => $post->post_status, 'slug' => $post->post_name];
            }
        }
        $options = [];
        foreach (['blogname', 'blogdescription', 'sticky_posts', 'widget_block', 'sidebars_widgets'] as $name) {
            $missing = new \stdClass();
            $value = get_option($name, $missing);
            $options[$name] = ['exists' => $value !== $missing, 'value' => $value !== $missing ? $value : null];
        }
        $admin = get_user_by('login', 'admin');

        return [
            'version' => 1,
            'created_at' => current_time('mysql'),
            'stylesheet' => get_stylesheet(),
            'archived_posts' => $archived,
            'editorial_ids' => $owned,
            'theme_mods' => (array) get_theme_mods(),
            'options' => $options,
            'author' => $admin instanceof \WP_User ? ['id' => (int) $admin->ID, 'display_name' => $admin->display_name] : null,
        ];
    }

    private function updateAuthor(): void
    {
        $admin = get_user_by('login', 'admin');
        if (! $admin instanceof \WP_User || ! in_array(trim($admin->display_name), ['', 'admin', 'Administrator', 'Администратор'], true)) {
            return;
        }
        $name = __('Кирилл Александров', 'imperial-future');
        $result = wp_update_user(['ID' => (int) $admin->ID, 'display_name' => $name]);
        $updated = get_userdata((int) $admin->ID);
        if (is_wp_error($result) || ! $updated instanceof \WP_User || $updated->display_name !== $name) {
            throw new \RuntimeException(__('Не удалось обновить отображаемое имя автора.', 'imperial-future'));
        }
    }

    private function saveOption(string $name, mixed $value): void
    {
        update_option($name, $value);
        if (get_option($name) !== $value) {
            throw new \RuntimeException(sprintf(__('Не удалось сохранить параметр %s.', 'imperial-future'), $name));
        }
    }

    private function rollback(array $run, array $archived, array $changedMods): array
    {
        $errors = [];
        foreach ($archived as $id => $status) {
            if (get_post_status($id) === 'trash') {
                wp_untrash_post($id);
            }
            $result = wp_update_post(['ID' => $id, 'post_status' => $status, 'post_name' => $run['archived_posts'][$id]['slug'] ?? get_post_field('post_name', $id)], true);
            delete_post_meta($id, '_imperial_editorial_archived');
            if (is_wp_error($result) || get_post_status($id) !== $status) {
                $errors[] = sprintf(__('статья %d', 'imperial-future'), $id);
            }
        }
        foreach ($changedMods as $name) {
            if (array_key_exists($name, $run['theme_mods'])) {
                set_theme_mod($name, $run['theme_mods'][$name]);
            } else {
                remove_theme_mod($name);
            }
        }
        foreach ($run['options'] as $name => $saved) {
            if ($saved['exists']) {
                update_option($name, $saved['value']);
            } else {
                delete_option($name);
            }
            $missing = new \stdClass();
            $value = get_option($name, $missing);
            if ($saved['exists'] ? $value !== $saved['value'] : $value !== $missing) {
                $errors[] = $name;
            }
        }
        if ($run['author'] !== null) {
            $result = wp_update_user(['ID' => $run['author']['id'], 'display_name' => $run['author']['display_name']]);
            if (is_wp_error($result)) {
                $errors[] = __('имя автора', 'imperial-future');
            }
        }

        return $errors;
    }
}
