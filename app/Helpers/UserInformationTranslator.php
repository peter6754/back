<?php

namespace App\Helpers;

class UserInformationTranslator
{

    // Константы для полов
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_MF = 'm_f';
    public const GENDER_MM = 'm_m';
    public const GENDER_FF = 'f_f';

    // Константы для сексуальной ориентации
    public const ORIENTATION_HETERO = 'hetero';
    public const ORIENTATION_GAY = 'gay';
    public const ORIENTATION_LESBIAN = 'lesbian';
    public const ORIENTATION_BISEXUAL = 'bisexual';
    public const ORIENTATION_ASEXUAL = 'asexual';
    public const ORIENTATION_NOT_DECIDED = 'not_decided';

    // Константы для семейного статуса
    public const FAMILY_STATUS_MARRIED = 'married';
    public const FAMILY_STATUS_NOT_MARRIED = 'not_married';
    public const FAMILY_STATUS_WIDOW = 'widow_er';
    public const FAMILY_STATUS_DIVORCED = 'divorced';

    // Константы для знаков зодиака
    public const ZODIAC_CAPRICORN = 'capricornus';
    public const ZODIAC_AQUARIUS = 'aquarius';
    public const ZODIAC_PISCES = 'pisces';
    public const ZODIAC_ARIES = 'aries';
    public const ZODIAC_TAURUS = 'taurus';
    public const ZODIAC_GEMINI = 'gemini';
    public const ZODIAC_CANCER = 'cancecr';
    public const ZODIAC_LEO = 'leo';
    public const ZODIAC_VIRGO = 'virgo';
    public const ZODIAC_LIBRA = 'libra';
    public const ZODIAC_SCORPIO = 'scorpius';
    public const ZODIAC_SAGITTARIUS = 'sagittarius';

    // Константы для образования
    public const EDUCATION_LOWER_SECONDARY = 'lower_secondary';
    public const EDUCATION_SECONDARY = 'secondary';
    public const EDUCATION_SPECIALIZED_SECONDARY = 'specialized_secondary';
    public const EDUCATION_INCOMPLETE_HIGHER = 'incomplete_higher';
    public const EDUCATION_HIGHER = 'higher';
    public const EDUCATION_TWO_OR_MORE_HIGHER = 'two_or_more_higher';
    public const EDUCATION_ACADEMIC_DEGREE = 'academic_degree';

    // Константы для планов на семью
    public const FAMILY_WANT_CHILDREN = 'want_children';
    public const FAMILY_DONT_WANT_CHILDREN = 'dont_want_children';
    public const FAMILY_HAVE_AND_WANT_MORE = 'have_children_and_want';
    public const FAMILY_HAVE_AND_DONT_WANT_MORE = 'have_children_and_dont_want';
    public const FAMILY_NOT_DECIDED = 'not_decided';

    // Константы для стиля общения
    public const COMMUNICATION_TEXTING = 'texting';
    public const COMMUNICATION_PHONE = 'by_phone';
    public const COMMUNICATION_VIDEOCALL = 'videocall';
    public const COMMUNICATION_MEET = 'meet';

    // Константы для языков любви
    public const LOVE_LANGUAGE_GIFTS = 'gifts';
    public const LOVE_LANGUAGE_TOUCHES = 'touches';
    public const LOVE_LANGUAGE_COMPLIMENTS = 'compliments';
    public const LOVE_LANGUAGE_DEEDS = 'deeds';
    public const LOVE_LANGUAGE_ATTENTION = 'constant_attention';

    // Константы для домашних животных
    public const PET_DOG = 'dog';
    public const PET_CAT = 'cat';
    public const PET_REPTILE = 'reptile';
    public const PET_AMPHIBIAN = 'amphibian';
    public const PET_BIRD = 'bird';
    public const PET_FISH = 'fish';
    public const PET_TURTLE = 'turtle';
    public const PET_RABBIT = 'rabbit';
    public const PET_HAMSTER = 'hamster';
    public const PET_WANT = 'i_want';
    public const PET_DONT_HAVE = 'dont_have';

    // Константы для отношения к алкоголю
    public const ALCOHOL_DONT_DRINK = 'dont_drink';
    public const ALCOHOL_HOLIDAYS = 'on_holidays';
    public const ALCOHOL_WEEKENDS = 'on_weekends';
    public const ALCOHOL_OFTEN = 'drink_often';

