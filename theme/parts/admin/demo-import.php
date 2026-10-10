<?php
if (! defined('ABSPATH')) {
    exit;
}
$status = sanitize_key((string) ($_GET['import_status'] ?? ''));
?>
<div class="wrap">
    <h1><?php esc_html_e('Демо Imperial Future', 'imperial-future'); ?></h1>
    <?php if ($status === 'success') : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Демо-контент добавлен. Существующие материалы, меню и виджеты сохранены.', 'imperial-future'); ?></p></div>
    <?php elseif ($status === 'error') : ?>
        <div class="notice notice-error"><p><?php esc_html_e('Импорт не завершён. Проверь доступ к папке загрузок и повтори попытку.', 'imperial-future'); ?></p></div>
    <?php endif; ?>
    <p><?php esc_html_e('Добавить статьи, изображения, рубрики, страницы контактов и кабинета, примеры комментариев? Меню и виджеты заполняются только в свободных областях.', 'imperial-future'); ?></p>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="imperial_import_demo">
        <?php wp_nonce_field('imperial_import_demo'); ?>
        <?php submit_button(__('Добавить демо-контент', 'imperial-future')); ?>
    </form>
</div>
