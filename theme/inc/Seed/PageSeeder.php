<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class PageSeeder
{
    public function seed(): array
    {
        return [
            'contacts' => $this->findOrCreate('contacts', 'Контакты редакции', $this->contactsContent(), 'page-contacts.php'),
            'about' => $this->findOrCreate('about', 'Об авторе', $this->aboutContent()),
        ];
    }

    private function findOrCreate(string $slug, string $title, string $content, string $template = ''): int
    {
        $existing = get_page_by_path($slug);

        if ($existing) {
            return (int) $existing->ID;
        }

        $pageId = wp_insert_post([
            'post_type' => 'page',
            'post_name' => $slug,
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'comment_status' => 'closed',
        ]);

        if (is_wp_error($pageId)) {
            return 0;
        }

        if ($template !== '') {
            update_post_meta((int) $pageId, '_wp_page_template', $template);
        }

        return (int) $pageId;
    }

    private function contactsContent(): string
    {
        return '<!-- wp:paragraph --><p>Редакция принимает письма, предложения о коллаборациях и сигналы из будущего. Пишите спокойно и по делу: каждое сообщение проходит через латунный сортировщик внимания.</p><!-- /wp:paragraph -->';
    }

    private function aboutContent(): string
    {
        return '<!-- wp:paragraph --><p>Этот блог ведёт автор, который любит код, историю, аккуратный интерфейс и странное чувство, будто у каждой хорошей идеи есть собственная типография.</p><!-- /wp:paragraph -->';
    }
}
