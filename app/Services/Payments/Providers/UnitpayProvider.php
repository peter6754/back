<?php

namespace App\Services\Payments\Providers;

use App\Services\Payments\Contracts\PaymentProviderInterface;
use Illuminate\Validation\ValidationException;
use App\Services\Payments\PaymentsService;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\TransactionUnitpay;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionProcess;
use GuzzleHttp\Client;

class UnitpayProvider implements PaymentProviderInterface
{
    private string $publicKey;
    private string $secretKey;
    private $payments;
    private $isTest;

    public function __construct()
    {
        $this->publicKey = config('payments.unitpay.public_key');
        $this->secretKey = config('payments.unitpay.secret_key');
        $this->isTest = config('payments.unitpay.isTest');
        $this->payments = app(PaymentsService::class);
    }

    public function getProviderName(): string
    {
        return 'unitpay';
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

        // Create transaction record
        $transaction = TransactionUnitpay::create([]);

        $processData = TransactionProcess::create([
            "email" => $params['customer']['email'],
            "user_id" => $params['customer']['id'],
            "provider" => $this->getProviderName(),
            "transaction_id" => $transaction->id,
            "type" => $params['product'],
            "price" => $params['price'],
            "id" => $transaction->id // Using same ID for simplicity
        ])->toArray();

        $signature = $this->generateSignature([
            'account' => $processData['id'],
            'currency' => 'RUB',
            'desc' => $params['description'],
            'sum' => $params['price']
        ]);

        $queryParams = [
            'sum' => $params['price'],
            'account' => $processData['id'],
            'desc' => $params['description'],
            'currency' => 'RUB',
            'signature' => $signature,
            'customerEmail' => $params['customer']['email'],
            'customerPhone' => $params['customer']['phone'] ?? null,
            'backUrl' => $params['backUrl'] ?? null,
            'locale' => 'ru',
        ];

        if ($this->isTest) {
            $queryParams['test'] = 1;
        }

        return [
            "confirmation_url" => 'https://unitpay.ru/pay/' . $this->publicKey . '?' . http_build_query($queryParams),
            "created_at" => now()->format('Y-m-d H:i:s'),
            "payment_id" => $processData['transaction_id'],
            "invoice_id" => $processData['id'],
        ];
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

        // Create transaction record
        $transaction = TransactionUnitpay::create([]);

        $processData = TransactionProcess::create([
            "email" => $params['customer']['email'],
            "user_id" => $params['customer']['id'],
            "provider" => $this->getProviderName(),
            "subscription_id" => $params['price'],
            "transaction_id" => $transaction->id,
            "id" => $transaction->id,
            "type" => $params['product']
        ])->toArray();

        // Unitpay requires creating a subscription first
        $response = $this->createSubscription($params, $processData);

        return [
            "confirmation_url" => $response['result']['redirectUrl'] ?? null,
            "created_at" => now()->format('Y-m-d H:i:s'),
            "payment_id" => $processData['transaction_id'],
            "invoice_id" => $processData['id'],
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public function subscription(array $params): array
    {
        $processData = TransactionProcess::firstOrCreate([
            "subscription_id" => $params['subscribeInfo']['subscription_id'] ?? null,
            "email" => $params['customer']['email'],
            "user_id" => $params['customer']['id'],
            "provider" => $this->getProviderName(),
            "type" => $params['product'],
            "price" => $params['price'],
            "id" => $params['invoice_id'],
        ])->toArray();

        return [
            "created_at" => now()->format('Y-m-d H:i:s'),
            "payment_id" => $processData['transaction_id'],
            "invoice_id" => $processData['id'],
            "confirmation_url" => null
        ];
    }

    /**
     * @param array $params
     * @param bool $result
     * @return bool
     */
    public function validate(array $params, bool $result = false): bool
    {
        $required = ['params', 'method', 'params.signature'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            return false;
        }

        $signature = $this->generateCallbackSignature($params['method'], $params['params']);
        return hash_equals($params['params']['signature'], $signature);
    }

    /**
     * @param array $params
     * @return array|string
     * @throws GuzzleException
     */
    public function callbackResult(array $params): array|string
    {
        Log::channel('payments')->info('[' . $this->getProviderName() . '] Result request: ', $params);

        try {
            if (!$this->validate($params)) {
                throw new \Exception('Invalid signature');
            }

            $callbackParams = $params['params'];
            $transactionId = $callbackParams['account'];

            if ($transaction = TransactionProcess::find($transactionId)) {
                $transactionData = $transaction->toArray();

                // If payment already processed
                if ($transactionData['status'] === PaymentsService::ORDER_STATUS_COMPLETE) {
                    return $this->successResponse($transactionId);
                }

                // Update status
                PaymentsService::updateTransaction([
                    'transaction_id' => $transactionData['transaction_id'],
                    'status' => PaymentsService::ORDER_STATUS_COMPLETE
                ]);

                $transaction = (new \App\Models\TransactionProcess())->transactionInfo(
                    $transactionData['transaction_id']
                );

                $this->processPaymentType(
                    $callbackParams['Shp_product'] ?? $transaction['type'] ?? null,
                    $transaction,
                    $callbackParams
                );
            }

            return $this->successResponse($transactionId);
        } catch (\Exception $e) {
            Log::channel('payments')->error('[' . $this->getProviderName() . '] Error callback result: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public function successPage(array $params): array
    {
        try {
            if (isset($params['account'])) {
                return [
                    "id" => $params['account']
                ];
            }
            return [];
        } catch (\Exception $e) {
            Log::channel('payments')->error('[' . $this->getProviderName() . '] Error callback successPage: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public function errorPage(array $params): array
    {
        try {
            if (isset($params['account'])) {
                if ($transaction = TransactionProcess::find($params['account'])) {
                    PaymentsService::updateTransaction([
                        'transaction_id' => $transaction->transaction_id,
                        'status' => PaymentsService::ORDER_STATUS_CANCEL
                    ]);
                }
                return ["id" => $params['account']];
            }
            return [];
        } catch (\Exception $e) {
            Log::channel('payments')->error('[' . $this->getProviderName() . '] Error callback errorPage: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws GuzzleException
     */
    public function checkOrderStatus(string $invoiceId): array
    {
        $params = [
            'paymentId' => $invoiceId
        ];

        $response = $this->makeApiRequest('getPayment', $params);
        return $response['result'] ?? [];
    }

    /**
     * Create subscription in Unitpay
     */
    private function createSubscription(array $params, array $processData): array
    {
        $subscriptionParams = [
            'account' => $processData['id'],
            'sum' => $params['price'],
            'description' => $params['description'] ?? 'Subscription payment',
            'customerEmail' => $params['customer']['email'],
            'subscriptionId' => $processData['id'],
            'period' => $params['period'] ?? 'month',
            'ip' => request()->ip()
        ];

        return $this->makeApiRequest('initSubscription', $subscriptionParams);
    }

    /**
     * Make API request to Unitpay
     */
    private function makeApiRequest(string $method, array $params): array
    {
        $params['secretKey'] = $this->secretKey;
        $signature = $this->generateSignature($params);
        $params['signature'] = $signature;
        unset($params['secretKey']);

        $client = new Client();
        $response = $client->request('GET', 'https://unitpay.ru/api', [
            'query' => [
                'method' => $method,
                'params' => json_encode($params)
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Generate signature for payment requests
     */
    private function generateSignature(array $params): string
    {
        ksort($params);
        unset($params['sign'], $params['signature']);
        return hash('sha256', implode('{up}', $params) . '{up}' . $this->secretKey);
    }

    /**
     * Generate signature for callback validation
     */
    private function generateCallbackSignature(string $method, array $params): string
    {
        ksort($params);
        unset($params['sign'], $params['signature']);
        return hash('sha256', $method . '{up}' . implode('{up}', $params) . '{up}' . $this->secretKey);
    }

    /**
     * Process payment based on product type
     */
    private function processPaymentType(?string $productType, array $transaction, array $params): void
    {
        switch ($productType) {
            case PaymentsService::ORDER_PRODUCT_SERVICE:
                $updateParams = [];
                if (!empty($transaction['package_type']) && !empty($transaction['package_count'])) {
                    $updateParams[$transaction['package_type']] = $transaction['package_count'];
                    $updateParams['user_id'] = $transaction['user_id'];
                    $this->payments->sendServicePackage($updateParams);
                }
                break;

            case PaymentsService::ORDER_PRODUCT_GIFT:
                $this->payments->sendGift($transaction);
                break;

            default:
                $this->payments->sendSubscription($transaction);
                break;
        }
    }

    private function successResponse(string $transactionId): string
    {
        return json_encode([
            "result" => [
                "message" => "OK"
            ]
        ]);
    }

    private function errorResponse(string $message): string
    {
        return json_encode([
            "error" => [
                "message" => $message
            ]
        ]);
    }
}
