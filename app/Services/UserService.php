<?php

namespace App\Services;

use App\Helpers\UserInformationTranslator;
use App\Models\BlockedContacts;
use App\Models\ConnectedAccount;
use App\Models\LikeSettings;
use App\Models\Secondaryuser;
use App\Models\UserPreference;
use App\Models\UserSettings;
use App\Models\UserInformation;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Log;

class UserService
{
    private $maleGenders = [
        UserInformationTranslator::GENDER_MALE,
        UserInformationTranslator::GENDER_MM,
        UserInformationTranslator::GENDER_MF,
    ];

    /**
     * @return array
     *
     * @throws \Throwable
     */
    public function getUser(string $id, array $viewer)
    {
        return $this->fetchUserData($id, $viewer);
    }

    /**
     * @throws \Throwable
     */
    private function fetchUserData(string $id, array $viewer): array
    {
        return DB::transaction(function () use ($id, $viewer) {
            $results = [
                $this->getUserInfo($id),
                $this->getUserAgeAndDistance($id, $viewer),
            ];

            [$info, $withQueryRaw] = $results;

            return $this->formatResponse($info, $withQueryRaw);
        });
    }

    private function getUserInfo(string $id): array
    {
        $user = Secondaryuser::with([
            'finalPreference:preference',
            'interests.interest:id,name',
            'images' => fn ($query) => $query->select('user_id', 'image')->take(4),
            'verificationRequest:user_id,status',
            'receivedGifts' => fn ($query) => $query->with('gift:id,image')
                ->whereHas('transaction', fn ($q) => $q->where('status', 'succeeded'))
                ->take(2),
            'settings:user_id,show_my_orientation,show_my_gender,show_my_age,show_distance_from_me',
            'city:user_id,formatted_address',
            'userInformation',
            'pets' => fn ($query) => $query->select('user_id', 'pet'),
        ])->withCount([
            'receivedGifts as gifts_count' => fn ($query) => $query->whereHas('transaction', fn ($q) => $q->where('status', 'succeeded')),
            'feedbacks as feedbacks_count',
        ])->findOrFail($id);

        return $user->toArray();
    }

