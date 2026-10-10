<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! defined('ABSPATH')) {
    exit('Run with wp eval-file scripts/export-settings.php');
}

require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
$manager = new WP_Customize_Manager();
\ImperialFuture\Support\Customizer::registerControls($manager);
\ImperialFuture\Support\FeatureCustomizer::registerControls($manager);
$sections = [];

// Экспортируем только определения, не сохранённые значения и секреты сайта.
foreach ($manager->controls() as $control) {
    if (! str_starts_with($control->id, 'imperial_')) {
        continue;
    }
    $section = $manager->get_section($control->section);
    $default = $control->setting->default;
    if (in_array($control->id, ['imperial_contact_recipient_email', 'imperial_contact_from_email', 'imperial_social_email'], true)) {
        $default = 'Email администратора WordPress';
    } elseif ($control->id === 'imperial_contact_from_name') {
        $default = 'Название сайта';
    }
    $sections[$control->section]['title'] = $section->title;
    $sections[$control->section]['priority'] = $section->priority;
    $sections[$control->section]['controls'][] = [
        'id' => $control->id,
        'label' => $control->label,
        'type' => $control->type,
        'default' => $default,
        'choices' => $control->choices,
        'input_attrs' => $control->input_attrs,
    ];
}
uasort($sections, static fn (array $left, array $right): int => $left['priority'] <=> $right['priority']);
echo wp_json_encode(array_values($sections), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
