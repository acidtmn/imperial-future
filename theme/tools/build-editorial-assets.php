<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! extension_loaded('gd')) {
    exit("Нужны PHP CLI и GD.\n");
}

$source = $argv[1] ?? '';
$destination = dirname(__DIR__) . '/assets/images';
$names = ['hero', 'digital-workshop', 'independent-archive', 'home-laboratory', 'access-keys', 'readable-interface', 'quiet-telegraph'];

// Сжимаем исходники без изменения композиции; размеры для srcset создаёт WordPress.
if (! is_dir($destination . '/editorial')) {
    mkdir($destination . '/editorial', 0755, true);
}
foreach ($names as $name) {
    $path = $source . '/' . $name . '.png';
    $image = is_file($path) ? imagecreatefrompng($path) : false;
    if (! $image) {
        throw new RuntimeException('Нет исходника: ' . $path);
    }
    imagewebp($image, $destination . '/editorial/' . $name . '.webp', 82);
    if ($name === 'hero') {
        $preview = imagecreatetruecolor(1200, 630);
        imagecopyresampled($preview, $image, 0, 0, 0, 0, 1200, 630, imagesx($image), imagesy($image));
        imagetruecolortopalette($preview, false, 256);
        imagepng($preview, $destination . '/og-imperial.png', 9);
        imagedestroy($preview);
    }
    printf("%s: %d bytes\n", $name, filesize($destination . '/editorial/' . $name . '.webp'));
    imagedestroy($image);
}

// Растровые иконки повторяют геометрию векторной монограммы, без зависимости от шрифта.
foreach ([32 => 'favicon-32.png', 180 => 'apple-touch-icon.png', 512 => 'site-icon.png'] as $size => $file) {
    $image = imagecreatetruecolor(512, 512);
    $background = imagecolorallocate($image, 23, 25, 27);
    $gold = imagecolorallocate($image, 212, 175, 55);
    $paper = imagecolorallocate($image, 244, 231, 197);
    imagefill($image, 0, 0, $background);
    imagesetthickness($image, 16);
    imageellipse($image, 256, 256, 432, 432, $gold);
    imagesetthickness($image, 24);
    foreach ([[152,144,248,144],[264,144,360,144],[152,368,248,368],[264,368,360,368],[200,144,200,368],[200,368,312,144],[312,144,312,368]] as $line) {
        imageline($image, ...[...$line, $paper]);
    }
    $icon = imagecreatetruecolor($size, $size);
    imagecopyresampled($icon, $image, 0, 0, 0, 0, $size, $size, 512, 512);
    imagepng($icon, $destination . '/brand/' . $file, 9);
    imagedestroy($icon);
    imagedestroy($image);
}
