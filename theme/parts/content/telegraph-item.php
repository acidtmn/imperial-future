<?php
declare(strict_types=1);

use ImperialFuture\Template\TemplateTags;

if (! defined('ABSPATH')) {
    exit;
}
?>
<article class="telegraph-item swiper-slide">
    <div class="telegraph-item__meta"><?php echo esc_html(TemplateTags::postMeta(get_the_ID())); ?></div>
    <h3 class="telegraph-item__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
</article>
