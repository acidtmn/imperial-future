<?php
declare(strict_types=1);

namespace ImperialFuture\Contact;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class ContactMessageBuilder
{
    public static function subject(string $subject): string
    {
        $prefix = ThemeOptions::get('imperial_contact_subject_prefix');
        $cleanSubject = $subject !== '' ? $subject : __('Сообщение без темы', 'imperial-future');

        return trim($prefix . ': ' . $cleanSubject);
    }

    public static function body(array $data): string
    {
        return implode("\n\n", [
            'Имя: ' . $data['name'],
            'Email: ' . $data['email'],
            'Тема: ' . ($data['subject'] ?: 'Без темы'),
            'Сообщение:',
            $data['message'],
            'Страница отправки: ' . ($data['source_url'] ?: home_url('/contacts/')),
        ]);
    }
}
