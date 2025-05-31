<?php

namespace App\Services\Payments\Providers;

use App\Services\Payments\Contracts\PaymentProviderInterface;
use Illuminate\Validation\ValidationException;
use App\Services\Payments\PaymentsService;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\TransactionRobokassa;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionProcess;
use GuzzleHttp\Client;

class RobokassaProvider implements PaymentProviderInterface
{
    private string $merchantLogin;
    private string $password1;
    private string $password2;
    private $payments;
    private $isTest;

    /**
     *
     */
    public function __construct()
    {
        $this->merchantLogin = config('payments.robokassa.merchant_login');

        $this->password1 = config('payments.robokassa.isTest') ?
            config('payments.robokassa.password1_test') :
            config('payments.robokassa.password1');

        $this->password2 = config('payments.robokassa.isTest') ?
            config('payments.robokassa.password2_test') :
            config('payments.robokassa.password2');

        $this->isTest = config('payments.robokassa.isTest');

        $this->payments = app(PaymentsService::class);
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return 'robokassa';
    }

    /**
     * @param array $params
     * @return array
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function recurrent(array $params): array
    {
        $required = ['price'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            throw ValidationException::withMessages([
                'payment' => 'Missing required payment parameters'
            ]);
        }

        // ToDo: Временный затык позже его убьем нафиг
        $getRobo = TransactionRobokassa::create([])->toArray();

        // ToDo: при удалении ID не забудь убрать из модели
        $getData = TransactionProcess::create([
            "email" => $params['customer']['email'],
            "user_id" => $params['customer']['id'],

            "provider" => $this->getProviderName(),
            "subscription_id" => $params['price'],
            "transaction_id" => $getRobo['id'],
            "id" => (int)$getRobo['invId'],
            "type" => $params['product']
        ])->toArray();

        $recurrentUrl = "https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/SubscriberGetOrCreate";
        $recurrentParams = [
            'subscriptionId' => $params['price'],
            'email' => $params['customer']['email']
        ];
        $response = (new Client())->request('POST', $recurrentUrl . '?' . http_build_query($recurrentParams));
        $queryParams = json_decode($response->getBody(), true);

        $baseUrl = 'https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/Subscribe';
        $baseParams = [
            'subscriberId' => $queryParams['subscriberId'],
            'subscriptionId' => $params['price']
        ];

        // Update subscriber id
        TransactionProcess::where('transaction_id', $getData['transaction_id'])->update([
            'subscriber_id' => $queryParams['subscriberId']
        ]);

        return [
            "confirmation_url" => $baseUrl . '?' . http_build_query($baseParams),
            "created_at" => (new \DateTime())->format('Y-m-d H:i:s'),
            "payment_id" => $getData['transaction_id'],
            "invoice_id" => $getData['id'],
        ];
    }

    /**
     * @param array $params
     * @return array
     * @throws ValidationException
     */
    public function payment(array $params): array
    {
        $required = ['price', 'description'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            throw ValidationException::withMessages([
                'payment' => 'Missing required payment parameters'
            ]);
        }

        // ToDo: Временный затык позже его убьем нафиг
        $getRobo = TransactionRobokassa::create([])->toArray();

        // ToDo: при удалении ID не забудь убрать из модели
        $getData = TransactionProcess::create([
            "email" => $params['customer']['email'],
            "user_id" => $params['customer']['id'],

            "provider" => $this->getProviderName(),
            "transaction_id" => $getRobo['id'],
            "type" => $params['product'],
            "price" => $params['price'],
            "id" => $getRobo['invId']
        ])->toArray();

        $expirationDate = (new \DateTime())->setTimestamp(strtotime("+1 day"));
        $baseUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';
        $queryParams = [];

        $signature = $this->signatureMerchant([
            $params['price'],
            $getData['id'],
            $this->password1,
            "Shp_product=" . $params['product'],
        ]);

        $queryParams = array_merge([
            'MerchantLogin' => $this->merchantLogin,
            'OutSum' => $params['price'],
            'InvId' => $getData['id'],
            'Description' => $params['description'],
            'Email' => $params['customer']['email'],
            'ExpirationDate' => $expirationDate->format('c'),
            'Shp_product' => $params['product'],
            'isTest' => $this->isTest,
            'SignatureValue' => $signature
        ], $queryParams);

        if (isset($params['recurring'])) {
            $queryParams['Recurring'] = "true";
        }

        return [
            "confirmation_url" => $baseUrl . '?' . http_build_query($queryParams),
            "created_at" => (new \DateTime())->format('Y-m-d H:i:s'),
            "payment_id" => $getData['transaction_id'],
            "invoice_id" => $getData['id'],
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public function subscription(array $params): array
    {
        $getData = TransactionProcess::firstOrCreate([
            "subscription_id" => $params['subscribeInfo']['subscription_id'] ?? null,
            "subscriber_id" => $params['subscribeInfo']['subscriber_id'] ?? null,

            "email" => $params['customer']['email'],
            "user_id" => $params['customer']['id'],

            "provider" => $this->getProviderName(),
            "type" => $params['product'],
            "price" => $params['price'],
            "id" => $params['invoice_id'],
        ])->toArray();

        return [
            "created_at" => (new \DateTime())->format('Y-m-d H:i:s'),
            "payment_id" => $getData['transaction_id'],
            "invoice_id" => $getData['id'],
            "confirmation_url" => null
        ];
    }

    public function validate(array $params, bool $result = false): bool
    {
        $required = ['InvId', 'OutSum', 'SignatureValue'];
        $customParams = [];

        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            return false;
        }

        foreach ($params as $key => $val) {
            if (str_contains($key, 'Shp_')) {
                $customParams[] = "{$key}={$val}";
            }
        }

        $signature = $this->signatureResult(array_merge([
            $params['OutSum'],
            $params['InvId'],
            ($result === false) ? $this->password1 :
                $this->password2
        ], $customParams));
        return strtolower($params['SignatureValue']) === strtolower($signature);
    }

    /**
     * @param array $params
     * @return array|string
     * @throws GuzzleException
     * @throws \Throwable
     */
    public function callbackResult(array $params): array|string
    {
        Log::channel($this->getProviderName())->info('Result request: ', $params);
        try {
            if (!$this->validate($params, true)) {
                throw new \Exception('Invalid signature');
            }

            // Checking transaction
            if ($getTransaction = TransactionProcess::find($params['InvId'])) {
                // Get Transaction
                $getTransaction = $getTransaction->toArray();

                // Если мы уже обработали платеж
                if ($getTransaction['status'] === PaymentsService::ORDER_STATUS_COMPLETE) {
                    return "OK" . $params['InvId'];
                }

                // Update status
                PaymentsService::updateTransaction([
                    'transaction_id' => $getTransaction['transaction_id'],
                    'status' => PaymentsService::ORDER_STATUS_COMPLETE
                ]);
            }

            $transaction = (new \App\Models\TransactionProcess())->transactionInfo(
                $transaction['transaction_id'] ??
                (int)$params['InvId'] ??
                null
            );

            if (empty($transaction['type'])) {
                $transaction['type'] = null;
            }

            switch ($params['Shp_product'] ?? $transaction['type'] ?? null) {
                case PaymentsService::ORDER_PRODUCT_SERVICE:
                    // Default variable
                    $updateParams = [];

                    // Calculate current options
                    if (!empty($transaction['package_type']) && !empty($transaction['package_count'])) {
                        $updateParams[$transaction['package_type']] = $transaction['package_count'];
                        $updateParams['user_id'] = $transaction['user_id'];

                        // Update params
                        $this->payments->sendServicePackage($updateParams);
                    }
                    break;

                case PaymentsService::ORDER_PRODUCT_GIFT:
                    $this->payments->sendGift($transaction);
                    break;

                default:
                    // Может быть подписка на платеж, если да ищем последнюю транзакцию с подпиской
                    if (empty($transaction['increment_id'])) {
                        $transaction = (array)(new \App\Models\TransactionProcess())->transactionInfo(
                            $params['EMail'] ?? null,
                            true
                        );

                        $getData = $this->payments->createPayment($this->getProviderName(), [
                            "product_id" => $transaction["subscription_id"] ?? null,
                            "product" => $transaction['type'] ?? null,
                            "subscribeInfo" => $getTransaction,
                            "price" => $params['OutSum'],
                            "invoice_id" => (int)$params['InvId'],
                            "customer" => [
                                "email" => $transaction['user_email'],
                                "id" => $transaction['user_id'],
                            ],
                            "action" => "subscription",
                        ]);

                        // Update status
                        PaymentsService::updateTransaction([
                            'status' => PaymentsService::ORDER_STATUS_COMPLETE,
                            'transaction_id' => $getData['payment_id'],
                        ]);

                        // Update transaction
                        $transaction = (new \App\Models\TransactionProcess())->transactionInfo(
                            $getData['payment_id'] ?? null
                        );
                    }
                    $this->payments->sendSubscription($transaction);
                    break;
            }

            return "OK" . $params['InvId'];
        } catch (\Exception $e) {
            Log::channel('payments')->error('Error callback result: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * @param array $params
     * @return array|null[]
     */
    public function successPage(array $params): array
    {
        try {
            if (!$this->validate($params)) {
                throw new \Exception('Invalid signature');
            }

            return [
                "id" => $params['InvId'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::channel('payments')->error('Error callback successPage: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * @param array $params
     * @return array|null[]
     */
    public function errorPage(array $params): array
    {
        try {
            if (!$this->validate($params)) {
                throw new \Exception('Invalid signature');
            }

            // Checking transaction
            if ($getTransaction = TransactionProcess::find($params['InvId'])) {
                // Get Transaction
                $transaction = $getTransaction->toArray();

                // Update status
                PaymentsService::updateTransaction([
                    'transaction_id' => $transaction['transaction_id'],
                    'status' => PaymentsService::ORDER_STATUS_CANCEL
                ]);
            }

            return [
                "id" => $transaction['id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::channel('payments')->error('Error callback errorPage: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws GuzzleException
     * @throws \Exception
     */
    public function checkOrderStatus(string $invoiceId): array
    {
        // Ger order data
        $requestUrl = "https://auth.robokassa.ru/Merchant/WebService/Service.asmx/OpStateExt?" .
            http_build_query([
                'MerchantLogin' => $this->merchantLogin,
                'InvoiceID' => $invoiceId,
                'Signature' => $this->signatureMerchant([
                    $invoiceId,
                    $this->password2
                ])
            ]);
        $response = (new Client())->request('GET', $requestUrl);
        $response = simplexml_load_string($response->getBody());
        $orderData = json_decode(
            json_encode((array)$response, JSON_NUMERIC_CHECK),
            true
        );

        // Get state
        if (empty($orderData['Info']['OpKey'])) {
            throw new \Exception("Order not found");
        }

        $requestUrl = 'https://auth.robokassa.ru/Merchant/Payment/GetOpState?opKey=' . $orderData['Info']['OpKey'];
        $response = (new Client())->request('GET', $requestUrl);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param array $params
     * @return string
     */
    private function signatureMerchant(array $params): string
    {
        array_unshift($params, $this->merchantLogin);
        $signatureStr = implode(':', $params);
        return md5($signatureStr);
    }

    /**
     * @param array $params
     * @return string
     */
    private function signatureResult(array $params): string
    {
        $signatureStr = implode(':', $params);
        return md5($signatureStr);
    }
}
