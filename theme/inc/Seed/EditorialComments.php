<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class EditorialComments
{
    public static function seed(array $postIds): void
    {
        $examples = [
            ['key' => 'workshop-checklist', 'post' => 'digital-workshop', 'content' => __('Демонстрационный комментарий: проверку сохранения удобно выполнять отдельно от проверки внешнего вида. Создать запись, закрыть страницу и снова открыть её - короткий воспроизводимый сценарий.', 'imperial-future')],
            ['key' => 'backup-restore', 'post' => 'independent-archive', 'content' => __('Демонстрационный вопрос: входит ли в проверку восстановления открытие самих документов? Наличие имени файла ещё не показывает, что внутри сохранились нужные данные.', 'imperial-future')],
            ['key' => 'docker-code', 'post' => 'home-laboratory', 'content' => __("Демонстрационный пример команд для своего каталога лаборатории:\n\n```sh\ndocker compose ps\ndocker compose logs web\n```\n\nКоманды показывают состояние и журнал; они не очищают чужие проекты.", 'imperial-future')],
            ['key' => 'docker-list', 'post' => 'home-laboratory', 'content' => __("Демонстрационный список перед запуском:\n\n- проверить свободный локальный порт;\n- подключить только папку примера;\n- не добавлять личные данные;\n- записать команду остановки.", 'imperial-future')],
            ['key' => 'docker-reply', 'post' => 'home-laboratory', 'parent' => 'docker-code', 'content' => __("Демонстрационный ответ с цитатой:\n\n> Пересоздание помогает проверить, где остались файлы.\n\nПосле запуска полезно изменить index.html, пересоздать контейнер и сравнить содержимое страницы. Это проверяет именно подключённую папку.", 'imperial-future')],
        ];
        $commentIds = [];

        foreach ($examples as $index => $example) {
            $found = get_comments(['status' => 'all', 'number' => 2, 'meta_key' => '_imperial_editorial_comment_key', 'meta_value' => $example['key']]);

            if (count($found) > 1) {
                throw new \RuntimeException(__('Обнаружены дубли редакционных демокомментариев.', 'imperial-future'));
            }

            if ($found !== []) {
                if ((int) $found[0]->comment_post_ID !== $postIds[$example['post']]) {
                    throw new \RuntimeException(__('Демокомментарий связан с другой статьёй.', 'imperial-future'));
                }
                $commentIds[$example['key']] = (int) $found[0]->comment_ID;
                continue;
            }

            $id = wp_insert_comment(wp_slash([
                'comment_post_ID' => $postIds[$example['post']],
                'comment_parent' => isset($example['parent']) ? $commentIds[$example['parent']] : 0,
                'comment_author' => __('Демо-читатель', 'imperial-future'),
                'comment_author_email' => 'demo-reader-' . ($index + 1) . '@example.test',
                'comment_content' => $example['content'],
                'comment_type' => 'comment',
                'comment_approved' => 1,
                'user_id' => 0,
                'comment_meta' => ['_imperial_seed_comment' => '1', '_imperial_editorial_comment_key' => $example['key']],
            ]));

            if (! $id || get_comment_meta((int) $id, '_imperial_editorial_comment_key', true) !== $example['key'] || get_comment_meta((int) $id, '_imperial_seed_comment', true) !== '1') {
                throw new \RuntimeException(__('Не удалось сохранить редакционный демокомментарий.', 'imperial-future'));
            }

            $commentIds[$example['key']] = (int) $id;
        }
    }
}
