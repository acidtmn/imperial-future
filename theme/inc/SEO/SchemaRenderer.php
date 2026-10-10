<?php
declare(strict_types=1);

namespace ImperialFuture\SEO;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class SchemaRenderer
{
    public static function registerHooks(): void
    {
        add_action('wp_head', [self::class, 'renderJsonLd'], 20);
    }

    public static function renderJsonLd(): void
    {
        if (! SeoPolicy::enabled() || SeoPolicy::privatePage() || is_search() || is_feed()) {
            return;
        }

        $schema = [
            self::websiteSchema(),
            self::breadcrumbsSchema(),
        ];

        if (is_singular('post')) {
            $schema[] = self::articleSchema();
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>' . "\n";
    }

    private static function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'inLanguage' => str_replace('_', '-', get_locale()),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    private static function breadcrumbsSchema(): array
    {
        $items = [];

        foreach (Breadcrumbs::items() as $position => $item) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private static function articleSchema(): array
    {
        $image = SocialImage::url(get_queried_object_id());

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => get_the_title(),
            'description' => get_the_excerpt(),
            'datePublished' => get_the_date(DATE_W3C),
            'dateModified' => get_the_modified_date(DATE_W3C),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) get_post_field('post_author', get_queried_object_id())),
                'url' => get_author_posts_url((int) get_post_field('post_author', get_queried_object_id())),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => ThemeOptions::mediaUrl('imperial_logo_light') ?: get_template_directory_uri() . '/assets/images/og-imperial.png',
                ],
            ],
            'image' => $image,
            'mainEntityOfPage' => get_permalink(),
            'inLanguage' => str_replace('_', '-', get_locale()),
        ];
    }
}
