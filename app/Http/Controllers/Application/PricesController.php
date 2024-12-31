<?php

namespace App\Http\Controllers\Application;

use Exception;
use App\Models\Gifts;
use Illuminate\Http\Request;
use App\Models\GiftCategory;
use App\Models\Subscriptions;
use App\Models\ServicePackages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;

class PricesController extends Controller
{
    use ApiResponseTrait;

    /**
     * @param $subscription_id
     * @param Request $request
     * @OA\Get(
     *     path="/application/prices/subscriptions/{id}",
     *     tags={"App Settings"},
     *     summary="Price for subscriptions",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *           name="id",
     *           in="path",
     *           description="Type (на backend-е конвертируем в numeric, все лишнее удаляем)",
     *           required=true,
     *          @OA\Schema(
     *              enum={"1 - Plus", "2 - Gold", "3 - Premium"},
     *              example="2 - Gold",
     *              type="string"
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
     *                      "type": "Tinderone Plus+",
     *                      "subscription_services": {
     *                          {
     *                              "description": "Безлимит лайков! Ставь столько лайков, сколько захочешь!",
     *                              "image": "2,277103501936"
     *                          }
     *                      },
     *                      "subscription_packages": {
     *                          {
     *                              "id": 1,
     *                              "is_bestseller": false,
     *                              "stock": 0,
     *                              "term": "one_month",
     *                              "price_per_month": "399"
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
    public function getSubscriptions($subscription_id, Request $request): JsonResponse
    {
        try {
            $subscription_id = (int)preg_replace('/\D/', '', $subscription_id);
            $gender = $request->customer['gender'];

            $subscription = Subscriptions::with([
                'services:id,subscription_id,description,image',
                'packages.price'
            ])
                ->findOrFail($subscription_id);

            $formattedSubscription = [
                'type' => $subscription->type,
                'subscription_services' => $subscription->services->map(function ($service) use ($gender) {
                    return [
                        'description' => $service->description,
                        'image' => $service->image
                    ];
                }),
                'subscription_packages' => $subscription->packages->map(function ($package) use ($gender) {
                    return [
                        'id' => $package->id,
                        'is_bestseller' => (bool)$package->is_bestseller,
                        'stock' => $package->stock,
                        'term' => $package->term,
                        'price_per_month' => (string)intval($package->price->{$gender} ?? 0)
                    ];
                })
            ];

            return $this->successResponse($formattedSubscription);
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
     *     path="/application/prices/service-package",
     *     tags={"App Settings"},
     *     summary="Price for service packages",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *           name="type",
     *           in="query",
     *           description="Type",
     *           required=true,
     *          @OA\Schema(
     *              type="string",
     *              enum={"superboom","superlike"},
     *              example="superboom"
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
     *                              "count": 3,
     *                              "stock": 0,
     *                              "is_bestseller": false,
     *                              "price_per_one": "280"
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
    public function getPackages(Request $request): JsonResponse
    {
        try {
            $gender = $request->customer['gender'];
            $type = $request->input('type');

            $packages = ServicePackages::where('type', $type)
                ->with(['price'])
                ->select(['id', 'count', 'stock', 'is_bestseller'])
                ->get()
                ->map(function ($package) use ($gender) {
                    return [
                        'id' => $package->id,
                        'count' => $package->count,
                        'stock' => $package->stock,
                        'is_bestseller' => $package->is_bestseller,
                        'price_per_one' => (string)intval($package->price->{$gender} ?? 0)
                    ];
                });

            return $this->successResponse([
                'items' => $packages
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/application/prices/gifts/categories",
     *     tags={"App Settings"},
     *     summary="Get gift categories",
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
     *                      "categories": {
     *                          {
     *                              "id": 1,
     *                              "name": "Цветы",
     *                              "image": "1,26f1bdaeb0c8"
     *                          }
     *                      },
     *                      "popular_gifts": {
     *                          {
     *                              "image": "2,26f56d34ec55",
     *                              "category_id": 1,
     *                              "id": 1
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
    public function getGiftCategories(): JsonResponse
    {
        try {
            // Все категории подарков
            $categories = GiftCategory::all();

            // Популярные подарки (по количеству в user_gifts)
            $popular_gifts = DB::table('user_gifts as ug')
                ->leftJoin('gifts as g', 'g.id', '=', 'ug.gift_id')
                ->select('g.image', 'g.category_id', 'g.id', DB::raw('COUNT(ug.gift_id) as count'))
                ->groupBy('g.id', 'g.image', 'g.category_id')
                ->orderByDesc('count')
                ->limit(2)
                ->get();

            return $this->successResponse([
                'categories' => $categories,
                'popular_gifts' => $popular_gifts->map(function ($popular_gifts) {
                    return [
                        "image" => $popular_gifts->image,
                        "category_id" => $popular_gifts->category_id,
                        "id" => $popular_gifts->id
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param $category_id
     * @param Request $request
     * @OA\Get(
     *     path="/application/prices/gifts/{id}",
     *     tags={"App Settings"},
     *     summary="Price for gift",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *           name="id",
     *           in="path",
     *           description="ID категории",
     *           required=true,
     *          @OA\Schema(
     *              type="integer",
     *              example=2
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
     *                      "type": "Tinderone Plus+",
     *                      "subscription_services": {
     *                          {
     *                              "description": "Безлимит лайков! Ставь столько лайков, сколько захочешь!",
     *                              "image": "2,277103501936"
     *                          }
     *                      },
     *                      "subscription_packages": {
     *                          {
     *                              "id": 1,
     *                              "is_bestseller": false,
     *                              "stock": 0,
     *                              "term": "one_month",
     *                              "price_per_month": "399"
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
    public function getGifts($category_id, Request $request): JsonResponse
    {
        try {
            $category_id = (int)preg_replace('/\D/', '', $category_id);
            $gender = $request->customer['gender'];
            $gifts = Gifts::with('price')
                ->where('category_id', $category_id)
                ->get();

            $items = $gifts->map(function ($gift) use ($gender) {
                $price = $gift->price->{$gender};

                return [
                    'id' => $gift->id,
                    'image' => $gift->image,
                    'message' => $gift->message,
                    'price' => (string)intval($price),
                ];
            });

            return $this->successResponse([
                'items' => $items
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }
}
