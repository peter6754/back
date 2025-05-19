<?php

namespace App\Services\Payments\Providers;

use App\Services\Payments\Contracts\PaymentProviderInterface;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\TransactionRobokassa;
use App\Models\TransactionProcess;
use GuzzleHttp\Client;

class RobokassaProvider implements PaymentProviderInterface
{
    private string $merchantLogin;
    private string $password1;
    private string $password2;

    /**
     *
     */
    public function __construct()
    {
        $this->merchantLogin = config('payments.robokassa.merchant_login');
        $this->password1 = config('payments.robokassa.password1');
        $this->password2 = config('payments.robokassa.password2');
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
            print_r($params);
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
            "price" => $params['price'],
            "id" => $getRobo['invId'],
        ])->toArray();

        $expirationDate = (new \DateTime())->setTimestamp(strtotime("+1 day"));
        $baseUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';
        $queryParams = [
            'MerchantLogin' => $this->merchantLogin,
            'OutSum' => $params['price'],
            'InvId' => $getData['id'],
            'Description' => $params['description'],
            'Email' => $params['customer']['email'],
            'ExpirationDate' => $expirationDate->format('c'),
            'Shp_product' => $params['product'],
            'SignatureValue' => $this->signatureMerchant([
                $params['price'],
                $getData['id'],
                $this->password1,
                "Shp_product=" . $params['product'],
            ])
        ];

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

    public function validate(array $params): bool
    {
        $required = ['InvId', 'OutSum', 'SignatureValue'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            return false;
        }

        $signature = $this->signatureResult([
            $params['OutSum'],
            $params['InvId'],
            $this->password2
        ]);

        return strtolower($params['SignatureValue']) === strtolower($signature);
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
            return [
                "id" => $params['InvId'] ?? null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function checkOrderStatus(string $invoiceId): array
    {
        $requestUrl = "https://auth.robokassa.ru/Merchant/WebService/Service.asmx/OpStateExt";
        $queryParams = [
            'MerchantLogin' => $this->merchantLogin,
            'InvoiceID' => $invoiceId,
            'Signature' => $this->signatureResult([
                $invoiceId,
                $this->password2
            ])
        ];

        $response = (new Client())->request('GET', $requestUrl . '?' . http_build_query($queryParams));
        $response = simplexml_load_string($response->getBody());
        $orderData = json_decode(
            json_encode((array)$response, JSON_NUMERIC_CHECK),
            true
        );
        return $orderData;
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
