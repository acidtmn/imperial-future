<?php
declare(strict_types=1);

use ImperialFuture\Auth\AvatarService;
use ImperialFuture\Media\ResponsiveImageFormats;

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__, 4) . '/wp-load.php';

$checks = 0;
$files = [];
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) throw new RuntimeException($message);
    echo 'PASS: ' . $message . PHP_EOL;
    ++$checks;
};

try {
    $configured = ['image/jpeg' => 'image/webp', 'image/heic' => 'image/jpeg'];
    $formats = ResponsiveImageFormats::outputFormats($configured);
    $assert($formats['image/jpeg'] === 'image/webp' && $formats['image/heic'] === 'image/jpeg', 'Явные настройки других расширений сохранены');
    $assert(ResponsiveImageFormats::quality(82, 'image/avif') === 60 && ResponsiveImageFormats::quality(40, 'image/avif') === 40, 'AVIF использует ограничение качества без повышения заданного значения');
    $assert(ResponsiveImageFormats::quality(86, 'image/webp') === 86, 'Качество других форматов не меняется');

    $withoutEditors = static fn (array $editors): array => [];
    add_filter('wp_image_editors', $withoutEditors);
    try {
        $assert(ResponsiveImageFormats::outputFormats($configured) === $configured, 'Без поддержки AVIF преобразование не включается');
    } finally {
        remove_filter('wp_image_editors', $withoutEditors);
    }

    $source = tempnam(sys_get_temp_dir(), 'imperial-image-test-');
    $files[] = $source;
    $image = imagecreatetruecolor(800, 600);
    imagepng($image, $source);
    $original = hash_file('sha256', $source);
    $crop = new ReflectionMethod(AvatarService::class, 'cropSquare');
    $saved = $crop->invoke(null, $source, $source . '.png', 'image/png');
    $assert(is_array($saved) && is_file($saved['path']), 'Аватар возвращает фактический путь сохранённого файла');
    $files[] = $saved['path'];
    $info = getimagesize($saved['path']);
    $assert($info[0] === 512 && $info[1] === 512, 'Аватар обрезан до квадрата 512x512');
    $assert($info['mime'] === $saved['mime-type'], 'MIME аватара соответствует сохранённому файлу');
    $assert(hash_file('sha256', $source) === $original, 'Оригинал изображения не изменён');
    $assert($crop->invoke(null, $source, $source . '.gif', 'image/gif') === false, 'Неподдерживаемый формат аватара отклонён');
    if (wp_image_editor_supports(['mime_type' => 'image/avif'])) {
        $assert($saved['mime-type'] === 'image/avif' && str_ends_with($saved['path'], '.avif'), 'Путь и формат аватара согласованы с AVIF');
    }
} finally {
    foreach (array_unique($files) as $file) wp_delete_file($file);
}
echo $checks . ' image checks passed.' . PHP_EOL;
