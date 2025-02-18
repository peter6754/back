<?php

namespace App\Services;

use App\Helpers\UserInformationTranslator;
use App\Models\Secondaryuser;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Log;

class UserService
{

    private $maleGenders = [
        UserInformationTranslator::GENDER_MALE,
        UserInformationTranslator::GENDER_MM,
        UserInformationTranslator::GENDER_MF
    ];

    /**
     * @param string $id
     * @param array $viewer
     * @return array
     */
    public function getUser(string $id, array $viewer)
    {
        return $this->fetchUserData($id, $viewer);
    }

    /**
     * @param string $id
     * @param array $viewer
     * @return array
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

    /**
     * @param string $id
     * @return array
     */
    private function getUserInfo(string $id): array
    {
        $user = Secondaryuser::with([
            'finalPreference:preference',
            'interests.interest:id,name',
            'images' => fn($query) => $query->select('user_id', 'image')->take(4),
            'verificationRequest:user_id,status',
            'receivedGifts' => fn($query) => $query->with('gift:id,image')
                ->whereHas('transaction', fn($q) => $q->where('status', 'succeeded'))
                ->take(2),
            'settings:user_id,show_my_orientation,show_my_gender,show_my_age,show_distance_from_me',
            'city:user_id,formatted_address',
            'userInformation',
            'pets' => fn($query) => $query->select('user_id', 'pet'),
        ])->withCount([
            'receivedGifts as gifts_count' => fn($query) => $query->whereHas('transaction', fn($q) => $q->where('status', 'succeeded')),
            'feedbacks as feedbacks_count'
        ])->findOrFail($id);

        return $user->toArray();
    }

