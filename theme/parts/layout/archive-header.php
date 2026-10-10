<?php
declare(strict_types=1);

use ImperialFuture\SEO\Breadcrumbs;
use ImperialFuture\Template\TemplateTags;

if (! defined('ABSPATH')) {
    exit;
}

$archiveTitle = is_category() || is_tag() || is_tax()
    ? single_term_title('', false)
    : wp_strip_all_tags((string) get_the_archive_title());
?>
<header class="archive-header">
    <div class="container">
        <?php Breadcrumbs::render(); ?>
        <span class="section-kicker"><?php esc_html_e('Архивная полоса', 'imperial-future'); ?></span>
        <h1 class="archive-header__title"><?php echo esc_html($archiveTitle ?: __('Все публикации', 'imperial-future')); ?></h1>
        <p class="section-lead"><?php echo wp_kses_post(TemplateTags::archiveLead()); ?></p>
    </div>
</header>
