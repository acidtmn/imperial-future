<?php
declare(strict_types=1);

namespace ImperialFuture\Media;

if (! defined('ABSPATH')) {
    exit;
}

final class GeneratedFeaturedImageService
{
    public static function generateForPost(int $postId): int
    {
        $upload = wp_upload_dir();

        if (! empty($upload['error']) || ! is_string($upload['path'] ?? null)) {
            return 0;
        }

        $fileName = 'imperial-auto-cover-' . $postId . '-' . time() . '.png';
        $filePath = trailingslashit((string) $upload['path']) . $fileName;

        if (! self::drawImage($postId, $filePath)) {
            return 0;
        }

        $attachmentId = wp_insert_attachment([
            'post_mime_type' => 'image/png',
            'post_title' => 'Imperial cover ' . $postId,
            'post_content' => '',
            'post_status' => 'inherit',
        ], $filePath, $postId);

        if (is_wp_error($attachmentId)) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata((int) $attachmentId, wp_generate_attachment_metadata((int) $attachmentId, $filePath));

        return (int) $attachmentId;
    }

    private static function drawImage(int $postId, string $filePath): bool
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $width = 1640;
        $height = 920;
        $image = imagecreatetruecolor($width, $height);

        if (! $image instanceof \GdImage) {
            return false;
        }

        $void = imagecolorallocate($image, 15, 15, 18);
        $panel = imagecolorallocate($image, 27, 27, 34);
        $gold = imagecolorallocate($image, 212, 175, 55);
        $paper = imagecolorallocate($image, 244, 231, 197);
        $red = imagecolorallocate($image, 111, 32, 36);

        imagefilledrectangle($image, 0, 0, $width, $height, $void);

        for ($x = 0; $x < $width; $x += 54) {
            imageline($image, $x, 0, $x, $height, $panel);
        }

        for ($y = 0; $y < $height; $y += 46) {
            imageline($image, 0, $y, $width, $y, $panel);
        }

        imagerectangle($image, 74, 74, $width - 75, $height - 75, $gold);
        imagerectangle($image, 116, 116, $width - 117, $height - 117, imagecolorallocatealpha($image, 244, 231, 197, 72));

        for ($i = 0; $i < 2600; $i++) {
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), imagecolorallocatealpha($image, 244, 231, 197, random_int(96, 124)));
        }

        imagearc($image, 360, 520, 620, 620, 0, 360, imagecolorallocatealpha($image, 212, 175, 55, 78));
        imagearc($image, 1280, 360, 340, 340, 0, 360, imagecolorallocatealpha($image, 244, 231, 197, 86));
        imagearc($image, 1280, 360, 220, 220, 0, 360, imagecolorallocatealpha($image, 212, 175, 55, 64));
        imageline($image, 160, 710, 1260, 240, imagecolorallocatealpha($image, 212, 175, 55, 32));
        imageline($image, 160, 728, 1260, 258, imagecolorallocatealpha($image, 111, 32, 36, 54));

        self::drawAsciiText($image, $postId, $paper, $gold, $red);

        $saved = imagepng($image, $filePath, 8);
        imagedestroy($image);

        return $saved;
    }

    private static function drawAsciiText(\GdImage $image, int $postId, int $paper, int $gold, int $red): void
    {
        $title = wp_strip_all_tags((string) get_the_title($postId));
        $category = get_the_category($postId)[0]->name ?? 'Imperial Archive';
        $asciiTitle = self::ascii($title) ?: 'Imperial Archive';
        $asciiCategory = self::ascii($category) ?: 'Chronicle';

        imagestring($image, 5, 184, 206, strtoupper($asciiCategory), $gold);
        imagestring($image, 5, 184, 456, self::fit($asciiTitle, 48), $paper);
        imagestring($image, 3, 188, 526, 'AUTO GENERATED IMPERIAL COVER', $red);
        imagestring($image, 3, 188, 558, get_bloginfo('name'), $gold);
    }

    private static function fit(string $value, int $length): string
    {
        return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
    }

    private static function ascii(string $value): string
    {
        $transliterated = remove_accents($value);
        $clean = preg_replace('/[^a-zA-Z0-9 .,:;!?-]+/', '', $transliterated) ?? '';

        return trim($clean);
    }
}
