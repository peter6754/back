<?php

namespace App\Http\Controllers\Users;

use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\UserService;
use Illuminate\Http\Request;
use Exception;

/**
 * Class InfoController
 * @package App\Http\Controllers\Users
 */
class InfoController extends Controller
{
    /**
     * Include API response trait
     */
    use ApiResponseTrait;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param $id
     * @param Request $request
     * @OA\Get(
     *     path="/users/info/{id}",
     *     tags={"User Settings"},
     *     summary="Get user profile",
     *     description="Returns detailed user profile information by user ID",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(
     *             type="string",
     *             format="uuid",
     *             example="00012bae-a544-4300-acb4-8b39ca353b8c"
     *         )
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
     *                          "id": "00012bae-a544-4300-acb4-8b39ca353b8c",
     *                          "name": "Олежа",
     *                          "bio": null,
     *                          "educational_institution": null,
     *                          "role": null,
     *                          "residence": "Благовещенск",
     *                          "company": null,
     *                          "gender": "Мужчина",
     *                          "age": 19,
     *                          "info": {
     *                              "Гетеро",
     *                              "Не женат",
     *                              "Серьезные отношения"
     *                          },
     *                          "distance": 6666,
     *                          "is_verified": false,
     *                          "images": {
     *                              "45,075c70c6065121",
     *                              "48,075c7142509235",
     *                              "46,075c723825fe52",
     *                              "44,075c7391990dc5"
     *                          },
     *                          "interests": {
     *                              "Фильмы",
     *                              "Киберспорт",
     *                              "Сериалы",
     *                              "Пиво",
     *                              "Аниме"
     *                          },
     *                          "gifts": {},
     *                          "gifts_count": 0,
     *                          "feedbacks_count": 0
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
    public function getUser($id, Request $request): JsonResponse
    {
        $viewer = $request->user()->toArray();
        try {
            return $this->successResponse(
                $this->userService->getUser($id, $viewer)
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }
}
