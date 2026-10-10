<?php
declare(strict_types=1);

namespace ImperialFuture\Seed;

if (! defined('ABSPATH')) {
    exit;
}

final class CategorySeeder
{
    public function seed(): array
    {
        $categories = [
            'chronicles' => [
                'name' => 'Имперская хроника',
                'description' => 'Короткие наблюдения о технологиях, обществе и странных новостях будущего.',
            ],
            'machines' => [
                'name' => 'Машины и протоколы',
                'description' => 'Практические заметки о коде, инфраструктуре и цифровых механизмах.',
            ],
            'culture' => [
                'name' => 'Культура архива',
                'description' => 'Эссе о книгах, городах, визуальной культуре и дореволюционной эстетике.',
            ],
            'letters' => [
                'name' => 'Письма редактора',
                'description' => 'Личные размышления автора без канцелярского лака.',
            ],
        ];

        $ids = [];

        foreach ($categories as $key => $category) {
            $existing = get_category_by_slug($key);

            if ($existing) {
                $ids[$key] = (int) $existing->term_id;
                continue;
            }

            $result = wp_insert_term($category['name'], 'category', [
                'slug' => $key,
                'description' => $category['description'],
            ]);

            if (! is_wp_error($result)) {
                $ids[$key] = (int) $result['term_id'];
            }
        }

        return $ids;
    }
}