    // Константы для отношения к курению
    public const SMOKING_FOR_COMPANY = 'for_the_company';
    public const SMOKING_WHEN_DRINK = 'when_i_drink';
    public const SMOKING_I_SMOKE = 'i_smoke';
    public const SMOKING_DONT_SMOKE = 'dont_smoke';
    public const SMOKING_GIVE_UP = 'give_up';

    // Константы для отношения к спорту
    public const SPORT_EVERYDAY = 'train_everyday';
    public const SPORT_OFTEN = 'train_often';
    public const SPORT_SOMETIMES = 'train_sometimes';
    public const SPORT_DONT_TRAIN = 'dont_train';

    // Константы для пищевых предпочтений
    public const FOOD_VEGANISM = 'veganism';
    public const FOOD_VEGETARIANISM = 'vegetarianism';
    public const FOOD_PESCETARIANISM = 'pescatarianism';
    public const FOOD_KOSHER = 'kosher_food';
    public const FOOD_EVERYTHING = 'everything';

    // Константы для отношения к соцсетям
    public const SOCIAL_INFLUENCER = 'influencer';
    public const SOCIAL_ACTIVE_USER = 'active_user';
    public const SOCIAL_DONT_USE = 'dont_use';
    public const SOCIAL_SOMETIMES = 'sometimes_im_on';

    // Константы для хронотипов сна
    public const SLEEP_LARK = 'lark';
    public const SLEEP_OWL = 'owl';

