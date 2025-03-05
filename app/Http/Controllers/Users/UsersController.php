<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetUserLikesRequest;
use App\Http\Requests\updateUserInfoRegistrationRequest;
use App\Http\Requests\UpdateUserInformationRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\BoughtSubscriptions;
use App\Models\LikeSettings;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class UsersController
 */
class UsersController extends Controller
{
    /**
     * Include API response trait
     */
    use ApiResponseTrait;

    private UserService $userService;

    /**
     * UsersController constructor.
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Get(
     *     path="/users/profile",
     *     tags={"User Settings"},
     *     summary="Get my profile",
     *     description="Returns account information",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "images": {
     *                          {
     *                              "id": 63351,
     *                              "image": "11,f3dc14b810ca"
     *                          },
     *                          {
     *                              "id": 63350,
     *                              "image": "4,f3dbd03bce08"
     *                          }
     *                      },
     *                      "information": {
     *                          "id": "3ade5db5-fe5e-4f8c-a3bf-94d1d6ab1043",
     *                          "name": "Oleg",
     *                          "age": 33,
     *                          "email": "test@test.ru",
     *                          "phone": "+11111111111",
     *                          "birth_date": "1994-11-28T22:00:00.000000Z",
     *                          "registration_screen": null,
     *                          "registration_date": "2024-08-15T13:29:22.000000Z",
     *                          "show_my_gender": true,
     *                          "username": null,
     *                          "show_me": {
     *                              "female"
     *                          },
     *                          "residence": "Тираспол",
     *                          "bio": "Люблю путешествовать и читать книги",
     *                          "gender": {
     *                               "key": "male",
     *                               "translation_ru": "Мужчина"
     *                           },
     *                          "sexual_orientation": {
     *                                "key": "hetero",
     *                                "translation_ru": "Гетеро"
     *                            },
     *                          "zodiac_sign": {
     *                                "key": "taurus",
     *                                "translation_ru": "Телец"
     *                            },
     *                          "education": "",
     *                          "family": "",
     *                          "communication": "",
     *                          "love_language": "",
     *                          "alcohole": {
     *                                "key": "on_holidays",
     *                                "translation_ru": "Пью по праздникам"
     *                            },
     *                          "smoking": {
     *                                "key": "i_smoke",
     *                                "translation_ru": "Курю"
     *                            },
     *                          "sport": {
     *                                "key": "train_sometimes",
     *                                "translation_ru": "Иногда тренируюсь"
     *                            },
     *                          "food": {
     *                                "key": "everything",
     *                                "translation_ru": "Ем всё"
     *                            },
     *                          "social_network": {
     *                                "key": "sometimes_im_on",
     *                                "translation_ru": "Иногда захожу в соцсети"
     *                            },
     *                          "sleep": {
     *                                "key": "owl",
     *                                "translation_ru": "Я сова"
     *                            },
     *                          "educational_institution": null,
     *                          "family_status": {
     *                              "key": "married",
     *                              "translation_ru": "Женат"
     *                          },
     *                          "pets": {
     *                               "Кошка"
     *                           },
     *                           "interests": {
     *                                {
     *                                    "id": 2,
     *                                    "name": "Кофе"
     *                                },
     *                                {
     *                                    "id": 35,
     *                                    "name": "Ходьба"
     *
     *                                },
     *                                {
     *                                    "id": 87,
     *                                    "name": "Настольные игры"
     *                                }
     *                            },
     *                          "relationship_preference": "Новых друзей",
     *                          "role": null,
     *                          "company": null,
     *                          "superlikes": 257,
     *                          "superbooms": 5,
     *                          "likes": 30,
     *                          "show_distance_from_me": true,
     *                          "show_my_age": true,
     *                          "show_my_orientation": false,
     *                          "is_verified": false
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized")
     *     )
     * )
     *
     * @throws Exception
     */
    public function getAccountInformation(Request $request): JsonResponse
    {
        $user = $request->user()->toArray();
        try {
            return $this->successResponse(
                $this->userService->getAccountInformation($user['id'])
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/users/profile",
     *     tags={"User Settings"},
     *     summary="Update user information",
     *     description="Update comprehensive user profile information including personal details, preferences, pets, and settings",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *         description="User information to update (all fields are optional)",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
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
     *
     *                 @OA\Items(
     *                     type="integer",
     *                     example=1,
     *                     description="Interest ID"
     *                 )
     *             ),
     *
     *             @OA\Property(
     *                 property="pets",
     *                 type="array",
     *                 maxItems=10,
     *                 description="User pets (maximum 10)",
     *
     *                 @OA\Items(
     *                     type="string",
     *                     enum={"dog", "cat", "reptile", "amphibian", "bird", "fish", "turtle", "rabbit", "hamster", "i_want", "dont_have"},
     *                     example="dog",
     *                     description="Pet type"
     *                 )
     *             ),
     *
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
     *
     *                 @OA\Items(
     *                     type="string",
     *                     enum={"male", "female", "m_f", "m_m", "f_f"},
     *                     example="female"
     *                 )
     *             ),
     *
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
     *
     *     @OA\Response(
     *         response=200,
     *         description="Information updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Данные успешно обновлены",
     *                 description="Success message"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
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
     *
     *                     @OA\Items(
     *                         type="string",
     *                         example="Биография не должна превышать 500 символов"
     *                     )
     *                 ),
     *
     *                 @OA\Property(
     *                     property="interests",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="string",
     *                         example="Необходимо выбрать минимум 3 интереса"
     *                     )
     *                 ),
     *
     *                 @OA\Property(
     *                     property="pets",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="string",
     *                         example="Можно выбрать максимум 10 питомцев"
     *                     )
     *                 ),
     *
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="string",
     *                         example="Этот email уже используется другим пользователем"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
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
     *
     *     @OA\Response(
     *
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     */
    public function updateAccountInformation(UpdateUserInformationRequest $request): JsonResponse
    {
        $user = $request->user()->toArray();
        try {
            $data = $request->validated();

            $this->userService->updateUserInformation($user['id'], $data);

            return $this->successResponse(['message' => 'Данные успешно обновлены']);

        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/users/infoRegistration",
     *     tags={"User Registration"},
     *     summary="Update user information during registration",
     *     description="Update user profile information, preferences, and settings during registration process",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={},
     *
     *             @OA\Property(property="name", type="string", example="John", maxLength=255),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 example="male",
     *                 enum={"male", "female", "m_f", "m_m", "f_f"}
     *             ),
     *             @OA\Property(
     *                 property="sexual_orientation",
     *                 type="string",
     *                 example="hetero",
     *                 enum={"hetero", "gay", "lesbian", "bisexual", "asexual", "not_decided"}
     *             ),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="registration_screen", type="integer", example=1),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="username", type="string", example="johndoe", maxLength=255),
     *             @OA\Property(
     *                 property="family_status",
     *                 type="string",
     *                 example="married",
     *                 enum={"married", "not_married", "widow_er", "divorced"}
     *             ),
     *             @OA\Property(property="relationship_preference_id", type="integer", example=1),
     *             @OA\Property(property="show_my_orientation", type="boolean", example=true),
     *             @OA\Property(property="show_my_gender", type="boolean", example=true),
     *             @OA\Property(
     *                 property="show_me",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="string",
     *                     enum={"male", "female", "m_f", "m_m", "f_f"}
     *                 ),
     *                 example={"male", "female"}
     *             ),
     *
     *             @OA\Property(
     *                 property="interests",
     *                 type="array",
     *
     *                 @OA\Items(type="integer"),
     *                 minItems=3,
     *                 maxItems=5,
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User information updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Данные успешно обновлены")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=406,
     *         description="Validation error - wrong parameters",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Wrong params"),
     *             @OA\Property(property="code", type="integer", example=4064)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - invalid input data",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="field_name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The field name is required.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function updateUserInfoRegistration(updateUserInfoRegistrationRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $data = $request->validated();

            $this->userService->updateUserInfoRegistration($data, $user->id);

            return $this->successResponse(['message' => 'Данные успешно обновлены']);

        } catch (\Exception $e) {
            \Log::error('User info update error: '.$e->getMessage());

            if ($e->getCode() === 4064) {
                return response()->json([
                    'code' => 4064,
                    'message' => 'Wrong params',
                ], 406);
            }

            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * Get user likes with filtering options
     *
     * @OA\Get(
     *     description="Retrieve users who liked the authenticated user with various filtering options",
     *     tags={"User Settings"},
     *     path="/users/likes",
     *     summary="Get user likes",
     *     operationId="getUserLikes",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         description="Filter type for results",
     *         required=false,
     *         name="filter",
     *         in="query",
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"by_distance", "by_information", "by_verification_status", "by_settings"}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         description="Successful operation",
     *         response=200,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="age", type="integer", nullable=true),
     *                     @OA\Property(property="distance", type="integer", nullable=true),
     *                     @OA\Property(property="superliked_me", type="boolean"),
     *                     @OA\Property(property="is_online", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getUserLikes(GetUserLikesRequest $request): JsonResponse
    {
        try {
            // Получаем текущего пользователя через customer или auth user
            $secondaryUser = $request->customer ?? $request->user();

            if (! $secondaryUser) {
                return $this->errorUnauthorized();
            }

            // Получаем параметр фильтра
            $filter = $request->get('filter');

            // Константа для базовой подписки
            $PLUS_SUBSCRIPTION_ID = 1;

            $userSettings = null;

            // Если фильтр by_settings, проверяем подписку пользователя
            if ($filter === 'by_settings') {
                $userLatestSubscription = BoughtSubscriptions::where('due_date', '>', now())
                    ->whereHas('transaction', function ($query) use ($secondaryUser) {
                        $query->where('user_id', $secondaryUser->id);
                    })
                    ->with(['package.subscription'])
                    ->first();

                if ($userLatestSubscription && $userLatestSubscription->package &&
                    $userLatestSubscription->package->subscription_id > $PLUS_SUBSCRIPTION_ID) {
                    $userSettings = LikeSettings::where('user_id', $secondaryUser->id)->first();
                }
            }

            // Получаем через UserService (теперь возвращает готовую коллекцию)
            $formattedResults = $this->userService->getUserLikes($secondaryUser, $filter, $userSettings);

            return $this->successResponse([
                'items' => $formattedResults,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Something went wrong: '.$e->getMessage(),
                5000,
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/email-exist",
     *     summary="get email exist status",
     *     description="check email exist status",
     *     tags={"Users email exist check"},
     *
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Email для проверки",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="string",
     *             format="email"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 description="true - email существует, false - не существует"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Сообщение об успехе"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Некорректный email адрес"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Error check email exist"
     *             )
     *         )
     *     )
     * )
     */
    public function getEmailExistenceStatus(Request $request): JsonResponse
    {
        $email = $request->query('email');

        if (! $email) {
            return $this->errorResponse('Email обязателен для заполнения', 400);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Некорректный формат email', 400);
        }

        try {
            $exists = $this->userService->getEmailExistenceStatus($email);

            return $this->successResponse([
                'status' => $exists,
                'message' => $exists ? 'Email существует' : 'Email не существует',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode() ?: 500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/packagesInfo",
     *     tags={"User Packages"},
     *     summary="Get user packages and limits",
     *     description="Returns user subscription packages and superboom/superlike limits",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="error", type="null"),
     *                  @OA\Property(property="status", type="integer", example=200)
     *              ),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="subscription_package", type="object", nullable=true,
     *                      @OA\Property(property="type", type="string", example="premium"),
     *                      @OA\Property(property="due_date", type="string", format="date-time", example="2038-12-31 00:00:00")
     *                  ),
     *                  @OA\Property(property="superboom_due_date", type="string", format="date-time", nullable=true, example="2038-12-31 00:00:00"),
     *                  @OA\Property(property="superbooms", type="integer", example=5),
     *                  @OA\Property(property="superlikes", type="integer", example=257)
     *              )
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized")
     *     ),
     *
     *     @OA\Response(
     *          response=500,
     *          description="Server error"
     *      )
     * )
     */
    public function getUserPackages(Request $request): JsonResponse
    {
        try {
            $userPackages = $this->userService->getUserPackages($request->user()->id);

            return $this->successResponse($userPackages);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }
}
