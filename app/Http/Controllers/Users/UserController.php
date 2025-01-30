<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserInformationRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Secondaryuser;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * @param UpdateUserInformationRequest $request
     * @param Secondaryuser $user
     * @OA\Put(
     *     path="/users/{user}/information",
     *     tags={"User settings"},
     *     summary="Update user information",
     *     description="Update comprehensive user profile information including personal details, preferences, and settings",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="user",
     *          in="path",
     *          required=true,
     *          description="User ID",
     *          @OA\Schema(
     *              type="string",
     *               format="uuid",
     *               example="000558ed-d557-4fc0-99da-b23dec6be0bf"
     *          )
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="User information to update (all fields are optional)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="bio",
     *                 type="string",
     *                 maxLength=500,
     *                 example="Люблю путешествовать",
     *                 description="User biography"
     *             ),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 example="male",
     *                 description="User gender"
     *             ),
     *             @OA\Property(
     *                 property="sexual_orientation",
     *                 type="string",
     *                 example="hetero",
     *                 description="User sexual orientation"
     *             ),
     *             @OA\Property(
     *                 property="birth_date",
     *                 type="string",
     *                 format="date",
     *                 example="1990-05-15",
     *                 description="User birth date (must be in the past)"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 maxLength=100,
     *                 example="user@example.com",
     *                 description="User email address"
     *             ),
     *             @OA\Property(
     *                 property="zodiac_sign",
     *                 type="string",
     *                 example="taurus",
     *                 description="Zodiac sign"
     *             ),
     *             @OA\Property(
     *                 property="education",
     *                 type="string",
     *                 example="higher",
     *                 description="Education level"
     *             ),
     *             @OA\Property(
     *                 property="educational_institution",
     *                 type="string",
     *                 maxLength=100,
     *                 example="МГУ",
     *                 description="Educational institution name"
     *             ),
     *             @OA\Property(
     *                 property="family_status",
     *                 type="string",
     *                 example="single",
     *                 description="Family status"
     *             ),
     *             @OA\Property(
     *                 property="family",
     *                 type="string",
     *                 example="want_children",
     *                 description="Attitude towards family"
     *             ),
     *             @OA\Property(
     *                 property="communication",
     *                 type="string",
     *                 example="extrovert",
     *                 description="Communication style"
     *             ),
     *             @OA\Property(
     *                 property="love_language",
     *                 type="string",
     *                 example="quality_time",
     *                 description="Love language preference"
     *             ),
     *             @OA\Property(
     *                 property="alcohole",
     *                 type="string",
     *                 example="sometimes",
     *                 description="Attitude towards alcohol"
     *             ),
     *             @OA\Property(
     *                 property="smoking",
     *                 type="string",
     *                 example="never",
     *                 description="Smoking habits"
     *             ),
     *             @OA\Property(
     *                 property="sport",
     *                 type="string",
     *                 example="active",
     *                 description="Attitude towards sports"
     *             ),
     *             @OA\Property(
     *                 property="food",
     *                 type="string",
     *                 example="vegetarian",
     *                 description="Food preferences"
     *             ),
     *             @OA\Property(
     *                 property="social_network",
     *                 type="string",
     *                 example="active",
     *                 description="Social network activity"
     *             ),
     *             @OA\Property(
     *                 property="sleep",
     *                 type="string",
     *                 example="owl",
     *                 description="Sleep schedule preference"
     *             ),
     *             @OA\Property(
     *                 property="role",
     *                 type="string",
     *                 maxLength=50,
     *                 example="Software Developer",
     *                 description="Job role/position"
     *             ),
     *             @OA\Property(
     *                 property="company",
     *                 type="string",
     *                 maxLength=50,
     *                 example="Tech Company",
     *                 description="Company name"
     *             ),
     *             @OA\Property(
     *                 property="interests",
     *                 type="array",
     *                 minItems=3,
     *                 maxItems=5,
     *                 description="User interests (minimum 3, maximum 5)",
     *                 @OA\Items(
     *                     type="integer",
     *                     example=1,
     *                     description="Interest ID"
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="relationship_preference_id",
     *                 type="integer",
     *                 example=2,
     *                 description="Relationship preference ID"
     *             ),
     *             @OA\Property(
     *                 property="show_my_gender",
     *                 type="boolean",
     *                 example=true,
     *                 description="Whether to show user's gender to others"
     *             ),
     *             @OA\Property(
     *                 property="show_my_orientation",
     *                 type="boolean",
     *                 example=false,
     *                 description="Whether to show user's orientation to others"
     *             ),
     *             @OA\Property(
     *                 property="show_me",
     *                 type="array",
     *                 description="Gender preferences for matching",
     *                 @OA\Items(
     *                     type="string",
     *                     enum={"male", "female", "mf", "mm", "ff"},
     *                     example="female"
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="registration_screen",
     *                 type="string",
     *                 maxLength=50,
     *                 nullable=true,
     *                 example="step_3",
     *                 description="Current registration screen"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Information updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Данные успешно обновлены",
     *                 description="Success message"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Ошибка валидации данных",
     *                 description="Error message"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Validation errors",
     *                 @OA\Property(
     *                     property="bio",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         example="Биография не должна превышать 500 символов"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="interests",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         example="Необходимо выбрать минимум 3 интереса"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         example="Этот email уже используется другим пользователем"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Произошла ошибка при обновлении данных",
     *                 description="Error message"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="string",
     *                 example="Database connection error",
     *                 description="Error details"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     */
    public function updateInformation(UpdateUserInformationRequest $request, Secondaryuser $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

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

            DB::commit();

            return $this->successResponse(['message' => 'Данные успешно обновлены']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ошибка обновления данных пользователя', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $data ?? null,
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );

        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareUserData(array $data): array
    {
        $userData = [
            'gender' => $data['gender'] ?? null,
            'registration_screen' => $data['registration_screen'] ?? null,
            'sexual_orientation' => $data['sexual_orientation'] ?? null,
            'email' => $data['email'] ?? null,
        ];

        if (isset($data['birth_date'])) {
            $birthDate = Carbon::parse($data['birth_date']);
            $userData['birth_date'] = $birthDate;
            $userData['age'] = Carbon::now()->diffInYears($birthDate);
        }

        return array_filter($userData);
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareUserInformationData(array $data): array
    {
        return array_filter([
            'bio' => $data['bio'] ?? null,
            'zodiac_sign' => $data['zodiac_sign'] ?? null,
            'education' => $data['education'] ?? null,
            'educational_institution' => $data['educational_institution'] ?? null,
            'family' => $data['family'] ?? null,
            'communication' => $data['communication'] ?? null,
            'love_language' => $data['love_language'] ?? null,
            'alcohole' => $data['alcohole'] ?? null,
            'smoking' => $data['smoking'] ?? null,
            'sport' => $data['sport'] ?? null,
            'food' => $data['food'] ?? null,
            'social_network' => $data['social_network'] ?? null,
            'sleep' => $data['sleep'] ?? null,
            'family_status' => $data['family_status'] ?? null,
            'role' => $data['role'] ?? null,
            'company' => $data['company'] ?? null,
        ]);
    }

}
