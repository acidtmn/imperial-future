<?php
declare(strict_types=1);

use ImperialFuture\Embed\ExternalMetadataService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('DISABLE_WP_CRON', true);
$root = dirname(__DIR__, 4);
require_once $root . '/wp-includes/plugin.php';

$routes = [];
$requests = [];
$active = false;
$cacheKeys = [];
$checks = 0;
$failure = null;

// Перехватываем весь HTTP ещё до загрузки WordPress: реальная сеть недоступна тесту.
$transport = static function (mixed $response, array $args, string $url) use (&$routes, &$requests, &$active): array|WP_Error {
    if (! $active) {
        return new WP_Error('embed_test_network_disabled', 'Network disabled during bootstrap.');
    }

    $requests[] = $url;
    if (($args['redirection'] ?? null) !== 0 || ($args['reject_unsafe_urls'] ?? null) !== true) {
        throw new RuntimeException('HTTP must disable automatic redirects and retain URL validation.');
    }
    if (($args['method'] ?? '') !== 'GET' || ($args['limit_response_size'] ?? null) !== 262144) {
        throw new RuntimeException('Unexpected HTTP method or response size limit.');
    }
    if (! isset($routes[$url])) {
        throw new RuntimeException('Unexpected HTTP request: ' . $url);
    }

    return $routes[$url];
};
add_filter('pre_http_request', $transport, PHP_INT_MAX, 3);
require_once $root . '/wp-load.php';

$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
    echo 'OK ' . $checks . ': ' . $message . PHP_EOL;
};
$response = static function (int $status, string $location = '', string $body = ''): array {
    return [
        'headers' => $location === '' ? [] : ['location' => $location],
        'body' => $body,
        'response' => ['code' => $status, 'message' => 'Fixture'],
        'cookies' => [],
        'filename' => null,
    ];
};
$fallback = static fn (string $url): array => [
    'title' => __('Внешняя ссылка', 'imperial-future'),
    'description' => $url,
    'host' => (string) wp_parse_url($url, PHP_URL_HOST),
];
$fetch = static function (string $url, array $fixtures) use (&$routes, &$requests, &$active, &$cacheKeys): array {
    $key = 'imperial_embed_meta_' . md5($url);
    $cacheKeys[] = $key;
    $routes = $fixtures;
    $requests = [];
    $active = true;

    try {
        return ExternalMetadataService::forUrl($url);
    } finally {
        $active = false;
    }
};

