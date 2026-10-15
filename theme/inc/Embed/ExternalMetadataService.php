<?php
declare(strict_types=1);

namespace ImperialFuture\Embed;

use ImperialFuture\External\ExternalLinkService;
use ImperialFuture\External\ExternalUrlGuard;

if (! defined('ABSPATH')) {
    exit;
}

final class ExternalMetadataService
{
    public static function forUrl(string $url): array
    {
        $fallback = [
            'title' => __('Внешняя ссылка', 'imperial-future'),
            'description' => $url,
            'host' => ExternalLinkService::host($url),
        ];

        if (! ExternalUrlGuard::isSafeMetadataTarget($url)) {
            return $fallback;
        }

        $cacheKey = 'imperial_embed_meta_' . md5($url);
        $cached = get_transient($cacheKey);

        if (is_array($cached)) {
            return array_merge($fallback, $cached);
        }

        $response = self::fetch($url);

        if ($response === false) {
            return $fallback;
        }

        $html = (string) wp_remote_retrieve_body($response);
        $metadata = self::parse($html);
        $metadata = array_filter([
            'title' => $metadata['title'] ?? '',
            'description' => $metadata['description'] ?? '',
        ]);

        set_transient($cacheKey, $metadata, DAY_IN_SECONDS);

        return array_merge($fallback, $metadata);
    }

    private static function fetch(string $url): array|false
    {
        $visited = [];

        // Каждый адрес проверяем до запроса, не передавая переходы HTTP-транспорту.
        for ($redirects = 0; $redirects <= 3; ++$redirects) {
            $url = explode('#', $url, 2)[0];
            if (isset($visited[$url]) || ! ExternalUrlGuard::isSafeMetadataTarget($url)) {
                return false;
            }
            $visited[$url] = true;

            $response = wp_safe_remote_get($url, [
                'timeout' => 4,
                'redirection' => 0,
                'limit_response_size' => 262144,
                'reject_unsafe_urls' => true,
                'user-agent' => 'ImperialFutureEmbed/1.0; ' . home_url('/'),
            ]);

            if (is_wp_error($response)) {
                return false;
            }

            $status = (int) wp_remote_retrieve_response_code($response);
            if ($status >= 200 && $status < 300) {
                return $response;
            }
            if (! in_array($status, [301, 302, 303, 307, 308], true) || $redirects === 3) {
                return false;
            }

            $location = wp_remote_retrieve_header($response, 'location');
            if (! is_string($location) || trim($location) === '') {
                return false;
            }

            // Относительные Location разбираем так же, как библиотека HTTP в WordPress.
            try {
                $target = \WpOrg\Requests\Iri::absolutize($url, $location);
            } catch (\WpOrg\Requests\Exception $exception) {
                return false;
            }
            if ($target === false) {
                return false;
            }
            $url = $target->uri;
        }

        return false;
    }

    private static function parse(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $title = self::meta($document, 'og:title') ?: self::title($document);
        $description = self::meta($document, 'og:description') ?: self::meta($document, 'description');

        return [
            'title' => wp_strip_all_tags(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'description' => wp_strip_all_tags(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ];
    }

    private static function title(\DOMDocument $document): string
    {
        $nodes = $document->getElementsByTagName('title');

        return $nodes->length > 0 ? trim((string) $nodes->item(0)?->textContent) : '';
    }

    private static function meta(\DOMDocument $document, string $name): string
    {
        foreach ($document->getElementsByTagName('meta') as $node) {
            $property = strtolower((string) ($node->getAttribute('property') ?: $node->getAttribute('name')));

            if ($property === strtolower($name)) {
                return trim((string) $node->getAttribute('content'));
            }
        }

        return '';
    }
}
