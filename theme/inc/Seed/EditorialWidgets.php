<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

use ImperialFuture\Settings\ThemeOptions;

if (! defined('ABSPATH')) {
    exit;
}

final class EditorialWidgets
{
    public static function replace(array $widgets, array $postIds): array
    {
        $articleUrl = (string) get_permalink($postIds['digital-workshop']);
        $contactUrl = ThemeOptions::link('imperial_hero_secondary_url');

        foreach ($widgets as $number => $widget) {
            if (! is_int($number) || ! is_array($widget) || ! is_string($widget['content'] ?? null)) {
                continue;
            }

            $plain = self::plain($widget['content']);

            // Сверяем весь известный текст: пользовательскую правку похожего виджета сохраняем.
            if ($plain === 'Проверочный зал Демо-материалы собраны для контроля изображений, галерей, файлов, кода, реакций и дерева комментариев. Открыть стенд блоков' && str_contains($widget['content'], '/chronicles/demo-imperial-blocks/')) {
                $widgets[$number]['content'] = EditorialBlocks::heading(__('Начать с небольшого проекта', 'imperial-future'))
                    . EditorialBlocks::paragraph(__('Каталог домашних вещей помогает освоить цифровую мастерскую: поставить задачу, сохранить данные и проверить результат.', 'imperial-future'))
                    . EditorialBlocks::source(__('Читать о первом проекте мастерской', 'imperial-future'), $articleUrl);
            } elseif ($plain === 'Места под промо редакционные подборки; баннеры партнёров; анонсы рассылки; ручные HTML-блоки.') {
                $widgets[$number]['content'] = EditorialBlocks::heading(__('На связи', 'imperial-future'))
                    . EditorialBlocks::paragraph(__('Вопросы по статьям и идеи для мастерской можно отправить автору. Новые материалы собраны в еженедельном дайджесте.', 'imperial-future'))
                    . EditorialBlocks::source(__('Написать через форму контактов', 'imperial-future'), $contactUrl);
            } elseif ($plain === 'Каналы связи Telegram, VK и письма принимаются через страницу контактов. Яндекс.Метрика подключается из кастомайзера.') {
                $widgets[$number]['content'] = EditorialBlocks::heading(__('Каналы связи', 'imperial-future'))
                    . EditorialBlocks::paragraph(__('Вопросы по публикациям и идеи для новых материалов можно отправить автору.', 'imperial-future'))
                    . EditorialBlocks::source(__('Написать автору', 'imperial-future'), $contactUrl);
            }
        }

        return $widgets;
    }

    private static function plain(string $content): string
    {
        $content = (string) preg_replace('/<!--.*?-->/s', '', $content);
        $content = (string) preg_replace('/<[^>]+>/', ' ', $content);

        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
