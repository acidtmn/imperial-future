<?php
declare(strict_types=1);

namespace ImperialFuture\Embed;

use ImperialFuture\External\ExternalLinkService;
use ImperialFuture\Template\TemplateTags;

if (! defined('ABSPATH')) {
    exit;
}

final class LinkCardRenderer
{
    public static function internal(string $url): string
    {
        $postId = url_to_postid($url);

        if ($postId <= 0 || get_post_status($postId) !== 'publish') {
            return self::external($url);
        }

        $title = get_the_title($postId);
        $excerpt = get_the_excerpt($postId);
        $image = TemplateTags::featuredImageUrl($postId, 'imperial-card');

        ob_start();
        ?>
        <a class="imperial-embed-card imperial-embed-card--internal" href="<?php echo esc_url(get_permalink($postId)); ?>">
            <span class="imperial-embed-card__media">
                <img src="<?php echo esc_url($image); ?>" alt="" loading="lazy" decoding="async">
            </span>
            <span class="imperial-embed-card__body">
                <span class="imperial-embed-card__kicker"><?php esc_html_e('Из архива', 'imperial-future'); ?></span>
                <strong><?php echo esc_html($title); ?></strong>
                <em><?php echo esc_html(wp_trim_words($excerpt, 24)); ?></em>
            </span>
        </a>
        <?php
        return (string) ob_get_clean();
    }

    public static function external(string $url): string
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? preg_replace('/^www\./', '', $host) : '';
        $metadata = ExternalMetadataService::forUrl($url);
        $cardUrl = ExternalLinkService::wrapUrl($url);

        if ($host === '') {
            return esc_url($url);
        }

        ob_start();
        ?>
        <a class="imperial-embed-card imperial-embed-card--external" href="<?php echo esc_url($cardUrl); ?>" target="_blank" rel="noopener noreferrer nofollow external">
            <span class="imperial-embed-card__seal" aria-hidden="true">↗</span>
            <span class="imperial-embed-card__body">
                <span class="imperial-embed-card__kicker"><?php echo esc_html((string) ($metadata['host'] ?: $host)); ?></span>
                <strong><?php echo esc_html(wp_trim_words((string) $metadata['title'], 14)); ?></strong>
                <em><?php echo esc_html(wp_trim_words((string) $metadata['description'], 24)); ?></em>
            </span>
        </a>
        <?php
        return (string) ob_get_clean();
    }
}
