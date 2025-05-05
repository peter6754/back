<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class GiftCategoriesTranslationsSeeder extends Seeder
{

    public function run(): void
    {
        $items = [
            ['Цветы', 'Flowers', 'Flores', 'Fleurs', 'Flores'],
            ['Украшения', 'Decorations', 'Decoraciones', 'Décorations', 'Decorações'],
            ['Машины', 'Cars', 'Autos', 'Voitures', 'Carros'],
            ['Алкоголь', 'Alcohol', 'Alcohol', 'Alcool', 'Álcool'],
        ];

        foreach ($items as $i => $item) {
            DB::table('translations')->updateOrInsert(
                ['key' => 'gift_categories_' . ($i + 1)],
                [
                    'group' => 'gift_categories',
                    'translations' => json_encode([
                        'translation_ru' => $item[0],
                        'translation_en' => $item[1],
                        'translation_es' => $item[2],
                        'translation_fr' => $item[3],
                        'translation_pt' => $item[4],
                    ], JSON_UNESCAPED_UNICODE),
                ]
            );
        }
    }

}
