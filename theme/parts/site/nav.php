<?php
declare(strict_types=1);

use ImperialFuture\Navigation\HeaderMenuRenderer;

if (! defined('ABSPATH')) {
    exit;
}
?>
<nav class="site-nav" aria-label="<?php esc_attr_e('Главная навигация', 'imperial-future'); ?>">
    <?php HeaderMenuRenderer::renderDesktop(); ?>
</nav>
<details class="mobile-menu">
    <summary aria-label="<?php esc_attr_e('Меню', 'imperial-future'); ?>"><?php echo \ImperialFuture\Support\Icon::render('menu'); ?></summary>
    <?php HeaderMenuRenderer::renderMobile(); ?>
</details>
