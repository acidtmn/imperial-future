<?php
declare(strict_types=1);

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH') || ! ThemeOptions::enabled('imperial_search_enabled')) {
    return;
}

$examples = ThemeOptions::lines('imperial_search_examples');
?>
<div class="imperial-modal imperial-modal--search" data-search-modal hidden>
    <div class="imperial-modal__backdrop" data-search-close></div>
    <div class="imperial-modal__panel imperial-search" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Поиск по архиву', 'imperial-future'); ?>">
        <button class="imperial-modal__close" type="button" data-search-close aria-label="<?php esc_attr_e('Закрыть поиск', 'imperial-future'); ?>">×</button>
        <span class="section-kicker"><?php esc_html_e('Архивный поиск', 'imperial-future'); ?></span>
        <h2><?php esc_html_e('Найдём нужный лист', 'imperial-future'); ?></h2>
        <form class="imperial-search__form" action="<?php echo esc_url(home_url('/')); ?>" method="get" data-live-search-form>
            <label class="imperial-search__field">
                <span class="screen-reader-text"><?php esc_html_e('Введите поисковый запрос', 'imperial-future'); ?></span>
                <input type="search" name="s" data-live-search-input autocomplete="off" minlength="3" placeholder="<?php esc_attr_e('Начните с трёх символов...', 'imperial-future'); ?>">
            </label>
            <button type="submit"><?php esc_html_e('Найти', 'imperial-future'); ?></button>
        </form>
        <?php if ($examples !== []) : ?>
            <div class="imperial-search__examples" aria-label="<?php esc_attr_e('Примеры запросов', 'imperial-future'); ?>">
                <?php foreach ($examples as $example) : ?>
                    <button type="button" data-search-example="<?php echo esc_attr($example); ?>"><?php echo esc_html($example); ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="imperial-search__status" data-live-search-status></div>
        <div class="imperial-search__results" data-live-search-results></div>
    </div>
</div>
