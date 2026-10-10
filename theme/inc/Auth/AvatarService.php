<?php
declare(strict_types=1);

namespace ImperialFuture\Auth;

if (! defined('ABSPATH')) {
    exit;
}

final class AvatarService
{
    public static function registerHooks(): void
    {
        add_filter('get_avatar_url', [self::class, 'avatarUrl'], 10, 3);
    }

    public static function storeUploaded(int $userId, array $file): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file((string) $file['tmp_name'])) {
            return 0;
        }

        $upload = wp_upload_dir();

        if (! empty($upload['error'])) {
            return 0;
        }

        $source = (string) $file['tmp_name'];
        if ((int) ($file['size'] ?? 0) > 5 * MB_IN_BYTES || filesize($source) > 5 * MB_IN_BYTES) {
            return 0;
        }

        $info = @getimagesize($source);

        if (! is_array($info) || $info[0] * $info[1] > 16000000) {
            return 0;
        }

        $targetPath = trailingslashit((string) $upload['path']) . 'imperial-avatar-' . $userId . '-' . time() . '.png';

        if (! self::cropSquare($source, $targetPath, (string) $info['mime'])) {
            return 0;
        }

        $attachmentId = wp_insert_attachment([
            'post_mime_type' => 'image/png',
            'post_title' => 'Imperial avatar ' . $userId,
            'post_status' => 'inherit',
        ], $targetPath);

        if (is_wp_error($attachmentId)) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata((int) $attachmentId, wp_generate_attachment_metadata((int) $attachmentId, $targetPath));
        update_user_meta($userId, 'imperial_avatar_id', (string) $attachmentId);

        return (int) $attachmentId;
    }

    public static function avatarUrl(string $url, mixed $idOrEmail, array $args): string
    {
        $user = false;

        if ($idOrEmail instanceof \WP_User) {
            $user = $idOrEmail;
        } elseif (is_numeric($idOrEmail)) {
            $user = get_user_by('id', (int) $idOrEmail);
        }

        if (! $user instanceof \WP_User) {
            return $url;
        }

        $avatarId = (int) get_user_meta((int) $user->ID, 'imperial_avatar_id', true);
        $customUrl = $avatarId > 0 ? wp_get_attachment_image_url($avatarId, 'thumbnail') : false;

        return is_string($customUrl) ? $customUrl : $url;
    }

    private static function cropSquare(string $source, string $targetPath, string $mime): bool
    {
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return false;
        }
        $editor = wp_get_image_editor($source);
        if (is_wp_error($editor)) {
            return false;
        }
        $resized = $editor->resize(512, 512, true);
        return ! is_wp_error($resized) && ! is_wp_error($editor->save($targetPath, 'image/png'));
    }
}
