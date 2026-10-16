<?php
declare(strict_types=1);

namespace ImperialFuture\Media;

use ImperialFuture\Auth\AvatarService;

if (! defined('ABSPATH')) {
    exit;
}

final class DefaultAvatar
{
    public static function registerHooks(): void
    {
        add_filter('avatar_defaults', [self::class, 'registerDefault']);
        add_filter('pre_option_avatar_default', [self::class, 'defaultOption']);
        add_filter('pre_get_avatar_data', [self::class, 'replaceMissingAvatar'], 10, 2);
    }

    public static function registerDefault(array $defaults): array
    {
        $defaults[self::url()] = __('Имперская печать', 'imperial-future');

        return $defaults;
    }

    public static function defaultOption(): string
    {
        return self::url();
    }

    public static function replaceMissingAvatar(array $args, mixed $idOrEmail): array
    {
        if (! empty($args['url'])) {
            return $args;
        }

        $args['url'] = AvatarService::avatarUrl(self::url(), $idOrEmail, $args);

        return $args;
    }

    public static function url(): string
    {
        return get_template_directory_uri() . '/assets/images/default-avatar.svg';
    }
}
