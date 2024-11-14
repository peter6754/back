<?php

namespace App\Services\Payments\Providers;

use GuzzleHttp\Client;
use mysql_xdevapi\Exception;
use GuzzleHttp\Exception\GuzzleException;
use App\Services\Payments\Contracts\PaymentProviderInterface;
use Illuminate\Validation\ValidationException;
use App\Models\TransactionRobokassa;

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
        $getData = TransactionRobokassa::create([])->toArray();
        $params['invoiceId'] = $getData['invId'];

        $required = ['amount', 'email'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            throw ValidationException::withMessages([
                'payment' => 'Missing required payment parameters'
            ]);
        }

        $recurrentUrl = "https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/SubscriberGetOrCreate";
        $recurrentParams = [
            'subscriptionId' => $params['amount'],
            'email' => $params['email']
        ];
        $response = (new Client())->request('POST', $recurrentUrl . '?' . http_build_query($recurrentParams));
        $queryParams = json_decode($response->getBody(), true);

        $baseUrl = 'https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/Subscribe';
        $baseParams = [
            'subscriberId' => $queryParams['subscriberId'],
            'subscriptionId' => $params['amount']
        ];

        return [
            "confirmation_url" => $baseUrl . '?' . http_build_query($baseParams),
            "created_at" => (new \DateTime())->format('Y-m-d H:i:s'),
            "invoice_id" => $getData['invId'],
            "payment_id" => $getData['id']
        ];
    }

    /**
     * @param array $params
     * @return array
     * @throws ValidationException
     */
    public function payment(array $params): array
    {
        $getData = TransactionRobokassa::create([])->toArray();
        $params['invoiceId'] = $getData['invId'];

        $required = ['invoiceId', 'amount', 'description', 'email'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            throw ValidationException::withMessages([
                'payment' => 'Missing required payment parameters'
            ]);
        }

        $baseUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';

        $signature = $this->signatureOrder(
            $params['amount'],
            $params['invoiceId'],
            $this->password1
        );

        $expirationDate = new \DateTime();
        $expirationDate->setTimestamp(strtotime("+1 day"));

        $queryParams = [
            'MerchantLogin' => $this->merchantLogin,
            'OutSum' => $params['amount'],
            'InvId' => $params['invoiceId'],
            'Description' => $params['description'],
            'SignatureValue' => $signature,
            'Email' => $params['email'],
            'ExpirationDate' => $expirationDate->format('c')
        ];

        if (isset($params['currency'])) {
            $queryParams['OutSumCurrency'] = $params['currency'];
        }

        if (isset($params['recurring'])) {
            $queryParams['Recurring'] = "true";
        }

        return [
            "confirmation_url" => $baseUrl . '?' . http_build_query($queryParams),
            "created_at" => (new \DateTime())->format('Y-m-d H:i:s'),
            "invoice_id" => $getData['invId'],
            "payment_id" => $getData['id'],
        ];
    }

    public function validate(array $params): bool
    {
        $required = ['InvId', 'OutSum', 'SignatureValue'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            return false;
        }

        $signature = $this->signatureResult(
            $params['OutSum'],
            $params['InvId'],
            $this->password2
        );

        return strtolower($params['SignatureValue']) === strtolower($signature);
    }

    public function success(array $params): bool
    {
        $required = ['InvId', 'OutSum', 'SignatureValue'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            return false;
        }

        $signature = $this->signatureOrder(
            $params['OutSum'],
            $params['InvId'],
            $this->password1
        );

        return strtolower($params['SignatureValue']) === strtolower($signature);
    }

    public function checkOrderStatus(string $invoiceId): array
    {
        $requestUrl = "https://auth.robokassa.ru/Merchant/WebService/Service.asmx/OpStateExt";
        $queryParams = [
            'MerchantLogin' => $this->merchantLogin,
            'InvoiceID' => $invoiceId,
            'Signature' => $this->signatureState(
                $invoiceId,
                $this->password2
            )
        ];

        $response = (new Client())->request('GET', $requestUrl . '?' . http_build_query($queryParams));
        $response = simplexml_load_string($response->getBody());
        return json_decode(json_encode((array)$response, JSON_NUMERIC_CHECK), true);
    }

    private function signatureResult(string $amount, string $invoiceId, string $password): string
    {
        $signatureStr = implode(':', [
            $amount,
            $invoiceId,
            $password,
        ]);

        return md5($signatureStr);
    }

    private function signatureOrder(string $amount, string $invoiceId, string $password): string
    {
        $signatureStr = implode(':', [
            $this->merchantLogin,
            $amount,
            $invoiceId,
            $password,
        ]);

        return md5($signatureStr);
    }

    private function signatureState(string $invoiceId, string $password): string
    {
        $signatureStr = implode(':', [
            $this->merchantLogin,
            $invoiceId,
            $password
        ]);

        return md5($signatureStr);
    }

    private function validateParams(array $params, array $required): void
    {
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            throw ValidationException::withMessages([
                'payment' => 'Missing required payment parameters: ' . implode(', ', $required)
            ]);
        }
    }
}