    private function getUserAgeAndDistance(string $id, array $viewer): array
    {
        $result = DB::selectOne('
            SELECT CAST(
                IF(has_user_subscription(u.id) AND NOT us.show_my_age, null, u.age ) AS CHAR
            ) as age,
            CAST(
                IF(has_user_subscription(u.id) AND NOT us.show_distance_from_me, null,
                    ROUND(
                        (SELECT count_distance(u.id, ?, ?)), 0
                    )
                ) AS CHAR
            ) as distance
            FROM users u
            LEFT JOIN user_settings us ON us.user_id = u.id
            WHERE u.id = ?
        ', [$viewer['lat'], $viewer['long'], $id]);

        return (array) $result;
    }

    private function formatResponse(array $info, array $withQueryRaw): array
    {
        $userSettings = $info['settings'] ?? $info['user_settings'] ?? [];
        $userInformation = $info['user_information'] ?? [];
        $gender = $info['gender'] ?? null;

        $infoItems = [
            ($userSettings['show_my_orientation'] ?? false) && ! empty($info['sexual_orientation'])
            ? UserInformationTranslator::translate('orientations', $info['sexual_orientation'])
            : null,
            ! empty($userInformation['zodiac_sign'])
            ? UserInformationTranslator::translate('zodiac_signs', $userInformation['zodiac_sign'])
            : null,
            ! empty($userInformation['alcohole'])
            ? UserInformationTranslator::translate('alcohol', $userInformation['alcohole'])
            : null,
            ! empty($userInformation['smoking'])
            ? UserInformationTranslator::translate('smoking', $userInformation['smoking'])
            : null,
            ! empty($userInformation['education'])
            ? UserInformationTranslator::translate('education', $userInformation['education'])
            : null,
            ! empty($userInformation['family'])
            ? UserInformationTranslator::translate('family', $userInformation['family'])
            : null,
            ! empty($userInformation['communication'])
            ? UserInformationTranslator::translate('communication', $userInformation['communication'])
            : null,
            ...array_map(function ($pet) {
                return ! empty($pet['pet'])
                    ? UserInformationTranslator::translate('pets', $pet['pet'])
                    : null;
            }, $info['pets'] ?? $info['user_pets'] ?? []),
            ! empty($userInformation['sport'])
            ? UserInformationTranslator::translate('sport', $userInformation['sport'])
            : null,
            ! empty($userInformation['love_language'])
            ? UserInformationTranslator::translate('love_language', $userInformation['love_language'])
            : null,
            ! empty($userInformation['food'])
            ? UserInformationTranslator::translate('food', $userInformation['food'])
            : null,
            ! empty($userInformation['social_network'])
            ? UserInformationTranslator::translate('social_network', $userInformation['social_network'])
            : null,
            ! empty($userInformation['sleep'])
            ? UserInformationTranslator::translate('sleep', $userInformation['sleep'])
            : null,
            ! empty($userInformation['family_status'])
            ? UserInformationTranslator::translate(
                'family_statuses',
                $userInformation['family_status'],
                in_array($gender, $this->maleGenders) ? 'male' : 'female'
            )
            : null,
            $info['final_preference']['preference'] ??
            $info['user_relationship_preferences'][0]['preference']['preference'] ?? null,
        ];

        return [
            'id' => $info['id'],
            'name' => $info['name'],
            'bio' => $userInformation['bio'] ?? null,
            'educational_institution' => $userInformation['educational_institution'] ?? null,
            'role' => $userInformation['role'] ?? null,
            'residence' => $info['city']['formatted_address'] ?? null,
            'company' => $userInformation['company'] ?? null,
            'gender' => ($userSettings['show_my_gender'] ?? false) && ! empty($info['gender'])
                ? UserInformationTranslator::translate('genders', $info['gender'])
                : null,
            'age' => $withQueryRaw['age'] ? (int) $withQueryRaw['age'] : null,
            'info' => array_values(array_filter($infoItems)),
            'distance' => $withQueryRaw['distance'] !== null ? (int) $withQueryRaw['distance'] : null,
            'is_verified' => ($info['verification_request']['status'] ??
                $info['verification_requests'][0]['status'] ?? null) === 'approved',
            'images' => array_column($info['images'] ?? $info['user_image'] ?? [], 'image'),
            'interests' => array_map(function ($interest) {
                return $interest['interest']['name'];
            }, $info['interests'] ?? $info['user_interest'] ?? []),
            'gifts' => array_map(function ($gift) {
                return $gift['gift']['image'];
            }, $info['received_gifts'] ?? []),
            'gifts_count' => $info['gifts_count'],
            'feedbacks_count' => $info['feedbacks_count'],
        ];
    }

    /**
     * Получить полную информацию о собственном аккаунте пользователя
     */
    public function getAccountInformation(string $userId): array
    {
        $today = Carbon::today();

        $user = Secondaryuser::with([
            'sentReactions' => function ($query) use ($today) {
                $query->where('type', 'like')
                    ->whereDate('date', $today);
            },
            'interests.interest',
            'images',
            'preferences',
            'userSettings',
            'pets',
            'verificationRequest',
            'finalPreference',
            'city',
            'userInformation',
        ])->findOrFail($userId);

        $user = $user->toArray();

        return [
            'images' => collect($user['images'] ?? [])->map(function ($image) {
                return [
                    'id' => $image['id'],
                    'image' => $image['image'],
                ];
            }),
            'information' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'age' => $user['age'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'birth_date' => $user['birth_date'],
                'registration_screen' => $user['registration_screen'],
                'registration_date' => $user['registration_date'],
                'show_my_gender' => $user['user_settings']['show_my_gender'] ?? null,
                'username' => $user['username'],
                'show_me' => collect($user['preferences'] ?? [])->pluck('gender')->toArray(),
                'residence' => $user['city']['formatted_address'] ?? null,
                'bio' => $user['user_information']['bio'] ?? null,
                'gender' => ! empty($user['gender']) ? [
                    'key' => $user['gender'],
                    'translation_ru' => UserInformationTranslator::translate('genders', $user['gender']),
                ] : null,

                'sexual_orientation' => ! empty($user['sexual_orientation']) ? [
                    'key' => $user['sexual_orientation'],
                    'translation_ru' => UserInformationTranslator::translate('orientations', $user['sexual_orientation']),
                ] : null,

                'zodiac_sign' => ! empty($user['user_information']['zodiac_sign']) ? [
                    'key' => $user['user_information']['zodiac_sign'],
                    'translation_ru' => UserInformationTranslator::translate('zodiac_signs', $user['user_information']['zodiac_sign']),
                ] : null,

                'education' => ! empty($user['user_information']['education']) ? [
                    'key' => $user['user_information']['education'],
                    'translation_ru' => UserInformationTranslator::translate('education', $user['user_information']['education']),
                ] : null,

                'family' => ! empty($user['user_information']['family']) ? [
                    'key' => $user['user_information']['family'],
                    'translation_ru' => UserInformationTranslator::translate('family', $user['user_information']['family']),
                ] : null,

                'communication' => ! empty($user['user_information']['communication']) ? [
                    'key' => $user['user_information']['communication'],
                    'translation_ru' => UserInformationTranslator::translate('communication', $user['user_information']['communication']),
                ] : null,

                'love_language' => ! empty($user['user_information']['love_language']) ? [
                    'key' => $user['user_information']['love_language'],
                    'translation_ru' => UserInformationTranslator::translate('love_language', $user['user_information']['love_language']),
                ] : null,

                'alcohole' => ! empty($user['user_information']['alcohole']) ? [
                    'key' => $user['user_information']['alcohole'],
                    'translation_ru' => UserInformationTranslator::translate('alcohol', $user['user_information']['alcohole']),
                ] : null,

                'smoking' => ! empty($user['user_information']['smoking']) ? [
                    'key' => $user['user_information']['smoking'],
                    'translation_ru' => UserInformationTranslator::translate('smoking', $user['user_information']['smoking']),
                ] : null,

                'sport' => ! empty($user['user_information']['sport']) ? [
                    'key' => $user['user_information']['sport'],
                    'translation_ru' => UserInformationTranslator::translate('sport', $user['user_information']['sport']),
                ] : null,

                'food' => ! empty($user['user_information']['food']) ? [
                    'key' => $user['user_information']['food'],
                    'translation_ru' => UserInformationTranslator::translate('food', $user['user_information']['food']),
                ] : null,

                'social_network' => ! empty($user['user_information']['social_network']) ? [
                    'key' => $user['user_information']['social_network'],
                    'translation_ru' => UserInformationTranslator::translate('social_network', $user['user_information']['social_network']),
                ] : null,

                'sleep' => ! empty($user['user_information']['sleep']) ? [
                    'key' => $user['user_information']['sleep'],
                    'translation_ru' => UserInformationTranslator::translate('sleep', $user['user_information']['sleep']),
                ] : null,
                'educational_institution' => $user['user_information']['educational_institution'] ?? null,
                'family_status' => ! empty($user['user_information']['family_status']) ? [
                    'key' => $user['user_information']['family_status'],
                    'translation_ru' => UserInformationTranslator::translate(
                        'family_statuses',
                        $user['user_information']['family_status'],
                        $user['gender']
                    ),
                ] : null,
                'pets' => collect($user['pets'] ?? [])->map(function ($pet) {
                    return [
                        'key' => $pet['pet'],
                        'translation_ru' => UserInformationTranslator::translate('pets', $pet['pet']),
                    ];
                }),
                'interests' => collect($user['interests'] ?? [])->map(function ($userInterest) {
                    return [
                        'id' => $userInterest['interest']['id'],
                        'name' => $userInterest['interest']['name'],
                    ];
                }),
                'relationship_preference' => ! empty($user['final_preference']) ? [
                    'id' => $user['final_preference']['id'],
                    'preference' => $user['final_preference']['preference'],
                ] : null,
                'role' => $user['user_information']['role'] ?? null,
                'company' => $user['user_information']['company'] ?? null,
                'superlikes' => $this->getUserSuperlikes($userId),
                'superbooms' => $user['user_information']['superbooms'] ?? null,
                ...(in_array($user['gender'], $this->maleGenders) ?
                    ['likes' => 30 - count($user['sent_reactions'] ?? [])] :
                    []
                ),
                'show_distance_from_me' => $user['user_settings']['show_distance_from_me'] ?? null,
                'show_my_age' => $user['user_settings']['show_my_age'] ?? null,
                'show_my_orientation' => $user['user_settings']['show_my_orientation'] ?? null,
                'is_verified' => ($user['verification_request']['status'] ?? null) === 'approved',
            ],
        ];
    }

    /**
     * Обновить информацию пользователя
     *
     * @throws Exception
     */
    public function updateUserInformation(string $userId, array $data): bool
    {
        try {
            DB::beginTransaction();

            $user = Secondaryuser::findOrFail($userId);

            $user->update($this->prepareUserData($data));

            $user->userInformation()->updateOrCreate(
                ['user_id' => $user->id],
                $this->prepareUserInformationData($data)
            );

            if (isset($data['show_my_gender']) || isset($data['show_my_orientation'])) {
                $user->settings()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'show_my_gender' => $data['show_my_gender'] ?? false,
                        'show_my_orientation' => $data['show_my_orientation'] ?? false,
                    ]
                );
            }

            if (isset($data['relationship_preference_id'])) {
                $user->relationshipPreference()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['preference_id' => $data['relationship_preference_id']]
                );
            }

