<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$tags = get_the_tags();

if ($tags === false || $tags === []) {
    return;
}
?>
<nav class="article-tags" aria-label="<?php esc_attr_e('Теги материала', 'imperial-future'); ?>">
    <span class="article-tags__label"><?php esc_html_e('Бирки архива', 'imperial-future'); ?></span>
    <?php foreach ($tags as $tag) : ?>
        <a href="<?php echo esc_url(get_tag_link($tag)); ?>"><?php echo esc_html($tag->name); ?></a>
    <?php endforeach; ?>
</nav>
