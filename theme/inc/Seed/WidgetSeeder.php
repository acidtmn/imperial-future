<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class WidgetSeeder
{
    public function seed(): void
    {
        $widgets = (array) get_option('widget_block', []);
        $sidebars = wp_get_sidebars_widgets();
        $demo = [
            'imperial_sidebar' => ['<h3>Шифр редакции</h3><p>Новые материалы выходят как телеграммы: коротко, точно и с уважением к вниманию читателя.</p>', '<h3>Каналы связи</h3><p>Связаться с редакцией можно через страницу контактов.</p>'],
            'imperial_footer' => ['<p>© ' . esc_html(get_bloginfo('name')) . '</p>'],
            'imperial_home_after_posts' => ['<h3>Редакционная подборка</h3><p>История, культура и технологии в одном архиве.</p>', '<h3>На полях архива</h3><p>Читай новые публикации и возвращайся к любимым материалам.</p>'],
        ];
        $ids = array_filter(array_keys($widgets), 'is_int');
        $next = $ids === [] ? 1 : max($ids) + 1;

        // Заполненные области и номера существующих виджетов не перезаписываем.
        foreach ($demo as $sidebar => $contents) {
            if (! empty($sidebars[$sidebar])) {
                continue;
            }
            foreach ($contents as $content) {
                $widgets[$next] = ['content' => $content];
                $sidebars[$sidebar][] = 'block-' . $next++;
            }
        }
        $widgets['_multiwidget'] = 1;
        update_option('widget_block', $widgets);
        wp_set_sidebars_widgets($sidebars);
    }
}
