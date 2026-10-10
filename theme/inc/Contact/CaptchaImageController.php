<?php
declare(strict_types=1);

namespace ImperialFuture\Contact;

if (! defined('ABSPATH')) {
    exit;
}

final class CaptchaImageController
{
    public static function registerHooks(): void
    {
        add_action('admin_post_imperial_contact_captcha_image', [self::class, 'render']);
        add_action('admin_post_nopriv_imperial_contact_captcha_image', [self::class, 'render']);
    }

    public static function render(): never
    {
        $token = sanitize_key((string) ($_GET['token'] ?? ''));
        $payload = CaptchaService::payload($token);

        if ($payload === []) {
            status_header(404);
            exit;
        }

        if (function_exists('imagecreatetruecolor')) {
            self::renderPng($payload);
        }

        self::renderSvg($payload);
    }

    private static function renderPng(array $payload): never
    {
        $width = 360;
        $height = 112;
        $target = self::targetX((int) ($payload['target'] ?? 50), $width);
        $image = imagecreatetruecolor($width, $height);

        if (! $image instanceof \GdImage) {
            self::renderSvg($payload);
        }

        $paper = imagecolorallocate($image, 238, 224, 184);
        $paperShade = imagecolorallocate($image, 218, 196, 142);
        $ink = imagecolorallocate($image, 42, 31, 24);
        $gold = imagecolorallocate($image, 132, 93, 22);
        $red = imagecolorallocate($image, 92, 26, 30);

        imagefilledrectangle($image, 0, 0, $width, $height, $paper);
        imagerectangle($image, 6, 6, $width - 7, $height - 7, $gold);
        imagerectangle($image, 12, 12, $width - 13, $height - 13, $red);
        for ($i = 0; $i < 900; $i++) {
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $paperShade);
        }

        for ($i = 0; $i < 8; $i++) {
            imageline($image, random_int(14, 60), random_int(16, 96), random_int(300, 346), random_int(16, 96), $paperShade);
        }

        imageline($image, 24, 56, 336, 56, $paperShade);
        imagefilledellipse($image, $target, 56, 56, 56, $paper);
        imagesetthickness($image, 2);
        imageellipse($image, $target, 56, 58, 58, $ink);
        imageellipse($image, $target, 56, 46, 46, $gold);
        imageline($image, $target - 12, 56, $target + 12, 56, $gold);
        imageline($image, $target, 44, $target, 68, $gold);

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        imagepng($image);
        imagedestroy($image);
        exit;
    }

    private static function renderSvg(array $payload): never
    {
        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $target = self::targetX((int) ($payload['target'] ?? 50), 360);

        echo '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="112" viewBox="0 0 360 112" role="img" aria-label="captcha">';
        echo '<rect width="360" height="112" fill="#eee0b8"/><rect x="6" y="6" width="348" height="100" fill="none" stroke="#845d16"/><rect x="12" y="12" width="336" height="88" fill="none" stroke="#5c1a1e"/>';
        echo '<path d="M32 34 C96 10 164 96 236 42 S330 74 342 52M24 72 C106 118 164 8 254 78 S322 34 344 68M24 56h312" fill="none" stroke="#dac48e"/>';
        echo '<circle cx="' . esc_attr((string) $target) . '" cy="56" r="29" fill="#eee0b8" stroke="#2a1f18" stroke-width="2"/>';
        echo '<circle cx="' . esc_attr((string) $target) . '" cy="56" r="23" fill="none" stroke="#845d16" stroke-width="2"/>';
        echo '<path d="M' . esc_attr((string) ($target - 12)) . ' 56h24M' . esc_attr((string) $target) . ' 44v24" stroke="#845d16" stroke-width="2"/>';
        echo '</svg>';
        exit;
    }

    private static function targetX(int $percent, int $width): int
    {
        if ($percent > 100) {
            return max(42, min($width - 42, $percent));
        }

        return max(42, min($width - 42, (int) round($width * max(0, min(100, $percent)) / 100)));
    }
}
