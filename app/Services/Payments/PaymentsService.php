<?php

namespace App\Services\Payments;

use Illuminate\Config\Repository;
use App\Models\SubscriptionPackages;
use Illuminate\Support\Manager;
use App\Models\Transactions;
use Illuminate\Foundation\Application;

class PaymentsService extends Manager
{
    // Subscribes params list
    static array $subscriptions = [
        1 => [
            "female" => "d527d1a5-92e2-48a8-9565-51ad17004d59",
            "male" => "1c8ff1ce-15f9-4245-8762-82b5d8a4aaa1",
        ],
        4 => [
            "female" => "55f9c107-f698-4f51-95a8-bfd68ffeb3ef",
            "male" => "545a01b4-7de7-4063-93f4-4572d0036d29",
        ],
        7 => [
            "female" => "b44bdb1e-e7f7-44bb-a886-b040cc5cb53c",
            "male" => "648ef7cf-382c-4ff1-9656-672220d299e1",
        ],
        99 => [
            "female" => "bdc80644-a987-4798-bafc-85356d86bd55",
            "male" => "bdc80644-a987-4798-bafc-85356d86bd55",
        ],
    ];

    // Gender convert
    static array $genders = [
        "female" => "female",
        "male" => "male",
        "f_f" => "female",
        "m_f" => "male",
        "m_m" => "male",
    ];


    // Orders Products codes
    const ORDER_PRODUCT_SUBSCRIPTION = "subscription_package";
    const ORDER_PRODUCT_SERVICE = "service_package";
    const ORDER_PRODUCT_GIFT = "gift";

    // Orders statuses
    const ORDER_STATUS_COMPLETE = 'succeeded';
    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_CANCEL = 'canceled';

    // Products helpers
    static array $titleProducts = [
        self::ORDER_PRODUCT_SUBSCRIPTION => "Subscription package",
        self::ORDER_PRODUCT_SERVICE => "Service package",
        self::ORDER_PRODUCT_GIFT => "Gift"
    ];

    /**
     * @return Providers\RobokassaProvider
     */
    public function createRobokassaDriver()
    {
        return new Providers\RobokassaProvider();
    }

    /**
     * @return Repository|Application|mixed|object|string|null
     */
    public function getDefaultDriver(): mixed
    {
        return config('payments.default');
    }

    /**
     * Покупка разных опций (superlike, superboom)
     * @param string $provider
     * @param array $params
     * @return array
     */
    public function buyServicePackage(string $provider, array $params): array
    {

    }

    /**
     * Оформление подписки
     * @param string $provider
     * @param array $params
     * @return array
     */
    public function buySubscription(string $provider, array $params): array
    {
        // Default variables
        $gender = self::$genders[$params["customer"]["gender"]];
        $currentAction = "payment";
        $price = null;

        // Calculate price
        if ($package = SubscriptionPackages::with('price')->find((int)$params["package_id"])) {
            $package = $package->toArray();

            $price = (float)$package['price'][$gender];

            if ($params["from_banner"]) {
                $price *= 0.7;
            }

            $price = round($price, 2);
        }

        if (isset(self::$subscriptions[$params["package_id"]][$gender])) {
            $price = self::$subscriptions[$params["package_id"]][$gender];
            $currentAction = "recurrent";
        }

        return $this->createPayment($provider, [
            "customer" => [
                "full_name" => $params["customer"]["name"],
                "email" => $params["customer"]["email"],
                "phone" => $params["customer"]["phone"],
                "id" => $params["customer"]["id"]
            ],
            "product" => PaymentsService::ORDER_PRODUCT_SUBSCRIPTION,
            "action" => $currentAction,
            "price" => $price
        ]);

    }

    public function buyGift(string $provider, array $params): array
    {

    }

    /**
     * @param string $provider
     * @param array $params
     * @return array
     */
    private function createPayment(string $provider, array $params): array
    {
        $paymentData = call_user_func([$this->driver($provider), $params['action']], [
            "description" => self::$titleProducts[$params['product']],
            "email" => $params['customer']['email'],
            "amount" => $params['price'] ?? null
        ]);

        // Create transaction
//        try {
//            Transactions::create([
//                "id" => $paymentData['id'],
//                "purchased_at" => $paymentData['created_at'],
//                "price" => $params['price'],
//                "type" => $params['product'],
//                "user_id" => $params['customer']['id'],
//                "status" => self::ORDER_STATUS_PENDING,
//            ]);
//        } catch (\Exception $exception) {
//            echo $exception->getMessage();
//        }

        return [
            "confirmation_url" => $paymentData["confirmation_url"],
            "payment_id" => $paymentData["payment_id"],
            "discounted_price" => $params['price']
        ];
    }
}
