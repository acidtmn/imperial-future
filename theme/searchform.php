<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="search-field"><?php esc_html_e('Найти в архиве', 'imperial-future'); ?></label>
    <input id="search-field" class="search-field" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Поиск по архиву', 'imperial-future'); ?>">
    <button type="submit"><?php esc_html_e('Найти', 'imperial-future'); ?></button>
</form>
