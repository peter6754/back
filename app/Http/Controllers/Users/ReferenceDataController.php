<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Helpers\UserInformationTranslator;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Interests;
use App\Models\RelationshipPreferences;
use App\Models\Secondaryuser;
use App\Models\UserInterests;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    use ApiResponseTrait;

    /**
     * @param Request $request
     * @OA\Get(
     *     path="/users/reference-data/interests",
     *     tags={"User settings"},
     *     summary="Get interests list",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *           name="search",
     *           in="query",
     *           description="Search by interest name",
     *           required=false,
     *          @OA\Schema(
     *              type="string",
     *              example="Фотография"
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          {
     *                              "id": 1,
     *                              "name": "Мотоциклы"
     *                          },
     *                          {
     *                              "id": 2,
     *                              "name": "Кофе"
     *                          },
     *                          {
     *                              "id": 3,
     *                              "name": "Театр"
     *                          }
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getInterests(Request $request): JsonResponse
    {
        try {
            $query = Interests::query();

            if ($search = $request->query('search')) {
                $query->where('name', 'like', "%{$search}%");
            }

            $interests = $query->select(['id', 'name'])->get();

            return $this->successResponse([
                'items' => $interests
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }
    /**
     * @OA\Get(
     *     path="/users/reference-data/relationship-preferences",
     *     tags={"User settings"},
     *     summary="Get available genders",
     *     description="Returns list of available relationship preferences",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          {
     *                          "id": 1,
     *                          "preference": "Серьезные отношения"
     *                          },
     *                          {
     *                          "id": 2,
     *                          "preference": "Встречи на один раз."
     *                          },
     *                          {
     *                          "id": 3,
     *                          "preference": "Серьезные отношения, но не против встречи на один раз.."
     *                          },
     *                          {
     *                          "id": 4,
     *                          "preference": "Встречи на один раз, но не против регулярных встреч."
     *                          },
     *                          {
     *                          "id": 5,
     *                          "preference": "Новых друзей"
     *                          },
     *                          {
     *                          "id": 6,
     *                          "preference": "Все ещё думаю."
     *                          }
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getRelationshipPreferences(): JsonResponse
    {
        try {
            $query = RelationshipPreferences::query();

            $relationshipPreferences = $query->select(['id', 'preference'])->get();

            return $this->successResponse([
                'items' => $relationshipPreferences
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/genders",
     *     tags={"User settings"},
     *     summary="Get available genders",
     *     description="Returns list of available gender options with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "male": "Мужчина",
     *                          "female": "Женщина",
     *                          "m_f": "М+Ж",
     *                          "m_m": "М+М",
     *                          "f_f": "Ж+Ж"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getGenders(): JsonResponse
    {
        try {
            $genders = [
                'items' => UserInformationTranslator::getTranslationsForCategory('genders')
            ];
            return $this->successResponse($genders);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/zodiac-signs",
     *     tags={"User settings"},
     *     summary="Get available zodiac signs",
     *     description="Returns list of available zodiac signs options with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "capricornus": "Козерог",
     *                          "aquarius": "Водолей",
     *                          "pisces": "Рыбы",
     *                          "aries": "Овен",
     *                          "taurus": "Телец",
     *                          "gemini": "Близнецы",
     *                          "cancecr": "Рак",
     *                          "leo": "Лев",
     *                          "virgo": "Дева",
     *                          "libra": "Весы",
     *                          "sagittarius": "Стрелец",
     *                          "scorpius": "Скорпион"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getZodiacSigns(): JsonResponse
    {
        try {
            $zodiacSigns = [
                'items' => UserInformationTranslator::getTranslationsForCategory('zodiac_signs')
            ];
            return $this->successResponse($zodiacSigns);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Get(
     *     path="/users/reference-data/family-statuses",
     *     tags={"User settings"},
     *     summary="Get family statuses by gender",
     *     description="Returns list of family statuses with translations based on gender",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *           name="gender",
     *           in="query",
     *           description="Gender type to get appropriate family status translations",
     *           required=true,
     *          @OA\Schema(
     *              type="string",
     *              enum={"male","female","m_f","m_m","f_f"},
     *              example="male"
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          {
     *                              "key": "married",
     *                              "translation_ru": "Женат"
     *                          },
     *                          {
     *                              "key": "not_married",
     *                              "translation_ru": "Не женат"
     *                          },
     *                          {
     *                              "key": "widow_er",
     *                              "translation_ru": "Вдовец"
     *                          },
     *                          {
     *                               "key": "divorced",
     *                               "translation_ru": "Разведен"
     *                          }
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getFamilyStatuses(Request $request): JsonResponse
    {
        try {
            $gender = $request->query('gender');
            $maleGenders = ['male', 'm_m', 'm_f'];
            $isMale = in_array($gender, $maleGenders);

            $statuses = collect(UserInformationTranslator::getTranslationsForCategory('family_statuses'))
                ->map(function ($translations, $key) use ($isMale) {
                    return [
                        'key' => $key,
                        'translation_ru' => $isMale ? $translations[0] : $translations[1]
                    ];
                })
                ->values();

            return $this->successResponse(['items' => $statuses]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/education",
     *     tags={"User settings"},
     *     summary="Get education options",
     *     description="Returns list of education levels with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "lower_secondary": "Неполное среднее",
     *                           "secondary": "Среднее",
     *                           "specialized_secondary": "Среднее специальное",
     *                           "incomplete_higher": "Неполное высшее",
     *                           "higher": "Высшее",
     *                           "two_or_more_higher": "Два или более высших",
     *                           "academic_degree": "Ученая степень"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getEducationOptions(): JsonResponse
    {
        try {
            $educationOptions = [
                'items' => UserInformationTranslator::getTranslationsForCategory('education')
            ];
            return $this->successResponse($educationOptions);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/family",
     *     tags={"User settings"},
     *     summary="Get family plans options",
     *     description="Returns list of family plans with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "want_children": "Я хочу детей",
     *                          "dont_want_children": "Я не хочу детей",
     *                          "have_children_and_want": "У меня есть дети и хочу еще",
     *                          "have_children_and_dont_want": "Есть дети, но больше не хочу",
     *                          "not_decided": "Пока не знаю, хочу ли детей"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getFamilyPlans(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('family')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Get(
     *     path="/users/reference-data/communication",
     *     tags={"User settings"},
     *     summary="Get communication preferences",
     *     description="Returns list of communication preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "texting": "Люблю переписываться",
     *                          "by_phone": "Люблю общаться по телефону",
     *                          "videocall": "Больше нравятся видеозвонки",
     *                          "meet": "Лучше встречусь лично"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getCommunicationOptions(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('communication')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/love-languages",
     *     tags={"User settings"},
     *     summary="Get love languages",
     *     description="Returns list of love languages with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "gifts": "Нравятся подарки",
     *                          "touches": "Нравятся прикосновения",
     *                          "compliments": "Нравятся комплименты",
     *                          "deeds": "Нравятся поступки",
     *                          "constant_attention": "Нравится постоянное внимание"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getLoveLanguages(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('love_language')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/pets",
     *     tags={"User settings"},
     *     summary="Get pet preferences",
     *     description="Returns list of pet preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "dog": "Собака",
     *                          "cat": "Кошка",
     *                          "reptile": "Рептилия",
     *                          "amphibian": "Амфибия",
     *                          "bird": "Птица",
     *                          "fish": "Рыбки",
     *                          "turtle": "Черепаха",
     *                          "rabbit": "Кролик",
     *                          "hamster": "Хомяк",
     *                          "i_want": "Нет, но хочу питомца",
     *                          "dont_have": "Нет питомцев"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getPets(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('pets')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/alcohol",
     *     tags={"User settings"},
     *     summary="Get alcohol preferences",
     *     description="Returns list of alcohol preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "dont_drink": "Я не пью",
     *                          "on_holidays": "Пью по праздникам",
     *                          "on_weekends": "Пью по выходным",
     *                          "drink_often": "Пью часто"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getAlcohol(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('alcohol')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/smoking",
     *     tags={"User settings"},
     *     summary="Get smoking preferences",
     *     description="Returns list of smoking preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "for_the_company": "Курю за компанию",
     *                          "when_i_drink": "Курю, когда выпью",
     *                          "i_smoke": "Курю",
     *                          "dont_smoke": "Не курю",
     *                          "give_up": "Бросаю"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getSmoking(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('smoking')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/sport",
     *     tags={"User settings"},
     *     summary="Get sport preferences",
     *     description="Returns list of sport preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "train_everyday": "Тренируюсь каждый день",
     *                          "train_often": "Часто тренируюсь",
     *                          "train_sometimes": "Иногда тренируюсь",
     *                          "dont_train": "Не занимаюсь спортом"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getSport(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('sport')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/food",
     *     tags={"User settings"},
     *     summary="Get food preferences",
     *     description="Returns list of food preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "veganism": "Веганство",
     *                          "vegetarianism": "Вегетарианство",
     *                          "pescatarianism": "Пескетарианство",
     *                          "kosher_food": "Кошерная еда",
     *                          "everything": "Ем всё"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getFood(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('food')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/social-network",
     *     tags={"User settings"},
     *     summary="Get social network preferences",
     *     description="Returns list of social network preferences with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "influencer": "Инфлюенсер соцсетей",
     *                          "active_user": "Активный пользователь соцсетей",
     *                          "dont_use": "Меня нет в соцсетях",
     *                          "sometimes_im_on": "Иногда захожу в соцсети"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getSocialNetwork(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('social_network')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/sleep",
     *     tags={"User settings"},
     *     summary="Get sleep chronotypes",
     *     description="Returns list of sleep chronotypes with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "lark": "Я жаворонок",
     *                          "owl": "Я сова"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getSleep(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('sleep')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/orientations",
     *     tags={"User settings"},
     *     summary="Get sexual orientations",
     *     description="Returns list of sexual orientations with translations",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          "hetero": "Гетеро",
     *                          "gay": "Гей",
     *                          "lesbian": "Лесбиянка",
     *                          "bisexual": "Бисексуал(ка)",
     *                          "asexual": "Асексуал(ка)",
     *                          "not_decided": "Не определился(лась)"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getOrientations(): JsonResponse
    {
        try {
            return $this->successResponse([
                'items' => UserInformationTranslator::getTranslationsForCategory('orientations')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * Проверить существование username
     */
    public function getUsernameExistenceStatus(Request $request): JsonResponse
    {
        $username = $request->query('username');
        $exists = Secondaryuser::where('username', $username)->exists();

        return response()->json(['status' => $exists]);
    }

    /**
     * Проверить существование email
     */
    public function getEmailExistenceStatus(Request $request): JsonResponse
    {
        $email = $request->query('email');
        $exists = Secondaryuser::where('email', $email)->exists();

        return response()->json(['status' => $exists]);
    }

    /**
     * @OA\Get(
     *     path="/users/reference-data/clubs",
     *     tags={"User settings"},
     *     summary="Get user clubs and additional clubs",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "my_clubs": {
     *                          {
     *                              "name": "Кофе",
     *                              "image": "3,270a03362f79",
     *                              "id": 2
     *                          },
     *                          {
     *                              "name": "Ходьба",
     *                              "image": "5,272be78151bf",
     *                              "id": 35
     *                          }
     *                      },
     *                      "additional": {
     *                          {
     *                              "name": "Мотоциклы",
     *                              "image": "6,2709abd0cdc3",
     *                              "id": 1
     *                          },
     *                          {
     *                              "name": "Театр",
     *                              "image": "1,270b06ef8adb",
     *                              "id": 3
     *                          }
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     */
    public function getClubs(Request $request): JsonResponse
    {
        try {

            $customerData = $request->customer;

            $userId = $customerData['id'];

            $myInterestIds = UserInterests::where('user_id', $userId)
                ->pluck('interest_id');

            $myClubs = UserInterests::where('user_interests.user_id', $userId)
                ->join('interests', 'user_interests.interest_id', '=', 'interests.id')
                ->select([
                    'interests.club_name as name',
                    'interests.image as image',
                    'interests.id as id'
                ])
                ->get();

            $additionalClubs = Interests::whereNotIn('id', $myInterestIds)
                ->select(['club_name as name', 'image', 'id'])
                ->get();

            return $this->successResponse([
                'my_clubs' => $myClubs,
                'additional' => $additionalClubs
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }

    }


    /**
     * Перенаправить на профиль
     */
    public function redirectToProfile(Request $request): JsonResponse
    {
        $id = $request->query('id');
        return response()->json(['url' => "tinderone://profile/{$id}"]);
    }

    /**
     * Перенаправить на reel
     */
    public function redirectToReel(Request $request): JsonResponse
    {
        $id = $request->query('id');
        return response()->json(['url' => "tinderone://reel/{$id}"]);
    }

    /**
     * Перенаправить на deeplink
     */
    public function redirectToDeeplink(): JsonResponse
    {
        return response()->json(['url' => "tinderone://home"]);
    }

}
