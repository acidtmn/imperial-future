<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! defined('ABSPATH')) {
    exit('Запусти через wp eval-file tools/update-russian-translations.php');
}

require_once ABSPATH . WPINC . '/pomo/po.php';
require_once ABSPATH . WPINC . '/pomo/mo.php';

$directory = dirname(__DIR__) . '/languages/';
$template = new PO();
$previous = new PO();
if (! $template->import_from_file($directory . 'imperial-future.pot') || ! $previous->import_from_file($directory . 'ru_RU.po')) {
    throw new RuntimeException('Не удалось прочитать POT или русский перевод.');
}

// Сохраняем переводы; для новых русских исходных строк используем исходный текст.
$translation = new PO();
$translation->headers = $previous->headers;
$translation->set_header('Project-Id-Version', 'Imperial Future ' . wp_get_theme()->get('Version'));
$translation->set_header('POT-Creation-Date', $template->get_header('POT-Creation-Date'));
$translation->set_header('PO-Revision-Date', gmdate('Y-m-d H:iO'));
foreach ($template->entries as $key => $source) {
    $entry = clone $source;
    $existing = $previous->entries[$key] ?? null;
    if ($existing && ! in_array('', $existing->translations, true) && $existing->translations !== []) {
        $entry->translations = $existing->translations;
    } elseif ($entry->is_plural) {
        throw new RuntimeException('Нужны три формы перевода: ' . $entry->singular);
    } else {
        $entry->translations = [$entry->singular];
    }
    $entry->flags = array_values(array_diff($entry->flags, ['fuzzy']));
    $translation->add_entry($entry);
}

$binary = new MO();
$binary->headers = $translation->headers;
foreach ($translation->entries as $entry) {
    $binary->add_entry($entry);
}
if (! $translation->export_to_file($directory . 'ru_RU.po') || ! $binary->export_to_file($directory . 'ru_RU.mo')) {
    throw new RuntimeException('Не удалось сохранить русский перевод.');
}

// Проверяем обратное чтение всех строк после компиляции MO.
$verified = new MO();
if (! $verified->import_from_file($directory . 'ru_RU.mo') || count($verified->entries) !== count($translation->entries)) {
    throw new RuntimeException('Не совпало число переводов в MO.');
}
foreach ($translation->entries as $key => $entry) {
    if (($verified->entries[$key]->translations ?? null) !== $entry->translations) {
        throw new RuntimeException('Не совпал перевод в MO: ' . $entry->singular);
    }
}
echo 'PASS: ' . count($translation->entries) . " переводов PO/MO\n";