    private static $translations = [
        'family_statuses' => [
            self::FAMILY_STATUS_MARRIED => ['Женат', 'Замужем'],
            self::FAMILY_STATUS_NOT_MARRIED => ['Не женат', 'Не замужем'],
            self::FAMILY_STATUS_WIDOW => ['Вдовец', 'Вдова'],
            self::FAMILY_STATUS_DIVORCED => ['Разведен', 'Разведена'],
        ],
        'orientations' => [
            self::ORIENTATION_HETERO => 'Гетеро',
            self::ORIENTATION_GAY => 'Гей',
            self::ORIENTATION_LESBIAN => 'Лесбиянка',
            self::ORIENTATION_BISEXUAL => 'Бисексуал(ка)',
            self::ORIENTATION_ASEXUAL => 'Асексуал(ка)',
            self::ORIENTATION_NOT_DECIDED => 'Не определился(лась)',
        ],
        'genders' => [
            self::GENDER_MALE => 'Мужчина',
            self::GENDER_FEMALE => 'Женщина',
            self::GENDER_MF => 'М+Ж',
            self::GENDER_MM => 'М+М',
            self::GENDER_FF => 'Ж+Ж',
        ],
        'zodiac_signs' => [
            self::ZODIAC_CAPRICORN => 'Козерог',
            self::ZODIAC_AQUARIUS => 'Водолей',
            self::ZODIAC_PISCES => 'Рыбы',
            self::ZODIAC_ARIES => 'Овен',
            self::ZODIAC_TAURUS => 'Телец',
            self::ZODIAC_GEMINI => 'Близнецы',
            self::ZODIAC_CANCER => 'Рак',
            self::ZODIAC_LEO => 'Лев',
            self::ZODIAC_VIRGO => 'Дева',
            self::ZODIAC_LIBRA => 'Весы',
            self::ZODIAC_SAGITTARIUS => 'Стрелец',
            self::ZODIAC_SCORPIO => 'Скорпион',
        ],
        'education' => [
            self::EDUCATION_LOWER_SECONDARY => 'Неполное среднее',
            self::EDUCATION_SECONDARY => 'Среднее',
            self::EDUCATION_SPECIALIZED_SECONDARY => 'Среднее специальное',
            self::EDUCATION_INCOMPLETE_HIGHER => 'Неполное высшее',
            self::EDUCATION_HIGHER => 'Высшее',
            self::EDUCATION_TWO_OR_MORE_HIGHER => 'Два или более высших',
            self::EDUCATION_ACADEMIC_DEGREE => 'Ученая степень',
        ],
        'family' => [
            self::FAMILY_WANT_CHILDREN => 'Я хочу детей',
            self::FAMILY_DONT_WANT_CHILDREN => 'Я не хочу детей',
            self::FAMILY_HAVE_AND_WANT_MORE => 'У меня есть дети и хочу еще',
            self::FAMILY_HAVE_AND_DONT_WANT_MORE => 'Есть дети, но больше не хочу',
            self::FAMILY_NOT_DECIDED => 'Пока не знаю, хочу ли детей',
        ],
        'communication' => [
            self::COMMUNICATION_TEXTING => 'Люблю переписываться',
            self::COMMUNICATION_PHONE => 'Люблю общаться по телефону',
            self::COMMUNICATION_VIDEOCALL => 'Больше нравятся видеозвонки',
            self::COMMUNICATION_MEET => 'Лучше встречусь лично',
        ],
        'love_language' => [
            self::LOVE_LANGUAGE_GIFTS => 'Нравятся подарки',
            self::LOVE_LANGUAGE_TOUCHES => 'Нравятся прикосновения',
            self::LOVE_LANGUAGE_COMPLIMENTS => 'Нравятся комплименты',
            self::LOVE_LANGUAGE_DEEDS => 'Нравятся поступки',
            self::LOVE_LANGUAGE_ATTENTION => 'Нравится постоянное внимание',
        ],
        'pets' => [
            self::PET_DOG => 'Собака',
            self::PET_CAT => 'Кошка',
            self::PET_REPTILE => 'Рептилия',
            self::PET_AMPHIBIAN => 'Амфибия',
            self::PET_BIRD => 'Птица',
            self::PET_FISH => 'Рыбки',
            self::PET_TURTLE => 'Черепаха',
            self::PET_RABBIT => 'Кролик',
            self::PET_HAMSTER => 'Хомяк',
            self::PET_WANT => 'Нет, но хочу питомца',
            self::PET_DONT_HAVE => 'Нет питомцев',
        ],
        'alcohol' => [
            self::ALCOHOL_DONT_DRINK => 'Я не пью',
            self::ALCOHOL_HOLIDAYS => 'Пью по праздникам',
            self::ALCOHOL_WEEKENDS => 'Пью по выходным',
            self::ALCOHOL_OFTEN => 'Пью часто',
        ],
        'smoking' => [
            self::SMOKING_FOR_COMPANY => 'Курю за компанию',
            self::SMOKING_WHEN_DRINK => 'Курю, когда выпью',
            self::SMOKING_I_SMOKE => 'Курю',
            self::SMOKING_DONT_SMOKE => 'Не курю',
            self::SMOKING_GIVE_UP => 'Бросаю',
        ],
        'sport' => [
            self::SPORT_EVERYDAY => 'Тренируюсь каждый день',
            self::SPORT_OFTEN => 'Часто тренируюсь',
            self::SPORT_SOMETIMES => 'Иногда тренируюсь',
            self::SPORT_DONT_TRAIN => 'Не занимаюсь спортом',
        ],
        'food' => [
            self::FOOD_VEGANISM => 'Веганство',
            self::FOOD_VEGETARIANISM => 'Вегетарианство',
            self::FOOD_PESCETARIANISM => 'Пескетарианство',
            self::FOOD_KOSHER => 'Кошерная еда',
            self::FOOD_EVERYTHING => 'Ем всё',
        ],
        'social_network' => [
            self::SOCIAL_INFLUENCER => 'Инфлюенсер соцсетей',
            self::SOCIAL_ACTIVE_USER => 'Активный пользователь соцсетей',
            self::SOCIAL_DONT_USE => 'Меня нет в соцсетях',
            self::SOCIAL_SOMETIMES => 'Иногда захожу в соцсети',
        ],
        'sleep' => [
            self::SLEEP_LARK => 'Я жаворонок',
            self::SLEEP_OWL => 'Я сова',
        ],
    ];

    /**
     * Получить перевод по категории и ключу
     *
     * @param string $category Категория перевода (например, 'genders')
     * @param string $key Ключ перевода (например, 'male')
     * @param string|null $gender Пол пользователя ('male' или 'female') для гендерных переводов
     * @return string
     */
    public static function translate(string $category, string $key, ?string $gender = null): string
    {
        if (!isset(self::$translations[$category][$key])) {
            return $key;
        }

        $translation = self::$translations[$category][$key];

        if (is_array($translation)) {
            return $gender === self::GENDER_FEMALE ? $translation[1] : $translation[0];
        }

        return $translation;
    }

    /**
     * Получить все переводы для категории
     *
     * @param string $category
     * @return array
     */
    public static function getTranslationsForCategory(string $category): array
    {
        return self::$translations[$category] ?? [];
    }

}
