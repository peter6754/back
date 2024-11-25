<?php

namespace App\Services\Payments;

use App\Models\BoughtSubscriptions;
use App\Services\ExpoNotificationService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Application;
use App\Models\SubscriptionPackages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionProcess;
use Illuminate\Config\Repository;
use App\Models\ServicePackages;
use Illuminate\Support\Manager;
use App\Models\Secondaryuser;
use App\Services\ChatService;
use App\Models\Transactions;
use App\Models\Gifts;

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


    // Days in subscription
    static array $subscriptionDays = [
        "year" => 360,
        "six_months" => 180,
        "one_month" => 30,
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
     * @param string $provider
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function buyServicePackage(string $provider, array $params): array
    {
        // Default variables
        $gender = self::$genders[$params["customer"]["gender"]];

        // Get price
        $package = ServicePackages::with('price')
            ->find((int)$params["package_id"]);
        if (!$package) {
            throw new \Exception("Item not found", 4040);
        }
        $package = $package->toArray();

        // Calculate product price
        $price = $package['price'][$gender] * $package['count'];
        $discount = ((100 - $package['stock']) / 100);

        // Create payment
        return $this->createPayment($provider, [
            "price" => round($price * $discount, 2),
            "product" => PaymentsService::ORDER_PRODUCT_SERVICE,
            "product_id" => $params["package_id"],
            "customer" => $params["customer"],
            "action" => "payment",
        ]);
    }

    /**
     * @param string $provider
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function buySubscription(string $provider, array $params): array
    {
        // Default variables
        $gender = self::$genders[$params["customer"]["gender"]];
        $currentAction = "payment";

        // Get price
        $package = SubscriptionPackages::with('price')
            ->find((int)$params["package_id"]);
        if (!$package) {
            throw new \Exception("Item not found", 4040);
        }
        $package->toArray();

//        $getSubscriptions = DB::table('bought_subscriptions')
//            ->select([
//                'bought_subscriptions.*',
//                'transactions.user_id',
//                'transactions.price'
//            ])
//            ->leftJoin('transactions', 'bought_subscriptions.transaction_id', '=', 'transactions.id')
//            ->where('bought_subscriptions.due_date', '>', now())
//            ->where('transactions.user_id', $params["customer"]["id"])
//            ->get();
//        print_r($getSubscriptions);
//        exit;

        $price = (float)$package['price'][$gender];
        if ($params["from_banner"]) {
            $price *= 0.7;
        }

        $price = round($price, 2);

        if (isset(self::$subscriptions[$params["package_id"]][$gender])) {
            $price = self::$subscriptions[$params["package_id"]][$gender];
            $currentAction = "recurrent";
        }

        return $this->createPayment($provider, [
            "product" => PaymentsService::ORDER_PRODUCT_SUBSCRIPTION,
            "product_id" => $params["package_id"],
            "customer" => $params["customer"],
            "action" => $currentAction,
            "price" => $price
        ]);
    }

    /**
     * @param string $provider
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function buyGift(string $provider, array $params): array
    {
        // Default variables
        $gender = self::$genders[$params["customer"]["gender"]];

        // Get price
        $gift = Gifts::with('price')->find((int)$params["gift_id"]);
        $user = Secondaryuser::find($params["user_id"]);
        if (!$user || !$gift) {
            throw new \Exception("Gift/User doesn't exist", 4060);
        }

        $gift = $gift->toArray();
        $user = $user->toArray();

        return $this->createPayment($provider, [
            "product" => PaymentsService::ORDER_PRODUCT_GIFT,
            "price" => (float)$gift['price'][$gender],
            "product_id" => $params["gift_id"],
            "customer" => $params["customer"],
            "receiver_id" => $user['id'],
            "action" => "payment",
        ]);
    }

    /**
     * @param array $params
     * @return bool
     * @throws GuzzleException
     */
    public function sendServicePackage(array $params)
    {
        try {
            $updateParams = [];

            if (!empty($params['superlikes'])) {
                $updateParams['superlikes'] = DB::raw('superlikes + ' . $params['superlikes']);
            }

            if (!empty($params['superbooms'])) {
                $updateParams['superbooms'] = DB::raw('superbooms + ' . $params['superbooms']);
            }

            if (!empty($updateParams)) {
                DB::table('user_information')
                    ->where('user_id', $params['user_id'])
                    ->update($updateParams);
            }
            return true;
        } catch (\Exception $e) {
            Log::channel('payments')->error(__METHOD__ . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param array $params
     * @return bool
     * @throws GuzzleException
     */
    public function sendSubscription(array $params): bool
    {
        try {
            DB::transaction(function () use ($params) {
                // Работаем строго с подписками
                if ($params['type'] == self::ORDER_PRODUCT_SUBSCRIPTION) {
                    // Деактивируем все активные подписки пользователя
                    BoughtSubscriptions::whereHas('transaction', function ($query) use ($params) {
                        $query->where('status', 'succeeded')
                            ->where('user_id', $params['user_id']);
                    })
                        ->where('due_date', '>', now())
                        ->update(['due_date' => null]);

                    // Активируем новую подписку
                    $termDays = self::$subscriptionDays[$params['subscription_term']] ?? 1;
                    BoughtSubscriptions::where('transaction_id', $params['transaction_id'])
                        ->update(['due_date' => now()->addDays($termDays)]);
                }

                // Обновление информации пользователя / пакетов
                if ($params['subscription_id'] > 1) {
                    $this->sendServicePackage([
                        'user_id' => $params['user_id'],
                        'superlikes' => 5,
                        'superbooms' => 1,
                    ]);
                }
            });
            return true;
        } catch (\Exception $e) {
            Log::channel('payments')->error(__METHOD__ . ': ' . $e->getMessage());
            return false;
        }
    }


    /**
     * @param array $params
     * @return bool
     * @throws GuzzleException
     */
    public function sendGift(array $params): bool
    {
        try {
            // Проверка существующих сообщений
            $hasPreviousMessages = DB::table('chat_messages')
                ->where('sender_id', $params['gift_receiver_id'])
                ->where('receiver_id', $params['gift_sender_id'])
                ->exists();

            // Отправка подарка
            (new ChatService())->sendGift([
                'user_id' => $params['gift_receiver_id'],
                'sender_id' => $params['gift_sender_id'],
                'gift_id' => $params['gift_id']
            ], !$hasPreviousMessages);

            // Уведомление о подарке
            if (!$hasPreviousMessages) {
                (new ExpoNotificationService())->sendPushNotification(
                    json_decode($params['receiver_device_tokens'], true),
                    ExpoNotificationService::NEW_GIFT_PUSH['message'],
                    ExpoNotificationService::NEW_GIFT_PUSH['title'],
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('payments')->error(__METHOD__ . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $provider
     * @param array $params
     * @return array
     */
    public function createPayment(string $provider, array $params): array
    {
        // Init payment
        $paymentData = call_user_func([$this->driver($provider), $params['action']], array_merge($params, [
            "description" => self::$titleProducts[$params['product']]
        ]));

        // Create transaction
        Transactions::firstOrCreate([
            "id" => $paymentData['payment_id'] ?? null,
            "purchased_at" => $paymentData['created_at'],
            "price" => ($params['action'] != "recurrent" ? $params['price'] : 0.00),
            "type" => $params['product'],
            "user_id" => $params['customer']['id'],
            "status" => self::ORDER_STATUS_PENDING,
        ])->toArray();

        // Increment data
        switch ($params['product']) {
            case self::ORDER_PRODUCT_SUBSCRIPTION:
                DB::table("bought_subscriptions")->insert([
                    "transaction_id" => $paymentData['payment_id'],
                    "package_id" => $params['product_id'],
                ]);
                break;
            case self::ORDER_PRODUCT_SERVICE:
                DB::table("bought_service_packages")->insert([
                    "transaction_id" => $paymentData['payment_id'],
                    "package_id" => $params['product_id'],
                ]);
                break;
            case self::ORDER_PRODUCT_GIFT:
                DB::table("user_gifts")->insert([
                    "transaction_id" => $paymentData['payment_id'],
                    "sender_id" => $params['customer']['id'],
                    "receiver_id" => $params['receiver_id'],
                    "gift_id" => $params['product_id'],
                ]);
                break;
        }

        return [
            "confirmation_url" => $paymentData["confirmation_url"],
            "payment_id" => $paymentData["payment_id"]
        ];
    }

    /**
     * @param array $params
     * @return bool
     * @throws \Throwable
     */
    public static function updateTransaction(array $params): bool
    {
        $transactionId = $params['transaction_id'];
        unset($params['transaction_id']);

        try {
            return DB::transaction(function () use ($params, $transactionId) {
                $updatedProcess = DB::table('transactions_process')
                    ->where('transaction_id', $transactionId)
                    ->update($params);

                $updatedTransaction = DB::table('transactions')
                    ->where('id', $transactionId)
                    ->update($params);

                return $updatedProcess > 0 && $updatedTransaction > 0;
            });
        } catch (\Exception $e) {
            Log::channel('payments')->error('[ERROR] ' . __METHOD__ . ': ' . $e->getMessage());
            return false;
        }
    }


    protected function checkPendingPayments(string $invoiceId = null): array
    {
        if (is_null($invoiceId)) {
            TransactionProcess::find(["invoice_id" => $invoiceId]);
        }

    }
}
