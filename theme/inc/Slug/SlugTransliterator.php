<?php
declare(strict_types=1);

namespace ImperialFuture\Slug;

if (! defined('ABSPATH')) {
    exit;
}

final class SlugTransliterator
{
    private const MAP = [
        'А' => 'A',
        'Б' => 'B',
        'В' => 'V',
        'Г' => 'G',
        'Д' => 'D',
        'Е' => 'E',
        'Ё' => 'Yo',
        'Ж' => 'Zh',
        'З' => 'Z',
        'И' => 'I',
        'Й' => 'Y',
        'К' => 'K',
        'Л' => 'L',
        'М' => 'M',
        'Н' => 'N',
        'О' => 'O',
        'П' => 'P',
        'Р' => 'R',
        'С' => 'S',
        'Т' => 'T',
        'У' => 'U',
        'Ф' => 'F',
        'Х' => 'Kh',
        'Ц' => 'Ts',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Sch',
        'Ъ' => '',
        'Ы' => 'Y',
        'Ь' => '',
        'Э' => 'E',
        'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'yo',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'kh',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sch',
        'ъ' => '',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',
        'Ґ' => 'G',
        'ґ' => 'g',
        'Є' => 'Ye',
        'є' => 'ye',
        'І' => 'I',
        'і' => 'i',
        'Ї' => 'Yi',
        'ї' => 'yi',
        'Ў' => 'U',
        'ў' => 'u',
    ];

    public static function toLatin(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
        $value = strtr($value, self::MAP);

        return remove_accents($value);
    }

    public static function toSlug(string $value): string
    {
        $value = strtolower(self::toLatin($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';
        $value = preg_replace('/-+/', '-', $value) ?: '';

        return trim($value, '-');
    }
}
