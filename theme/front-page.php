<?php
declare(strict_types=1);

use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Ads\AdRenderer;
use ImperialFuture\Share\SocialLinks;
use ImperialFuture\Template\TemplateTags;

if (! defined('ABSPATH')) {
    exit;
}

// Статическая главная должна показывать страницу, выбранную владельцем сайта.
if (! is_home()) {
    get_template_part('page');
    return;
}

get_header();

$latest = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => ThemeOptions::number('imperial_posts_count', 3, 12),
    'ignore_sticky_posts' => true,
]);

$telegraph = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => ThemeOptions::number('imperial_telegraph_count', 3, 16),
    'ignore_sticky_posts' => true,
]);

$heroTitle = ThemeOptions::get('imperial_hero_title') ?: get_bloginfo('name');
$tickerItems = ThemeOptions::lines('imperial_hero_ticker_items');
$heroMode = ThemeOptions::get('imperial_hero_mode') === 'slider' ? 'slider' : 'static';
$stickyPosts = array_values(array_filter(array_map('intval', (array) get_option('sticky_posts'))));
$heroQueryArgs = [
    'post_type' => 'post',
    'posts_per_page' => ThemeOptions::number('imperial_hero_slider_count', 2, 8),
    'ignore_sticky_posts' => true,
];

if ($stickyPosts !== []) {
    $heroQueryArgs['post__in'] = $stickyPosts;
    $heroQueryArgs['orderby'] = 'post__in';
}

$heroSlider = $heroMode === 'slider' ? new WP_Query($heroQueryArgs) : null;
?>
<section class="hero hero--<?php echo esc_attr($heroMode); ?>">
    <div class="hero__content">
        <div class="hero__copy">
            <span class="eyebrow" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_hero_eyebrow')); ?></span>
            <h1 class="hero__title" data-reveal><?php echo esc_html($heroTitle); ?></h1>
            <p class="hero__lead" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_hero_lead')); ?></p>
            <div class="hero__actions" data-reveal>
                <a class="button" href="<?php echo esc_url(ThemeOptions::link('imperial_hero_primary_url')); ?>">
                    <?php echo esc_html(ThemeOptions::get('imperial_hero_primary_label')); ?>
                </a>
                <a class="button button--ghost" href="<?php echo esc_url(ThemeOptions::link('imperial_hero_secondary_url')); ?>">
                    <?php echo esc_html(ThemeOptions::get('imperial_hero_secondary_label')); ?>
                </a>
            </div>
        </div>
        <?php if ($heroSlider instanceof WP_Query && $heroSlider->have_posts()) : ?>
            <div class="hero-feature swiper hero-swiper" data-reveal>
                <div class="swiper-wrapper">
                    <?php while ($heroSlider->have_posts()) : ?>
                        <?php $heroSlider->the_post(); ?>
                        <article class="hero-feature__slide swiper-slide">
                            <?php echo TemplateTags::featuredImage(get_the_ID(), 'imperial-hero', ['loading' => $heroSlider->current_post === 0 ? 'eager' : 'lazy', 'data-deferred-image' => $heroSlider->current_post > 0 ? '1' : false, 'sizes' => '(min-width: 920px) 540px, 92vw']); ?>
                            <div class="hero-feature__body">
                                <span><?php echo esc_html(TemplateTags::primaryCategory(get_the_ID())); ?> · <?php echo esc_html(get_the_date()); ?></span>
                                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                            </div>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
                <div class="hero-feature__pagination" data-hero-pagination></div>
            </div>
        <?php endif; ?>
    </div>
    <?php if (ThemeOptions::enabled('imperial_hero_ticker_enabled') && $tickerItems !== []) : ?>
        <div class="hero__ticker" aria-hidden="true">
            <div class="hero__ticker-track">
                <?php for ($i = 0; $i < 2; $i++) : ?>
                    <?php foreach ($tickerItems as $tickerItem) : ?>
                        <span><?php echo esc_html($tickerItem); ?></span>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php AdRenderer::render('after_hero'); ?>

<?php if ($latest->have_posts()) : ?>
    <section class="section" id="chronicle">
        <div class="container">
            <?php AdRenderer::render('before_content'); ?>
            <span class="section-kicker" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_posts_kicker')); ?></span>
            <h2 class="section-title" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_posts_title')); ?></h2>
            <p class="section-lead" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_posts_lead')); ?></p>
            <div class="post-grid post-grid--feed">
                <?php
                while ($latest->have_posts()) :
                    $latest->the_post();
                    get_template_part('parts/content/card');
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (is_active_sidebar('imperial_home_after_posts')) : ?>
    <section class="section section--compact home-widget-band">
        <div class="container home-widget-band__grid" data-reveal>
            <?php dynamic_sidebar('imperial_home_after_posts'); ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($telegraph->have_posts()) : ?>
    <section class="section section--paper telegraph">
        <div class="container">
            <span class="section-kicker" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_telegraph_kicker')); ?></span>
            <h2 class="section-title" data-reveal><?php echo esc_html(ThemeOptions::get('imperial_telegraph_title')); ?></h2>
        </div>
        <div class="telegraph-swiper swiper" data-reveal>
            <div class="swiper-wrapper">
                <?php
                while ($telegraph->have_posts()) :
                    $telegraph->the_post();
                    get_template_part('parts/content/telegraph-item');
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section">
    <div class="container quote-band">
        <blockquote class="archive-quote" data-reveal>
            <p><?php echo esc_html(ThemeOptions::get('imperial_quote_1_text')); ?></p>
            <cite><?php echo esc_html(ThemeOptions::get('imperial_quote_1_cite')); ?></cite>
        </blockquote>
        <blockquote class="archive-quote" data-reveal>
            <p><?php echo esc_html(ThemeOptions::get('imperial_quote_2_text')); ?></p>
            <cite><?php echo esc_html(ThemeOptions::get('imperial_quote_2_cite')); ?></cite>
        </blockquote>
    </div>
</section>

<section class="section section--paper">
    <div class="container author-block" data-reveal>
        <div class="author-block__seal" aria-hidden="true"><?php echo esc_html(ThemeOptions::get('imperial_author_seal')); ?></div>
        <div>
            <span class="section-kicker"><?php echo esc_html(ThemeOptions::get('imperial_author_kicker')); ?></span>
            <h2><?php echo esc_html(ThemeOptions::get('imperial_author_title')); ?></h2>
            <p><?php echo esc_html(ThemeOptions::get('imperial_author_text')); ?></p>
            <?php if (SocialLinks::author() !== []) : ?>
                <div class="social-links">
                    <?php foreach (SocialLinks::author() as $socialLink) : ?>
                        <?php get_template_part('parts/social/link', null, ['link' => $socialLink]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a class="button button--ghost" href="<?php echo esc_url(ThemeOptions::link('imperial_author_button_url')); ?>">
                <?php echo esc_html(ThemeOptions::get('imperial_author_button_label')); ?>
            </a>
        </div>
    </div>
</section>
<?php AdRenderer::render('after_content'); ?>
<?php
get_footer();