try {
    // IP-адреса исключают даже DNS-запросы валидатора; ответы даёт только stub.
    $base = 'https://8.8.8.8/embed-security-' . bin2hex(random_bytes(8));
    $public = 'https://1.1.1.1/embed-security-' . bin2hex(random_bytes(8));
    $html = '<html><head><title>Public destination</title><meta name="description" content="Safe metadata"></head></html>';

    // Непубличный адрес нельзя запрашивать ни первым, ни после публичного перехода.
    foreach (['http://127.0.0.1/', 'http://10.0.0.1/', 'http://192.168.1.1/', 'http://169.254.169.254/', 'http://[::1]/', 'http://[fe80::1]/'] as $index => $private) {
        $private .= wp_basename($base) . '/blocked-' . $index;
        $start = $base . '/blocked-' . $index;
        $result = $fetch($start, [$start => $response(302, $private, '<title>Redirect body</title>')]);
        $assert($requests === [$start] && $result === $fallback($start), 'Публичный редирект заблокирован до запроса: ' . $private);
        $assert(get_transient('imperial_embed_meta_' . md5($start)) === false, 'Отказ не кеширует тело редиректа');

        $result = $fetch($private, []);
        $assert($requests === [] && $result === $fallback($private), 'Прямой непубличный адрес заблокирован: ' . $private);
    }

    $start = $base . '/later-private';
    $middle = $public . '/middle';
    $result = $fetch($start, [$start => $response(301, $middle), $middle => $response(307, '//169.254.169.254/latest/')]);
    $assert($requests === [$start, $middle] && $result === $fallback($start), 'Проверяется и второй переход, включая Location без схемы');

    // Обычные статусы и формы публичного Location сохраняют поддержку карточек.
    foreach ([301, 302, 303, 307, 308] as $status) {
        $start = $base . '/public-' . $status;
        $destination = $public . '/final-' . $status;
        $result = $fetch($start, [$start => $response($status, $destination), $destination => $response(200, '', $html)]);
        $assert($requests === [$start, $destination] && $result['title'] === 'Public destination' && $result['description'] === 'Safe metadata', 'Публичный переход ' . $status . ' получает метаданные конечного ответа');
        $assert(get_transient('imperial_embed_meta_' . md5($start))['title'] === 'Public destination', 'Метаданные кешируются под исходным адресом');
        $cached = $fetch($start, []);
        $assert($requests === [] && $cached === $result, 'Повторное чтение использует кеш без HTTP');
    }

    $start = $base . '/relative/start';
    $next = $base . '/relative/next?step=1';
    $queried = $base . '/relative/next?step=2';
    $destination = $public . '/relative-final';
    $result = $fetch($start, [
        $start => $response(302, './next?step=1'),
        $next => $response(303, '?step=2'),
        $queried => $response(308, '//1.1.1.1' . wp_parse_url($destination, PHP_URL_PATH)),
        $destination => $response(200, '', $html),
    ]);
    $assert($requests === [$start, $next, $queried, $destination] && $result['title'] === 'Public destination', 'Относительные, query-only и междоменные Location работают в пределах трёх переходов');

    // Циклы и превышение лимита завершаются без повторного или лишнего запроса.
    $start = $base . '/self-loop';
    $result = $fetch($start, [$start => $response(302, $start)]);
    $assert($requests === [$start] && $result === $fallback($start), 'Саморедирект остановлен до повторного запроса');

    $start = $base . '/fragment-loop';
    $result = $fetch($start, [$start => $response(302, '#again')]);
    $assert($requests === [$start] && $result === $fallback($start), 'Фрагмент не позволяет повторно запросить тот же адрес');

    $start = $base . '/loop-a';
    $other = $public . '/loop-b';
    $result = $fetch($start, [$start => $response(302, $other), $other => $response(301, $start)]);
    $assert($requests === [$start, $other] && $result === $fallback($start), 'Цикл между публичными адресами остановлен');

    $start = $base . '/limit-0';
    $fixtures = [];
    $expected = [];
    for ($i = 0; $i <= 3; ++$i) {
        $url = $base . '/limit-' . $i;
        $expected[] = $url;
        $fixtures[$url] = $response(302, $base . '/limit-' . ($i + 1));
    }
    $result = $fetch($start, $fixtures);
    $assert($requests === $expected && $result === $fallback($start), 'Четвёртый переход запрещён до запроса');

    foreach (['file:///etc/passwd', 'javascript:alert(1)', ''] as $index => $location) {
        $start = $base . '/invalid-' . $index;
        $result = $fetch($start, [$start => $response(302, $location, '<title>Redirect body</title>')]);
        $assert($requests === [$start] && $result === $fallback($start), 'Недопустимый или отсутствующий Location не исполняется и не кешируется');
    }

    $start = $base . '/transport-error';
    $result = $fetch($start, [$start => new WP_Error('fixture_error', 'Transport failed.')]);
    $assert($requests === [$start] && $result === $fallback($start), 'Ошибка транспорта возвращает исходную карточку');
} catch (Throwable $exception) {
    $failure = $exception;
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
} finally {
    $active = false;
    foreach (array_unique($cacheKeys) as $key) {
        delete_transient($key);
    }
    remove_filter('pre_http_request', $transport, PHP_INT_MAX);
}

exit($failure === null ? 0 : 1);