            if (isset($data['interests'])) {
                $user->interests()->delete();
                $interests = array_map(fn ($id) => ['interest_id' => $id], $data['interests']);
                $user->interests()->createMany($interests);
            }

            if (isset($data['show_me'])) {
                $user->preferences()->delete();
                $preferences = array_map(fn ($gender) => ['gender' => $gender], $data['show_me']);
                $user->preferences()->createMany($preferences);
            }

            if (isset($data['pets'])) {
                $this->updateUserPets($user, $data['pets']);
            }

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ошибка обновления данных пользователя', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Обновить питомцев пользователя
     */
    private function updateUserPets(Secondaryuser $user, array $pets): void
    {

        $user->pets()->delete();

        if (! empty($pets)) {
            $petsData = array_map(fn ($pet) => ['pet' => $pet], $pets);
            $user->pets()->createMany($petsData);
        }
    }

    /**
     * Подготовить данные для основной таблицы пользователей
     */
    private function prepareUserData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'email',
            'birth_date',
            'gender',
            'registration_screen',
        ]));
    }

    /**
     * Подготовить данные для таблицы дополнительной информации пользователя
     */
    private function prepareUserInformationData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'bio',
            'sexual_orientation',
            'zodiac_sign',
            'education',
            'educational_institution',
            'family_status',
            'family',
            'communication',
            'love_language',
            'alcohole',
            'smoking',
            'sport',
            'food',
            'social_network',
            'sleep',
            'role',
            'company',
        ]));
    }

    public function getUserLikes(Secondaryuser $user, ?string $filter = null, ?LikeSettings $userSettings = null): \Illuminate\Support\Collection
    {
        // координты по умолчанию если у юзера нет местоположения
        $userLat = $user->lat ?? 0;
        $userLong = $user->long ?? 0;

        // Параметры для фильтров (точно как в Node.js)
        $hasUserSettings = $userSettings ? 1 : 0;
        $filterValue = $filter ?? '';
        $userSettingsRadius = $userSettings->radius ?? 30;
        $userSettingsAgeRange = $userSettings ? explode('-', $userSettings->age_range) : [18, 100];
        $userSettingsVerified = $userSettings && $userSettings->verified ? 1 : 0;
        $userSettingsHasInfo = $userSettings && $userSettings->has_info ? 1 : 0;
        $userSettingsMinPhotoCount = $userSettings->min_photo_count ?? 0;

        // Чистый SQL запрос точно как в оригинальном Node.js (адаптированный под MySQL)
        $results = DB::select("
            WITH users_near AS (
                SELECT u.id FROM users u
                WHERE (6371 * acos(
                    cos(radians(?)) * cos(radians(COALESCE(u.lat, 0))) *
                    cos(radians(COALESCE(u.long, 0)) - radians(?)) +
                    sin(radians(?)) * sin(radians(COALESCE(u.lat, 0)))
                )) <= 30
            ),
            users_in_radius AS (
                SELECT u.id FROM users u
                WHERE (6371 * acos(
                    cos(radians(?)) * cos(radians(COALESCE(u.lat, 0))) *
                    cos(radians(COALESCE(u.long, 0)) - radians(?)) +
                    sin(radians(?)) * sin(radians(COALESCE(u.lat, 0)))
                )) <= ?
            ),
            my_matches AS (
                SELECT ure.user_id FROM user_reactions ure
                LEFT JOIN user_reactions ur ON ur.reactor_id = ure.user_id
                    AND ur.user_id = ? AND ure.reactor_id = ?
                WHERE ure.user_id != ? AND ure.type != 'dislike' AND ur.type != 'dislike'
            ),
            users_liked_me AS (
                SELECT reactor_id, date FROM user_reactions
                WHERE user_id = ? AND type IN ('like', 'superlike')
            ),
            users_superliked_me AS (
                SELECT reactor_id FROM user_reactions
                WHERE user_id = ? AND type = 'superlike'
            ),
            my_dislikes AS (
                SELECT user_id FROM user_reactions
                WHERE reactor_id = ? AND type = 'dislike'
            ),
            my_like_preferences AS (
                SELECT gender FROM like_preferences
                WHERE user_id = ?
            )

            SELECT u.id,
                u.name,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) as image,
                CAST(
                    IF(
                        (SELECT COUNT(*) FROM bought_subscriptions bs
                         JOIN transactions t ON t.id = bs.transaction_id
                         WHERE t.user_id = u.id AND bs.due_date > NOW()) > 0
                         AND COALESCE(us.show_my_age, 1) = 0,
                        NULL,
                        u.age
                    ) AS CHAR
                ) as age,
                CAST(
                    IF(
                        (SELECT COUNT(*) FROM bought_subscriptions bs
                         JOIN transactions t ON t.id = bs.transaction_id
                         WHERE t.user_id = u.id AND bs.due_date > NOW()) > 0
                         AND COALESCE(us.show_distance_from_me, 1) = 0,
                        NULL,
                        ROUND((6371 * acos(
                            cos(radians(?)) * cos(radians(COALESCE(u.lat, 0))) *
                            cos(radians(COALESCE(u.long, 0)) - radians(?)) +
                            sin(radians(?)) * sin(radians(COALESCE(u.lat, 0)))
                        )), 0)
                    ) AS CHAR
                ) as distance,
                CAST((u.id IN (SELECT * FROM users_superliked_me)) AS CHAR) as superliked_me,
                CAST(IF(COALESCE(us.status_online, 1), u.is_online, 0) AS CHAR) as is_online,
                ulm.date as like_date
            FROM users u
            LEFT JOIN user_settings us ON us.user_id = u.id
            LEFT JOIN user_information ui ON ui.user_id = u.id
            JOIN users_liked_me ulm ON u.id = ulm.reactor_id
            WHERE u.id IN (SELECT reactor_id FROM users_liked_me)
                AND u.id NOT IN (SELECT * FROM my_matches)
                AND u.id NOT IN (SELECT * FROM my_dislikes)
                AND IF(
                    ? = 1,
                    u.gender IN (SELECT * FROM my_like_preferences)
                    AND u.id IN (SELECT * FROM users_in_radius)
                    AND (u.age BETWEEN ? AND ?)
                    AND IF(? = 1, (SELECT status FROM verification_requests WHERE user_id = u.id AND status = 'approved') IS NOT NULL, 1)
                    AND IF(? = 1, ui.bio IS NOT NULL, 1)
                    AND (SELECT COUNT(*) FROM user_images WHERE user_id = u.id) >= ?,
                    IF(? = 'by_distance', u.id IN (SELECT * FROM users_near),
                       IF(? = 'by_verification_status', (SELECT status FROM verification_requests WHERE user_id = u.id AND status = 'approved') IS NOT NULL,
                          IF(? = 'by_information', ui.bio IS NOT NULL, 1)))
                )
            ORDER BY ulm.date DESC
        ", [
            // users_near CTE (3 параметра)
            $userLat, $userLong, $userLat,
            // users_in_radius CTE (4 параметра)
            $userLat, $userLong, $userLat, $userSettingsRadius,
            // my_matches CTE (3 параметра)
            $user->id, $user->id, $user->id,
            // users_liked_me CTE (1 параметр)
            $user->id,
            // users_superliked_me CTE (1 параметр)
            $user->id,
            // my_dislikes CTE (1 параметр)
            $user->id,
            // my_like_preferences CTE (1 параметр)
            $user->id,
            // distance calculation (3 параметра)
            $userLat, $userLong, $userLat,
            // main WHERE conditions (9 параметров)
            $hasUserSettings,
            $userSettingsAgeRange[0], $userSettingsAgeRange[1],
            $userSettingsVerified,
            $userSettingsHasInfo,
            $userSettingsMinPhotoCount,
            $filterValue, $filterValue, $filterValue,
        ]);

        // Преобразуем результаты точно как в Node.js
        return collect($results)->map(function ($user) {
            return (object) [
                'id' => $user->id,
                'name' => $user->name,
                'image' => $user->image,
                'age' => $user->age !== null ? (int) $user->age : null,
                'distance' => $user->distance !== null ? (int) $user->distance : null,
                'superliked_me' => (bool) (int) $user->superliked_me,
                'is_online' => (bool) (int) $user->is_online,
                'like_date' => $user->like_date,
            ];
        });
    }

    public function getFilterSettings(string $user_id): array
    {
        $user = Secondaryuser::with(['userSettings', 'userPreferences'])
            ->find($user_id);

        $filterCities = $user->userSettings->filter_cities;
        $city = null;
        if ($filterCities) {
            $cities = json_decode($filterCities, true);
            $city = is_array($cities) && ! empty($cities) ? $cities[0] : null;
        }

        return [
            'is_global_search' => $user->userSettings->is_global_search,
            'age_range' => implode('-', $user->userSettings->age_range ?? []),
            'search_radius' => $user->userSettings->search_radius,
            'city' => $city,
            'show_me' => $user->userPreferences
                ->map(function ($pref) {
                    return [
                        'key' => $pref->gender,
                        'translation_ru' => trans("genders.{$pref->gender}"),
                    ];
                })
                ->filter(fn ($item) => ! empty($item['translation_ru']))
                ->values()
                ->toArray(),
        ];
    }

    /**
     * @return string[]
     *
     * @throws \Throwable
     */
    public function updateFilterSettings(string $user_id, array $data): array
    {
        return DB::transaction(function () use ($user_id, $data) {
            // Обновляем основные настройки
            UserSettings::updateOrCreate([
                'user_id' => $user_id,
            ], [
                'is_global_search' => $data['is_global_search'] ?? null,
                'search_radius' => $data['search_radius'] ?? null,
                'age_range' => $data['age_range'] ?? null,
                'filter_cities' => isset($data['city']) ? json_encode([$data['city']]) : null,
            ]);

            // Обновляем предпочтения по полу, если переданы
            if (isset($data['show_me'])) {
                UserPreference::where('user_id', $user_id)->delete();

                $preferences = array_map(function ($gender) use ($user_id) {
                    return [
                        'user_id' => $user_id,
                        'gender' => $gender,
                    ];
                }, $data['show_me']);

                UserPreference::insert($preferences);
            }

            return ['message' => 'Data updated successfully'];
        });
    }

    /**
     * Обновить информацию пользователя при регистрации
     *
     * @return string[]
     *
     * @throws \Throwable
     */
    public function updateUserInfoRegistration(array $data, string $userId): array
    {

        try {
            DB::beginTransaction();

            $user = Secondaryuser::findOrFail($userId);

            if (isset($data['birth_date'])) {
                $birthDate = Carbon::parse($data['birth_date']);
                $age = $birthDate->diffInYears(Carbon::now());

                if ($age < 18) {
                    throw new Exception('Wrong params', 4064);
                }
            }

            $updateData = [];
            $fields = ['name', 'gender', 'email', 'registration_screen', 'username', 'sexual_orientation'];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($data['birth_date'])) {
                $birthDate = Carbon::createFromFormat('Y-m-d', $data['birth_date']);
                $updateData['birth_date'] = $birthDate->format('Y-m-d');
                $updateData['age'] = $birthDate->diffInYears(Carbon::now());
            }

            if (! empty($updateData)) {
                $user->update($updateData);
            }

            $userInfoData = $this->prepareUserInformationData($data);

            if (! empty($userInfoData)) {
                $user->userInformation()->updateOrCreate(
                    ['user_id' => $userId],
                    ['family_status' => $data['family_status']]
                );
            }

            if (isset($data['interests'])) {
                $user->interests()->delete();
                $interests = array_map(fn ($id) => ['interest_id' => $id], $data['interests']);
                $user->interests()->createMany($interests);
            }

            if (isset($data['relationship_preference_id'])) {
                $user->relationshipPreference()->updateOrCreate(
                    ['user_id' => $userId],
                    ['preference_id' => $data['relationship_preference_id']]
                );
            }

            if (isset($data['show_my_orientation']) || isset($data['show_my_gender'])) {
                $settingsData = [];
                if (isset($data['show_my_orientation'])) {
                    $settingsData['show_my_orientation'] = $data['show_my_orientation'];
                }
                if (isset($data['show_my_gender'])) {
                    $settingsData['show_my_gender'] = $data['show_my_gender'];
                }

                $user->settings()->updateOrCreate(
                    ['user_id' => $userId],
                    $settingsData
                );
            }

            if (is_array($data['show_me']) && ! empty($data['show_me'])) {

                $user->preferences()->delete();

                $preferences = [];
                foreach ($data['show_me'] as $gender) {
                    $preferences[] = ['gender' => $gender, 'user_id' => $userId];
                }

                $user->preferences()->createMany($preferences);
            }

            DB::commit();

            return ['message' => 'Request has ended successfully'];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('updateUserInfoRegistration error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Проверяет существование email
     *
     * @throws Exception
     */
    public function getEmailExistenceStatus(string $email): bool
    {
        try {
            return Secondaryuser::where('email', $email)->exists();
        } catch (Exception $e) {
            throw new Exception('Ошибка при проверке существования email: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get user packages and limits
     *
     * @throws Exception
     */
    public function getUserPackages(string $userId): array
    {
        try {
            $user = Secondaryuser::with([
                'userInformation:user_id,superboom_due_date,superbooms,superlikes',
                'activeSubscription.package.subscription:id,type',
            ])
                ->find($userId, ['id']);

            if (! $user) {
                throw new Exception('User not found', 404);
            }

            $subscriptionData = null;
            if ($user->activeSubscription) {
                $subscriptionData = [
                    'type' => $user->activeSubscription->package->subscription->type ?? null,
                    'due_date' => $user->activeSubscription->due_date->format('Y-m-d H:i:s'),
                ];
            }

            return [
                'subscription_package' => $subscriptionData,
                'superboom_due_date' => $user->userInformation->superboom_due_date ?? null,
                'superbooms' => $user->userInformation->superbooms ?? 0,
                'superlikes' => $user->userInformation->superlikes ?? 0,
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to get user packages: '.$e->getMessage(), 500);
        }
    }

    /**
     * Save user coordinates and update location data
     * Реплицирует точную логику из Node.js проекта метода saveCoordinates
     *
     * @throws Exception
     */
    public function saveCoordinates(Secondaryuser $user, array $data): array
    {
        try {
            DB::beginTransaction();

            // Получаем начало дня последней проверки как в node js
            $startOfLastCheckDay = $user->last_check ? Carbon::parse($user->last_check)->startOfDay() : null;
            $now = Carbon::now();

            $userInfo = $user->userInformation;
            $currentStreak = $userInfo ? ($userInfo->streak ?? 0) : 0;

            // Вычисляем новый streak
            $streakIncrement = 0;
            if ($startOfLastCheckDay) {
                $dayAfterLastCheck = $startOfLastCheckDay->copy()->addDay();
                $twoDaysAfterLastCheck = $startOfLastCheckDay->copy()->addDays(2);

                if (($now->greaterThan($dayAfterLastCheck) && $now->lessThan($twoDaysAfterLastCheck)) || $currentStreak == 0) {
                    $streakIncrement = 1;
                }
            } else {
                $streakIncrement = 1;
            }

            // Обновляем пользователя как в node js
            $user->update([
                'lat' => $data['lat'],
                'long' => $data['long'],
                'last_check' => $now,
                'is_online' => true,
            ]);

            // Обновляем или создаем город
            $user->city()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'lat' => $data['lat'],
                    'long' => $data['long'],
                    'formatted_address' => $data['formatted_address'] ?? null,
                ]
            );

            // Обновляем или создаем user_information с инкрементом streak
            $user->userInformation()->updateOrCreate(
                ['user_id' => $user->id],
                []
            );

            // Используем оригинальный SQL запрос
            DB::statement('UPDATE user_information SET streak = streak + ? WHERE user_id = ?', [$streakIncrement, $user->id]);

            DB::commit();

            return ['message' => 'Coordinates saved successfully'];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('saveCoordinates error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getTraceAsString(),
            ]);
            throw new Exception('Failed to save coordinates: '.$e->getMessage(), 500);
        }
    }

    /**
     * @return array|null[]
     *
     * @throws Exception
     */
    public function getConnectedAccounts(string $userId): array
    {
        try {
            $google = ConnectedAccount::where('user_id', $userId)
                ->where('provider', 'google')
                ->first();

            $facebook = ConnectedAccount::where('user_id', $userId)
                ->where('provider', 'facebook')
                ->first();

            $vk = ConnectedAccount::where('user_id', $userId)
                ->where('provider', 'vkontakte')
                ->first();

            $apple = ConnectedAccount::where('user_id', $userId)
                ->where('provider', 'apple')
                ->first();

            $settings = UserSettings::where('user_id', $userId)
                ->select([
                    'login_with_apple',
                    'login_with_google',
                    'login_with_facebook',
                    'login_with_vk',
                ])
                ->first();

            return [
                'google' => ! $google ? null : ($settings->login_with_google ?? null),
                'facebook' => ! $facebook ? null : ($settings->login_with_facebook ?? null),
                'apple' => ! $apple ? null : ($settings->login_with_apple ?? null),
                'vk' => ! $vk ? null : ($settings->login_with_vk ?? null),
            ];

        } catch (Exception $e) {
            Log::error('Error fetching connected accounts for user: '.$userId, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Failed to get user packages: '.$e->getMessage(), 500);
        }
    }

    public function getBlockedContacts(string $userId, ?string $query = null): array
    {
        try {

            $blockedContacts = BlockedContacts::where('user_id', $userId)
                ->when($query, function ($queryBuilder) use ($query) {
                    $queryBuilder->where('phone', 'like', "%{$query}%");
                })
                ->get();

            return $blockedContacts->toArray();

        } catch (Exception $e) {
            Log::error('Error fetching blocked contacts', [
                'user_id' => $userId,
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * @return string[]
     *
     * @throws \Throwable
     */
    public function createBlockedContact(string $userId, array $data): array
    {
        DB::beginTransaction();

        try {

            $existingContact = BlockedContacts::where('user_id', $userId)
                ->where('phone', $data['phone'])
                ->first();

            if ($existingContact) {
                throw new Exception('Blocked contact already exists', 406);
            }

            BlockedContacts::create([
                'user_id' => $userId,
                'phone' => $data['phone'],
                'name' => $data['name'],
                'date' => now()->toDateString(),
            ]);

            DB::commit();

            return [
                'message' => 'Data added successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Transaction failed for blocked contact', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    public function deleteBlockedContact(string $phone, string $userId): array
    {
        try {

            $blockedContact = BlockedContacts::where('user_id', $userId)
                ->where('phone', $phone)
                ->first();

            if (! $blockedContact) {
                throw new Exception('Data not exist', 404);
            }

            $blockedContact->delete();

            return [
                'message' => 'Data deleted successfully',
            ];

        } catch (Exception $e) {
            Log::error('Error deleting blocked contact', [
                'user_id' => $userId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get main user settings (original Node.js getSettings method)
     */
    public function getMainSettings(string $userId): array
    {
        // Original SQL from node js
        $user = DB::selectOne('
            SELECT 
                u.phone,
                u.email, 
                u.username,
                uc.formatted_address as residence
            FROM users u
            LEFT JOIN user_cities uc ON uc.user_id = u.id
            WHERE u.id = ?
        ', [$userId]);

        if (! $user) {
            throw new Exception('User not found', 404);
        }

        // Get user settings
        $userSettings = DB::selectOne('
            SELECT 
                search_radius,
                is_global_search,
                recommendations,
                visibility,
                show_me_on_finder
            FROM user_settings 
            WHERE user_id = ?
        ', [$userId]);

        // Handle delete queue logic (original Node.js logic)
        $timeLeftToDeleteAccount = -1;
        $isTimeDelete = null;

        $deleteQueue = DB::selectOne('
            SELECT date, is_date_delete 
            FROM in_queue_for_delete_user 
            WHERE user_id = ?
        ', [$userId]);

        if ($deleteQueue) {
            $queueDate = Carbon::parse($deleteQueue->date);
            $timeLeftToDeleteAccount = $queueDate->timestamp * 1000 - now()->timestamp * 1000;
            $isTimeDelete = (bool) $deleteQueue->is_date_delete;

            if ($isTimeDelete === false && $timeLeftToDeleteAccount <= 0) {
                $isTimeDelete = true;
                $timeLeftToDeleteAccount = 3 * 24 * 60 * 60 * 1000; // 3 days in milliseconds

                // Update delete queue
                DB::update('
                    UPDATE in_queue_for_delete_user 
                    SET date = ?, is_date_delete = ? 
                    WHERE user_id = ?
                ', [
                    now()->addMilliseconds($timeLeftToDeleteAccount)->toDateString(),
                    $isTimeDelete,
                    $userId,
                ]);

            } elseif ($isTimeDelete && $timeLeftToDeleteAccount <= 0) {
                $isTimeDelete = null;
                $timeLeftToDeleteAccount = -1;

                // Delete from queue
                DB::delete('DELETE FROM in_queue_for_delete_user WHERE user_id = ?', [$userId]);
            }
        }

        // Combine results (original response format)
        return [
            'phone' => $user->phone,
            'email' => $user->email,
            'username' => $user->username,
            'residence' => $user->residence,
            'timeLeftToDeleteAccount' => $timeLeftToDeleteAccount,
            'isTimeDelete' => $isTimeDelete,
            'search_radius' => $userSettings->search_radius ?? 999,
            'is_global_search' => (bool) ($userSettings->is_global_search ?? false),
            'recommendations' => $userSettings->recommendations ?? 'optimal',
            'visibility' => $userSettings->visibility ?? 'standard',
            'show_me_on_finder' => (bool) ($userSettings->show_me_on_finder ?? true),
        ];
    }

    /**
     * Update main user settings (original Node.js updateSettings method)
     */
    public function updateMainSettings(string $userId, array $data): array
    {
        // Original subscription check logic from Node.js
        if (isset($data['show_me_on_finder']) && $data['show_me_on_finder'] !== null) {
            $hasSubscription = DB::selectOne('
                SELECT COUNT(*) as count
                FROM users u
                LEFT JOIN transactions t ON t.user_id = u.id
                LEFT JOIN bought_subscriptions bs ON bs.transaction_id = t.id
                WHERE u.id = ? 
                AND bs.due_date > NOW()
                AND t.status = "succeeded"
            ', [$userId]);

            if (! $hasSubscription || $hasSubscription->count == 0) {
                throw new Exception('Could not change show_me_on_finder without a subscription', 406);
            }
        }

        // Original changeable settings from Node.js
        $changeableSettings = [
            'show_me_on_finder',
            'is_global_search',
            'username',
            'search_radius',
            'status_seen',
            'status_online',
            'email',
            'status_recently_active',
            'new_couples_push',
            'new_messages_push',
            'new_likes_push',
            'new_super_likes_push',
            'new_couples_email',
            'new_messages_email',
        ];

        // Prepare data for update
        $settingsData = [];
        $userData = [];

        foreach ($changeableSettings as $setting) {
            if (array_key_exists($setting, $data) && $data[$setting] !== null) {
                if ($setting === 'username') {
                    $userData['username'] = $data[$setting];
                } elseif ($setting === 'email') {
                    // Check if email changed to reset verification
                    $currentUser = DB::selectOne('SELECT email FROM users WHERE id = ?', [$userId]);
                    if ($currentUser->email !== $data[$setting]) {
                        $userData['email'] = $data[$setting];
                        $settingsData['is_email_verified'] = false;
                    }
                } else {
                    $settingsData[$setting] = $data[$setting];
                }
            }
        }

        // Update user data if needed
        if (! empty($userData)) {
            $updateFields = [];
            $updateValues = [];
            foreach ($userData as $field => $value) {
                $updateFields[] = "{$field} = ?";
                $updateValues[] = $value;
            }
            $updateValues[] = $userId;

            DB::update('UPDATE users SET '.implode(', ', $updateFields).' WHERE id = ?', $updateValues);
        }

        // Update settings data
        if (! empty($settingsData)) {
            $updateFields = [];
            $updateValues = [];
            foreach ($settingsData as $field => $value) {
                $updateFields[] = "{$field} = ?";
                $updateValues[] = $value;
            }
            $updateValues[] = $userId;

            DB::update('UPDATE user_settings SET '.implode(', ', $updateFields).' WHERE user_id = ?', $updateValues);
        }

        return [
            'message' => 'Data updated successfully',
        ];
    }

    /**
     * Get user's remaining superlikes from user information
     */
    private function getUserSuperlikes(string $userId): int
    {
        $userInfo = UserInformation::where('user_id', $userId)->first();
        
        if (!$userInfo) {
            return 0;
        }

        // Try to allocate weekly superlikes if eligible
        $userInfo->allocateWeeklySuperlikes();
        $userInfo->refresh();
        
        return $userInfo->getRemainingSuperlikes();
    }
}
