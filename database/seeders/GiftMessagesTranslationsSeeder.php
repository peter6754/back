<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class GiftMessagesTranslationsSeeder extends Seeder
{

    public function run(): void
    {
        $items = [
            ['Букет для самой красивой!', 'A bouquet for the most beautiful!', '¡Un ramo para la más hermosa!', 'Un bouquet pour la plus belle !', 'Um buquê para a mais bonita!'],
            ['Букет для самой обаятельной!', 'A bouquet for the most charming!', '¡Un ramo para la más encantadora!', 'Un bouquet pour la plus charmante !', 'Um buquê para a mais charmosa!'],
            ['Букет для самой милой!', 'A bouquet for the sweetest!', '¡Un ramo para la más dulce!', 'Un bouquet pour la plus mignonne !', 'Um buquê para a mais doce!'],
            ['Букет для самой лучшей!', 'A bouquet for the best!', '¡Un ramo para la mejor!', 'Un bouquet pour la meilleure !', 'Um buquê para a melhor!'],
            ['Букет для самой необыкновенной!', 'A bouquet for the most extraordinary!', '¡Un ramo para la más extraordinaria!', 'Un bouquet pour la plus extraordinaire !', 'Um buquê para a mais extraordinária!'],
            ['Кольцо для самой красивой!', 'A ring for the most beautiful!', '¡Un anillo para la más hermosa!', 'Une bague pour la plus belle !', 'Um anel para a mais bonita!'],
            ['Кольцо для самой очаровательной!', 'A ring for the most charming!', '¡Un anillo para la más encantadora!', 'Une bague pour la plus charmante !', 'Um anel para a mais encantadora!'],
            ['Ключ от моего сердца!', 'The key to my heart!', '¡La llave de mi corazón!', 'La clé de mon cœur !', 'A chave do meu coração!'],
            ['Бесценному человеку!', 'To a priceless person!', '¡A una persona invaluable!', 'À une personne inestimable !', 'Para uma pessoa inestimável!'],
            ['Колье для самой нежной!', 'A necklace for the most tender!', '¡Un collar para la más tierna!', 'Un collier pour la plus tendre !', 'Um colar para a mais terna!'],
            ['Дарю крутую тачку!', 'Giving a cool car!', '¡Regalando un auto genial!', 'Je donne une voiture cool !', 'Dando um carro legal!'],
            ['Отдохни сегодня на полную!', 'Relax to the fullest today!', '¡Relájate al máximo hoy!', 'Repose-toi au maximum aujourd\'hui !', 'Relaxe ao máximo hoje!'],
        ];

        foreach ($items as $i => $item) {
            DB::table('translations')->updateOrInsert(
                ['key' => 'gift_messages_' . ($i + 1)],
                [
                    'group' => 'gift_messages',
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
