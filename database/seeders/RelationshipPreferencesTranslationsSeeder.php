<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class RelationshipPreferencesTranslationsSeeder extends Seeder
{

    public function run(): void
    {
        $items = [
            ['Серьезные отношения', 'Serious relationships', 'Relaciones serias', 'Relations sérieuses', 'Relacionamentos sérios'],
            ['Встречи на один раз', 'One-time meetings', 'Citas de una sola vez', 'Rencontres d\'un soir', 'Encontros de uma vez'],
            ['Серьезные отношения, но не против встречи на один раз', 'Serious relationships, but open to one-time meetings', 'Relaciones serias, pero abierto a citas de una sola vez', 'Relations sérieuses, mais ouvert aux rencontres d\'un soir', 'Relacionamentos sérios, mas aberto a encontros de uma vez'],
            ['Встречи на один раз, но не против регулярных встреч', 'One-time meetings, but open to regular meetings', 'Citas de una sola vez, pero abierto a reuniones regulares', 'Rencontres d\'un soir, mais ouvert aux rencontres régulières', 'Encontros de uma vez, mas aberto a encontros regulares'],
            ['Новых друзей', 'New friends', 'Nuevos amigos', 'Nouveaux amis', 'Novos amigos'],
            ['Все ещё думаю', 'Still thinking', 'Todavía pensando', 'Je réfléchis encore', 'Ainda pensando'],
        ];

        foreach ($items as $i => $item) {
            DB::table('translations')->updateOrInsert(
                ['key' => 'relationship_preferences_' . ($i + 1)],
                [
                    'group' => 'relationship_preferences',
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