    /**
     * @param string $id
     * @param array $viewer
     * @return array
     */
    private function getUserAgeAndDistance(string $id, array $viewer): array
    {
        $result = DB::selectOne("
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
        ", [$viewer['lat'], $viewer['long'], $id]);

        return (array)$result;
    }

    /**
     * @param array $info
     * @param array $withQueryRaw
     * @return array
     */
    private function formatResponse(array $info, array $withQueryRaw): array
    {
        $userSettings = $info['settings'] ?? $info['user_settings'] ?? [];
        $userInformation = $info['user_information'] ?? [];
        $gender = $info['gender'] ?? null;

        $infoItems = [
                $userSettings['show_my_orientation'] ?? false
                ? UserInformationTranslator::translate('orientations', $info['sexual_orientation'])
                : null,
            !empty($userInformation['zodiac_sign'])
                ? UserInformationTranslator::translate('zodiac_signs', $userInformation['zodiac_sign'])
                : null,
            !empty($userInformation['alcohole'])
                ? UserInformationTranslator::translate('alcohol', $userInformation['alcohole'])
                : null,
            !empty($userInformation['smoking'])
                ? UserInformationTranslator::translate('smoking', $userInformation['smoking'])
                : null,
            !empty($userInformation['education'])
                ? UserInformationTranslator::translate('education', $userInformation['education'])
                : null,
            !empty($userInformation['family'])
                ? UserInformationTranslator::translate('family', $userInformation['family'])
                : null,
            !empty($userInformation['communication'])
                ? UserInformationTranslator::translate('communication', $userInformation['communication'])
                : null,
            ...array_map(function ($pet) {
                return isset($pet['pet']) && $pet['pet']
                    ? UserInformationTranslator::translate('pets', $pet['pet'])
                    : null;
            }, $info['pets'] ?? $info['user_pets'] ?? []),
            !empty($userInformation['sport'])
                ? UserInformationTranslator::translate('sport', $userInformation['sport'])
                : null,
            !empty($userInformation['love_language'])
                ? UserInformationTranslator::translate('love_language', $userInformation['love_language'])
                : null,
            !empty($userInformation['food'])
                ? UserInformationTranslator::translate('food', $userInformation['food'])
                : null,
            !empty($userInformation['social_network'])
                ? UserInformationTranslator::translate('social_network', $userInformation['social_network'])
                : null,
            !empty($userInformation['sleep'])
                ? UserInformationTranslator::translate('sleep', $userInformation['sleep'])
                : null,
            !empty($userInformation['family_status'])
                ? UserInformationTranslator::translate(
                'family_statuses',
                $userInformation['family_status'],
                in_array($gender, $this->maleGenders) ? 'male' : 'female'
            )
                : null,
            $info['final_preference']['preference'] ??
                $info['user_relationship_preferences'][0]['preference']['preference'] ?? null
        ];

        return [
            'id' => $info['id'],
            'name' => $info['name'],
            'bio' => $userInformation['bio'] ?? null,
            'educational_institution' => $userInformation['educational_institution'] ?? null,
            'role' => $userInformation['role'] ?? null,
            'residence' => $info['city']['formatted_address'] ?? null,
            'company' => $userInformation['company'] ?? null,
            'gender' => $userSettings['show_my_gender'] ?? false
                ? UserInformationTranslator::translate('genders', $info['gender'])
                : null,
            'age' => $withQueryRaw['age'] ? (int)$withQueryRaw['age'] : null,
            'info' => array_values(array_filter($infoItems)),
            'distance' => $withQueryRaw['distance'] ? (int)$withQueryRaw['distance'] : null,
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
     *
     * @param string $userId
     * @return array
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
            'userInformation'
        ])->findOrFail($userId);

        $user = $user->toArray();
        return [
            'images' => collect($user['images'] ?? [])->map(function ($image) {
                return [
                    'id' => $image['id'],
                    'image' => $image['image']
                ];
            }),
            'pets' => $user['pets'] ? [UserInformationTranslator::translate('pets', $user['pets']['pet'])] : [],
            'interests' => collect($user['interests'] ?? [])->map(function ($userInterest) {
                return [
                    'id' => $userInterest['interest']['id'],
                    'name' => $userInterest['interest']['name']
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
                'gender' => UserInformationTranslator::translate('genders', $user['gender']),
                'sexual_orientation' => UserInformationTranslator::translate('orientations', $user['sexual_orientation']),
                'zodiac_sign' => UserInformationTranslator::translate('zodiac_signs', $user['user_information']['zodiac_sign'] ?? ''),
                'education' => UserInformationTranslator::translate('education', $user['user_information']['education'] ?? ''),
                'family' => UserInformationTranslator::translate('family', $user['user_information']['family'] ?? ''),
                'communication' => UserInformationTranslator::translate('communication', $user['user_information']['communication'] ?? ''),
                'love_language' => UserInformationTranslator::translate('love_language', $user['user_information']['love_language'] ?? ''),
                'alcohole' => UserInformationTranslator::translate('alcohol', $user['user_information']['alcohole'] ?? ''),
                'smoking' => UserInformationTranslator::translate('smoking', $user['user_information']['smoking'] ?? ''),
                'sport' => UserInformationTranslator::translate('sport', $user['user_information']['sport'] ?? ''),
                'food' => UserInformationTranslator::translate('food', $user['user_information']['food'] ?? ''),
                'social_network' => UserInformationTranslator::translate('social_network', $user['user_information']['social_network'] ?? ''),
                'sleep' => UserInformationTranslator::translate('sleep', $user['user_information']['sleep'] ?? ''),
                'educational_institution' => $user['user_information']['educational_institution'] ?? null,
                'family_status' => !empty($user['user_information']['family_status']) ? [
                    'key' => $user['user_information']['family_status'],
                    'translation_ru' => UserInformationTranslator::translate(
                        'family_statuses',
                        $user['user_information']['family_status'],
                        $user['gender']
                    )
                ] : null,
                'relationship_preference' => $user['final_preference']['preference'] ?? null,
                'role' => $user['user_information']['role'] ?? null,
                'company' => $user['user_information']['company'] ?? null,
                'superlikes' => $user['user_information']['superlikes'] ?? null,
                'superbooms' => $user['user_information']['superbooms'] ?? null,
                ...(in_array($user['gender'], $this->maleGenders) ?
                    ['likes' => 30 - count($user['sent_reactions'] ?? [])] :
                    []
                ),
                'show_distance_from_me' => $user['user_settings']['show_distance_from_me'] ?? null,
                'show_my_age' => $user['user_settings']['show_my_age'] ?? null,
                'show_my_orientation' => $user['user_settings']['show_my_orientation'] ?? null,
                'is_verified' => ($user['verification_request']['status'] ?? null) === 'approved'
            ]
        ];
    }

    /**
     * Обновить информацию пользователя
     *
     * @param string $userId
     * @param array $data
     * @return bool
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
                $interests = array_map(fn($id) => ['interest_id' => $id], $data['interests']);
                $user->interests()->createMany($interests);
            }

            if (isset($data['show_me'])) {
                $user->preferences()->delete();
                $preferences = array_map(fn($gender) => ['gender' => $gender], $data['show_me']);
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
     *
     * @param Secondaryuser $user
     * @param array $pets
     * @return void
     */
    private function updateUserPets(Secondaryuser $user, array $pets): void
    {

        $user->pets()->delete();

        if (!empty($pets)) {
            $petsData = array_map(fn($pet) => ['pet' => $pet], $pets);
            $user->pets()->createMany($petsData);
        }
    }

    /**
     * Подготовить данные для основной таблицы пользователей
     *
     * @param array $data
     * @return array
     */
    private function prepareUserData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'email',
            'birth_date',
            'gender',
            'registration_screen'
        ]));
    }

    /**
     * Подготовить данные для таблицы дополнительной информации пользователя
     *
     * @param array $data
     * @return array
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
            'company'
        ]));
    }
}
