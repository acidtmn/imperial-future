<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class SidebarRegistrar
{
    public static function registerHooks(): void
    {
        add_action('widgets_init', [self::class, 'registerSidebars']);
    }

    public static function registerSidebars(): void
    {
        register_sidebar([
            'name' => __('Боковая колонка статьи', 'imperial-future'),
            'id' => 'imperial_sidebar',
            'description' => __('Виджеты для sticky-колонки внутри публикаций и архивов.', 'imperial-future'),
            'before_widget' => '<section id="%1$s" class="widget imperial-widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="imperial-widget__title">',
            'after_title' => '</h2>',
        ]);

        register_sidebar([
            'name' => __('Нижний архивный пояс', 'imperial-future'),
            'id' => 'imperial_footer',
            'description' => __('Короткие редакционные блоки в футере.', 'imperial-future'),
            'before_widget' => '<section id="%1$s" class="widget footer-widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="footer-widget__title">',
            'after_title' => '</h2>',
        ]);

        register_sidebar([
            'name' => __('Домашняя витрина виджетов', 'imperial-future'),
            'id' => 'imperial_home_after_posts',
            'description' => __('Промо, подборки и редакционные виджеты между лентой и телеграфной хроникой.', 'imperial-future'),
            'before_widget' => '<section id="%1$s" class="widget imperial-widget imperial-widget--home %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="imperial-widget__title">',
            'after_title' => '</h2>',
        ]);
    }
}
