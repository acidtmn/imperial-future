<?php
declare(strict_types=1);

namespace ImperialFuture\SEO;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class MetaRenderer
{
    public static function registerHooks(): void
    {
        add_action('wp_head', [self::class, 'renderMetaTags'], 5);
        add_action('wp_head', [self::class, 'renderYandexMetrika'], 30);
        add_filter('wp_robots', [self::class, 'robots']);
        add_filter('document_title_parts', [self::class, 'titleParts']);
        add_filter('wp_sitemaps_posts_query_args', [self::class, 'sitemapArguments'], 10, 2);
    }

    public static function sitemapArguments(array $args, string $postType): array
    {
        if ($postType === 'page') {
            $ids = get_posts(['post_type' => 'page', 'fields' => 'ids', 'numberposts' => -1,
                'meta_key' => '_wp_page_template', 'meta_value' => 'page-account.php']);
            $args['post__not_in'] = array_unique(array_merge($args['post__not_in'] ?? [], $ids));
        }
        return $args;
    }

    public static function renderMetaTags(): void
    {
        if (! SeoPolicy::enabled() || SeoPolicy::privatePage() || is_feed()) {
            return;
        }

        $title = self::title();
        $description = self::description();
        $url = self::canonicalUrl();
        $image = self::imageUrl();

        echo "\n" . '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        // У одиночных публикаций canonical уже выводится ядром WordPress.
        if (! is_singular() && ! is_search()) {
            echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
        }
        echo '<meta name="theme-color" content="#0f0f12">' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
        echo '<meta property="og:type" content="' . esc_attr(is_singular('post') ? 'article' : 'website') . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";

        $verification = ThemeOptions::get('imperial_seo_yandex_verification');

        if ($verification !== '') {
            echo '<meta name="yandex-verification" content="' . esc_attr($verification) . '">' . "\n";
        }

        if (is_singular('post')) {
            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date(DATE_W3C)) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date(DATE_W3C)) . '">' . "\n";
        }
    }

    public static function robots(array $robots): array
    {
        if (SeoPolicy::privatePage() || (SeoPolicy::enabled() && (
            (is_search() && ThemeOptions::enabled('imperial_seo_noindex_search'))
            || (is_archive() && ThemeOptions::enabled('imperial_seo_noindex_archives'))
        ))) {
            unset($robots['index']);
            $robots['noindex'] = true;
        }

        return $robots;
    }

    public static function titleParts(array $parts): array
    {
        if (SeoPolicy::enabled()) {
            $parts['title'] = self::withSuffix((string) ($parts['title'] ?? ''));
        }

        return $parts;
    }

    public static function renderYandexMetrika(): void
    {
        $counterId = trim((string) get_theme_mod('imperial_yandex_metrika_id', ''));

        if ($counterId === '' || ! ctype_digit($counterId)) {
            return;
        }

        ?>
        <script>
            window.ym = window.ym || function(){(window.ym.a = window.ym.a || []).push(arguments)};
            window.ym.l = 1 * new Date();
            ym(<?php echo esc_js($counterId); ?>, "init", {clickmap:true, trackLinks:true, accurateTrackBounce:true});
        </script>
        <script async src="https://mc.yandex.ru/metrika/tag.js"></script>
        <noscript><div><img src="https://mc.yandex.ru/watch/<?php echo esc_attr($counterId); ?>" style="position:absolute; left:-9999px;" alt=""></div></noscript>
        <?php
    }

    private static function title(): string
    {
        return wp_strip_all_tags((string) wp_get_document_title());
    }

    private static function description(): string
    {
        if (is_singular()) {
            $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags((string) get_post_field('post_content', get_the_ID())), 28);

            return $excerpt !== '' ? $excerpt : (string) get_bloginfo('description');
        }

        if (is_category() || is_tag() || is_tax()) {
            $termDescription = wp_strip_all_tags(term_description());
            if ($termDescription !== '') {
                return $termDescription;
            }
        }

        $description = ThemeOptions::get('imperial_seo_default_description');

        return $description !== '' ? $description : (string) get_bloginfo('description');
    }

    private static function canonicalUrl(): string
    {
        if (is_singular()) {
            return (string) get_permalink();
        }

        global $wp;

        return user_trailingslashit(home_url('/' . ($wp->request ?? '')));
    }

    private static function imageUrl(): string
    {
        return SocialImage::url(is_singular() ? get_queried_object_id() : 0);
    }

    private static function withSuffix(string $title): string
    {
        $suffix = ThemeOptions::get('imperial_seo_title_suffix');

        if ($suffix === '') {
            return $title;
        }

        return $title . ' ' . $suffix;
    }
}
