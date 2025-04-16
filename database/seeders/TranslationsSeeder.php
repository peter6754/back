<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationsSeeder extends Seeder
{

    public function run(): void
    {
        $translations = [
            'orientations' => [
                'hetero' => ['Гетеро', 'Heterosexual'],
                'gay' => ['Гей', 'Gay'],
                'lesbian' => ['Лесбиянка', 'Lesbian'],
                'bisexual' => ['Бисексуал', 'Bisexual'],
                'asexual' => ['Асексуал', 'Asexual'],
                'not_decided' => ['Не определился', 'Not decided'],
            ],
            'family_statuses' => [
                'married' => ['Женат / Замужем', 'Married'],
                'not_married' => ['Не женат / Не замужем', 'Single'],
                'widow_er' => ['Вдова / Вдовец', 'Widow(er)'],
                'divorced' => ['Разведен / Разведена', 'Divorced'],
            ],
            'zodiac_signs' => [
                'capricornus' => ['Козерог', 'Capricorn'],
                'aquarius' => ['Водолей', 'Aquarius'],
                'pisces' => ['Рыбы', 'Pisces'],
                'aries' => ['Овен', 'Aries'],
                'taurus' => ['Телец', 'Taurus'],
                'gemini' => ['Близнецы', 'Gemini'],
                'cancer' => ['Рак', 'Cancer'],
                'leo' => ['Лев', 'Leo'],
                'virgo' => ['Дева', 'Virgo'],
                'libra' => ['Весы', 'Libra'],
                'scorpius' => ['Скорпион', 'Scorpio'],
                'sagittarius' => ['Стрелец', 'Sagittarius'],
            ],
            'education' => [
                'lower_secondary' => ['Неполное среднее', 'Lower secondary'],
                'secondary' => ['Среднее', 'Secondary'],
                'specialized_secondary' => ['Среднее специальное', 'Specialized secondary'],
                'incomplete_higher' => ['Неполное высшее', 'Incomplete higher'],
                'higher' => ['Высшее', 'Higher'],
                'two_or_more_higher' => ['Два и более высших', 'Two or more higher'],
                'academic_degree' => ['Академическая степень', 'Academic degree'],
            ],
            'family' => [
                'want_children' => ['Хочу детей', 'Want children'],
                'dont_want_children' => ['Не хочу детей', 'Don’t want children'],
                'have_children_and_want' => ['Есть дети, хочу ещё', 'Have children, want more'],
                'have_children_and_dont_want' => ['Есть дети, не хочу больше', 'Have children, no more'],
                'not_decided' => ['Не решил(а)', 'Not decided'],
            ],
            'communication' => [
                'texting' => ['Переписка', 'Texting'],
                'by_phone' => ['По телефону', 'By phone'],
                'videocall' => ['Видеозвонок', 'Video call'],
                'meet' => ['Встреча', 'Meet'],
            ],
            'love_language' => [
                'gifts' => ['Подарки', 'Gifts'],
                'touches' => ['Прикосновения', 'Touches'],
                'compliments' => ['Комплименты', 'Compliments'],
                'deeds' => ['Поступки', 'Actions'],
                'constant_attention' => ['Постоянное внимание', 'Constant attention'],
            ],
            'pets' => [
                'dog' => ['Собака', 'Dog'],
                'cat' => ['Кошка', 'Cat'],
                'reptile' => ['Рептилия', 'Reptile'],
                'amphibian' => ['Амфибия', 'Amphibian'],
                'bird' => ['Птица', 'Bird'],
                'fish' => ['Рыба', 'Fish'],
                'turtle' => ['Черепаха', 'Turtle'],
                'rabbit' => ['Кролик', 'Rabbit'],
                'hamster' => ['Хомяк', 'Hamster'],
                'i_want' => ['Хочу завести питомца', 'I want a pet'],
                'dont_have' => ['Нет питомца', 'Don’t have a pet'],
            ],
            'alcohol' => [
                'dont_drink' => ['Не пью', 'Don’t drink'],
                'on_holidays' => ['По праздникам', 'On holidays'],
                'on_weekends' => ['По выходным', 'On weekends'],
                'drink_often' => ['Пью часто', 'Drink often'],
            ],
            'smoking' => [
                'for_the_company' => ['За компанию', 'For company'],
                'when_i_drink' => ['Когда пью', 'When I drink'],
                'i_smoke' => ['Курю', 'I smoke'],
                'dont_smoke' => ['Не курю', 'Don’t smoke'],
                'give_up' => ['Бросаю', 'Trying to quit'],
            ],
            'sport' => [
                'train_everyday' => ['Тренируюсь каждый день', 'Train every day'],
                'train_often' => ['Тренируюсь часто', 'Train often'],
                'train_sometimes' => ['Иногда тренируюсь', 'Train sometimes'],
                'dont_train' => ['Не тренируюсь', 'Don’t train'],
            ],
            'food' => [
                'veganism' => ['Веганство', 'Veganism'],
                'vegetarianism' => ['Вегетарианство', 'Vegetarianism'],
                'pescatarianism' => ['Пескатарианство', 'Pescatarianism'],
                'kosher_food' => ['Кошерная еда', 'Kosher food'],
                'everything' => ['Все ем', 'Eat everything'],
            ],
            'social_network' => [
                'influencer' => ['Блогер', 'Influencer'],
                'active_user' => ['Активный пользователь', 'Active user'],
                'dont_use' => ['Не пользуюсь', 'Don’t use'],
                'sometimes_im_on' => ['Иногда захожу', 'Sometimes I’m on'],
            ],
            'sleep' => [
                'lark' => ['Жаворонок', 'Lark'],
                'owl' => ['Сова', 'Owl'],
            ],
        ];

        $now = now();

        foreach ($translations as $group => $items) {
            foreach ($items as $key => $texts) {
                DB::table('translations')->updateOrInsert(
                    ['key' => "{$group}_{$key}"],
                    [
                        'group' => 'users',
                        'translations' => json_encode([
                            'translation_ru' => $texts[0],
                            'translation_en' => $texts[1],
                        ]),
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

}
