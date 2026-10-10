<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

use WP_Comment;

if (! defined('ABSPATH')) {
    exit;
}

final class DemoContentSeeder
{
    public function __construct(
        private readonly array $categories,
        private readonly array $media
    ) {
    }

    public function seed(): void
    {
        $blocksPostId = $this->upsertPost([
            'key' => 'demo-imperial-blocks',
            'title' => 'Демо-стенд: все блоки Имперского архива',
            'slug' => 'demo-imperial-blocks',
            'excerpt' => 'Контрольная публикация для проверки типографики, изображений, галерей, таблиц, файлов, кода, цитат и интерактивных блоков темы.',
            'content' => $this->blocksContent(),
            'categories' => [$this->categories['chronicles'] ?? 0, $this->categories['machines'] ?? 0],
            'image' => $this->media['observatory'] ?? 0,
            'date' => '2026-05-15 11:40:00',
            'tags' => ['демо', 'gutenberg', 'визуальная проверка'],
        ]);

        $commentsPostId = $this->upsertPost([
            'key' => 'demo-imperial-comments',
            'title' => 'Демо-стенд: комментарии, реакции и вложенные ответы',
            'slug' => 'demo-imperial-comments',
            'excerpt' => 'Материал для проверки Markdown-комментариев, реакций, аватаров, вложенности и состояния формы обсуждения.',
            'content' => $this->commentsContent(),
            'categories' => [$this->categories['letters'] ?? 0],
            'image' => $this->media['letter'] ?? 0,
            'date' => '2026-05-15 10:15:00',
            'tags' => ['демо', 'комментарии', 'markdown'],
        ]);

        if ($blocksPostId > 0) {
            $this->seedComments($blocksPostId, 'blocks');
        }

        if ($commentsPostId > 0) {
            $this->seedComments($commentsPostId, 'comments');
        }
    }

    private function upsertPost(array $post): int
    {
        $existing = $this->findPost((string) $post['key']);
        if ($existing > 0) {
            return $existing;
        }
        $postData = [
            'ID' => $existing,
            'post_type' => 'post',
            'post_title' => (string) $post['title'],
            'post_name' => (string) $post['slug'],
            'post_excerpt' => (string) $post['excerpt'],
            'post_content' => (string) $post['content'],
            'post_status' => 'draft',
            'post_author' => get_current_user_id() ?: 1,
            'post_category' => array_values(array_filter((array) $post['categories'])),
            'comment_status' => 'open',
            'post_date' => (string) $post['date'],
            'post_date_gmt' => get_gmt_from_date((string) $post['date']),
            'meta_input' => (int) $post['image'] > 0 ? ['_thumbnail_id' => (int) $post['image']] : [],
        ];

        $postId = $existing > 0 ? wp_update_post($postData, true) : wp_insert_post($postData, true);

        if (is_wp_error($postId)) {
            return 0;
        }

        update_post_meta((int) $postId, '_imperial_demo_post_key', (string) $post['key']);

        wp_set_post_tags((int) $postId, (array) $post['tags'], false);

        return (int) $postId;
    }

    private function findPost(string $key): int
    {
        $bySlug = get_page_by_path($key, OBJECT, 'post');

        if ($bySlug) {
            return (int) $bySlug->ID;
        }

        $query = new \WP_Query([
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_imperial_demo_post_key',
            'meta_value' => $key,
        ]);

        return (int) ($query->posts[0] ?? 0);
    }

