<?php

namespace App\Services\Payments;

use Illuminate\Config\Repository;
use App\Models\SubscriptionPackages;
use Illuminate\Support\Manager;
use App\Models\Transactions;
use Illuminate\Foundation\Application;

class PaymentsService extends Manager
{
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
        $package = SubscriptionPackages::with('price')
            ->find((int)$params["package_id"])
            ->toArray();

        $price = (float)$package['price'][$params["customer"]["gender"]];
        if ($params["from_banner"]) {
            $price *= 0.7;
        }

        return $this->createPayment($provider, [
            "customer" => [
                "full_name" => $params["customer"]["name"],
                "email" => $params["customer"]["email"],
                "phone" => $params["customer"]["phone"],
                "id" => $params["customer"]["id"]
            ],
            "product" => PaymentsService::ORDER_PRODUCT_SUBSCRIPTION,
            "price" => round($price, 2),
            "action" => "pay"
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
            "amount" => $params['price'],
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
//        }catch (\Exception $exception){
//            echo $exception->getMessage();
//        }

        return [
            "confirmation_url" => $paymentData["url"],
            "payment_id" => $paymentData["id"],
            "discounted_price" => $params['price']
        ];
    }
}
