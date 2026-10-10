<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$link = is_array($args['link'] ?? null) ? $args['link'] : [];
$label = (string) ($link['label'] ?? '');
$url = (string) ($link['url'] ?? '');
$icon = (string) ($link['icon'] ?? '');

if ($label === '' || $url === '') {
    return;
}
?>
<a class="social-link" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($label); ?>" data-social-label="<?php echo esc_attr($label); ?>">
    <?php if ($icon !== '') : ?>
        <span class="social-link__icon" aria-hidden="true">
            <?php if (str_starts_with($url, 'mailto:')) : ?>
                <?php echo \ImperialFuture\Support\Icon::render('mail'); ?>
            <?php else : ?>
                <img src="<?php echo esc_url($icon); ?>" alt="" loading="lazy" width="22" height="22">
            <?php endif; ?>
        </span>
    <?php endif; ?>
    <span class="social-link__label"><?php echo esc_html($label); ?></span>
</a>