    private function blocksContent(): string
    {
        $observatory = $this->imageData('observatory');
        $archive = $this->imageData('archive');
        $city = $this->imageData('city');
        $terminal = $this->imageData('terminal');
        $fileId = (int) ($this->media['letter'] ?? 0);
        $fileUrl = $fileId > 0 ? (string) wp_get_attachment_url($fileId) : $terminal['url'];
        $fileName = esc_html(wp_basename((string) wp_parse_url($fileUrl, PHP_URL_PATH)));
        $columnList = EditorialBlocks::listing(['маркированный пункт', 'ещё один пункт', 'строка с более длинным содержанием для адаптива']);

        return <<<HTML
<!-- wp:paragraph {"className":"is-style-lead"} --><p class="is-style-lead">Эта публикация нужна как живой стенд: здесь собраны блоки, которые чаще всего ломают премиальную тему, если у них нет продуманной типографики, отступов и адаптива.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Текст и базовая типографика</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Обычный абзац проверяет межстрочный интервал, ширину строки, ссылки и спокойный ритм чтения. Внутри есть <a href="https://example.com">внешняя ссылка</a>, <strong>важное выделение</strong>, <em>курсивная ремарка</em> и короткий <code>inline_code()</code>.</p><!-- /wp:paragraph -->
<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>Хорошая тема должна одинаково уверенно держать и длинный текст, и маленькую архивную записку на полях.</p><!-- /wp:paragraph --><cite>Проверочный лист редакции</cite></blockquote><!-- /wp:quote -->
<!-- wp:pullquote --><figure class="wp-block-pullquote"><blockquote><p>Премиальный интерфейс начинается там, где даже служебный блок выглядит намеренно.</p><cite>Имперская лаборатория UX</cite></blockquote></figure><!-- /wp:pullquote -->
<!-- wp:heading --><h2 class="wp-block-heading">Изображения и галерея</h2><!-- /wp:heading -->
<!-- wp:image {"id":{$observatory['id']},"sizeSlug":"large","linkDestination":"media"} --><figure class="wp-block-image size-large"><a href="{$observatory['url']}"><img src="{$observatory['url']}" alt="{$observatory['alt']}" class="wp-image-{$observatory['id']}"/></a><figcaption class="wp-element-caption">Одиночное изображение проверяет радиус, подпись, lightbox и соотношение сторон.</figcaption></figure><!-- /wp:image -->
<!-- wp:gallery {"linkTo":"media","columns":3} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image {"id":{$archive['id']},"sizeSlug":"medium","linkDestination":"media"} --><figure class="wp-block-image size-medium"><a href="{$archive['url']}"><img src="{$archive['url']}" alt="{$archive['alt']}" class="wp-image-{$archive['id']}"/></a></figure><!-- /wp:image --><!-- wp:image {"id":{$city['id']},"sizeSlug":"medium","linkDestination":"media"} --><figure class="wp-block-image size-medium"><a href="{$city['url']}"><img src="{$city['url']}" alt="{$city['alt']}" class="wp-image-{$city['id']}"/></a></figure><!-- /wp:image --><!-- wp:image {"id":{$terminal['id']},"sizeSlug":"medium","linkDestination":"media"} --><figure class="wp-block-image size-medium"><a href="{$terminal['url']}"><img src="{$terminal['url']}" alt="{$terminal['alt']}" class="wp-image-{$terminal['id']}"/></a></figure><!-- /wp:image --></figure><!-- /wp:gallery -->
<!-- wp:heading --><h2 class="wp-block-heading">Сетки, таблицы и кнопки</h2><!-- /wp:heading -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Левая колонка</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Проверяет компактные заголовки и переносы в двухколоночной сетке.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Правая колонка</h3><!-- /wp:heading -->{$columnList}</div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:table --><figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>Блок</th><th>Что проверяем</th><th>Статус</th></tr></thead><tbody><tr><td>Изображение</td><td>Пропорции и подпись</td><td>готово</td></tr><tr><td>Код</td><td>Тёмная оболочка и копирование</td><td>готово</td></tr><tr><td>Файл</td><td>Иконка расширения и размер</td><td>готово</td></tr></tbody></table></figure><!-- /wp:table -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#comments">К обсуждению</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/">На главную</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->
<!-- wp:heading --><h2 class="wp-block-heading">Код, файл и служебные блоки</h2><!-- /wp:heading -->
<!-- wp:code --><pre class="wp-block-code"><code>add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('imperial-future');
});</code></pre><!-- /wp:code -->
<!-- wp:preformatted --><pre class="wp-block-preformatted">Телеграфная строка:
скорость, читаемость, характер.</pre><!-- /wp:preformatted -->
<!-- wp:file {"id":{$fileId},"href":"{$fileUrl}"} --><div class="wp-block-file"><a href="{$fileUrl}">{$fileName}</a><a href="{$fileUrl}" class="wp-block-file__button wp-element-button" download>Скачать изображение</a></div><!-- /wp:file -->
HTML;
    }

    private function commentsContent(): string
    {
        $list = EditorialBlocks::listing([
            'у каждого комментария есть аккуратная карточка;',
            'вложенные ответы читаются как дерево;',
            'код получает тёмную оболочку и кнопку копирования;',
            'реакции не ломают высоту строки;',
            'форма комментария остаётся удобной на мобильном.',
        ]);

        return <<<HTML
<!-- wp:paragraph {"className":"is-style-lead"} --><p class="is-style-lead">Эта публикация проверяет комментарии: Markdown, вложенные ответы, код, цитаты, списки, реакции, аватары и форму отправки.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Что должно быть видно</h2><!-- /wp:heading -->
{$list}
<!-- wp:paragraph --><p>Ниже специально оставлены разные типы реплик, чтобы быстро проверять внешний вид после изменений темы.</p><!-- /wp:paragraph -->
HTML;
    }

    private function imageData(string $key): array
    {
        $id = (int) ($this->media[$key] ?? 0);
        $url = $id > 0 ? (string) wp_get_attachment_url($id) : get_template_directory_uri() . '/assets/images/og-imperial.svg';
        $alt = $id > 0 ? (string) get_post_meta($id, '_wp_attachment_image_alt', true) : __('Имперский архив будущего', 'imperial-future');

        return [
            'id' => $id,
            'url' => esc_url($url),
            'alt' => esc_attr($alt),
        ];
    }

    private function seedComments(int $postId, string $prefix): void
    {
        $first = $this->upsertComment($postId, [
            'key' => $prefix . '-typography',
            'author' => 'Типограф ревизор',
            'email' => 'typography@example.test',
            'content' => "**Жирный вывод:** карточка держит длинный текст, *курсивную ремарку* и ссылку на [демо-архив](https://example.com) без визуального шума.",
        ]);

        $second = $this->upsertComment($postId, [
            'key' => $prefix . '-quote-list',
            'author' => 'Читатель хроник',
            'email' => 'reader@example.test',
            'content' => "> Цитата в комментарии должна быть заметной, но не тяжелее самой реплики.\n\n- первый короткий пункт\n- второй пункт с длинной строкой для проверки переноса\n- третий пункт",
        ]);

        $this->upsertComment($postId, [
            'key' => $prefix . '-code',
            'author' => 'Инженер канцелярии',
            'email' => 'engineer@example.test',
            'content' => "Проверка кода в обсуждении:\n\n```php\nadd_filter('comment_text', static function (string \$text): string {\n    return trim(\$text);\n});\n```",
        ]);

        if ($first > 0) {
            $this->upsertComment($postId, [
                'key' => $prefix . '-reply',
                'author' => 'Автор архива',
                'email' => 'author@example.test',
                'parent' => $first,
                'content' => 'Да, именно этого и добиваемся: комментарий должен выглядеть как продолжение публикации, а не как внешний модуль.',
            ]);
        }

        if ($second > 0) {
            $this->upsertComment($postId, [
                'key' => $prefix . '-deep-reply',
                'author' => 'Младший архивариус',
                'email' => 'junior@example.test',
                'parent' => $second,
                'content' => 'Вложенность проверена: линия дерева должна быть тонкой, а отступ — достаточным для чтения на телефоне.',
            ]);
        }

        wp_update_comment_count_now($postId);
    }

    private function upsertComment(int $postId, array $comment): int
    {
        $existing = $this->findComment((string) $comment['key']);
        if ($existing > 0) {
            return $existing;
        }
        $data = [
            'comment_ID' => $existing,
            'comment_post_ID' => $postId,
            'comment_parent' => (int) ($comment['parent'] ?? 0),
            'comment_author' => (string) $comment['author'],
            'comment_author_email' => (string) $comment['email'],
            'comment_content' => (string) $comment['content'],
            'comment_approved' => 1,
            'user_id' => 0,
        ];

        if ($existing > 0) {
            $updated = wp_update_comment($data);
            $commentId = $updated ? $existing : 0;
        } else {
            $commentId = wp_insert_comment($data);
        }

        if (! $commentId) {
            return 0;
        }

        update_comment_meta((int) $commentId, '_imperial_demo_comment_key', (string) $comment['key']);

        return (int) $commentId;
    }

    private function findComment(string $key): int
    {
        $comments = get_comments([
            'status' => 'all',
            'number' => 1,
            'meta_key' => '_imperial_demo_comment_key',
            'meta_value' => $key,
        ]);

        $comment = $comments[0] ?? null;

        return $comment instanceof WP_Comment ? (int) $comment->comment_ID : 0;
    }
}
