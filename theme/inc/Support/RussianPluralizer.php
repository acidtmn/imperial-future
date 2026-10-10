<?php
declare(strict_types=1);

namespace ImperialFuture\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class RussianPluralizer
{
    /**
     * Русский язык требует три формы слова, поэтому стандартного _n без .mo-перевода
     * недостаточно: в локальной теме иначе получается "2 комментариев".
     *
     * @param array{0:string,1:string,2:string} $forms
     */
    public static function format(int $count, array $forms): string
    {
        return sprintf('%s %s', number_format_i18n($count), self::word($count, $forms));
    }

    /**
     * @param array{0:string,1:string,2:string} $forms
     */
    public static function word(int $count, array $forms): string
    {
        $value = abs($count);
        $mod10 = $value % 10;
        $mod100 = $value % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $forms[0];
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $forms[1];
        }

        return $forms[2];
    }

    public static function comments(int $count): string
    {
        return self::format($count, [
            __('комментарий', 'imperial-future'),
            __('комментария', 'imperial-future'),
            __('комментариев', 'imperial-future'),
        ]);
    }

    public static function replies(int $count): string
    {
        return self::format($count, [
            __('ответ', 'imperial-future'),
            __('ответа', 'imperial-future'),
            __('ответов', 'imperial-future'),
        ]);
    }

    public static function votes(int $count): string
    {
        return self::format($count, [
            __('голос', 'imperial-future'),
            __('голоса', 'imperial-future'),
            __('голосов', 'imperial-future'),
        ]);
    }
}
