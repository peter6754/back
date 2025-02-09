<?php

namespace App\Services;

use App\Helpers\UserInformationTranslator;
use App\Models\Secondaryuser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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

            list($info, $withQueryRaw) = $results;

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
}
