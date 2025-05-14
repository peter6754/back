<?php

namespace App\Services\Payments\Providers;

use App\Services\Payments\Contracts\PaymentProviderInterface;
use Illuminate\Validation\ValidationException;
use App\Models\TransactionRobokassa;

class RobokassaProvider implements PaymentProviderInterface
{
    private string $merchantLogin;
    private string $password1;
    private string $password2;
    private string $hashAlgo;

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
     */
    public function pay(array $params): array
    {
        $getData = TransactionRobokassa::create([])->toArray();
        $params['invoiceId'] = $getData['invId'];
        $params['amount'] = (int)$params['amount'];

        $required = ['invoiceId', 'amount', 'description'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            throw ValidationException::withMessages([
                'payment' => 'Missing required payment parameters'
            ]);
        }

        $baseUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';

        $signature = $this->generateSignature(
            $params['invoiceId'],
            $params['amount'],
            $this->password1
        );

        $expirationDate = new \DateTime();
        $expirationDate->setTimestamp(strtotime("+10 minutes"));

        $formattedDate = $expirationDate->format('Y-m-d H:i:s');

        $queryParams = [
            'MerchantLogin' => $this->merchantLogin,
            'OutSum' => $params['amount'],
            'InvId' => $params['invoiceId'],
            'Description' => $params['description'],
            'SignatureValue' => $signature,
            'Email' => $params['email'],
            'ExpirationDate' => $formattedDate
        ];

        if (isset($params['currency'])) {
            $queryParams['OutSumCurrency'] = $params['currency'];
        }

        return [
            "created_at" => (new \DateTime())->format('Y-m-d H:i:s'),
            "url" => $baseUrl . '?' . http_build_query($queryParams),
            "id" => $getData['id'],
        ];
    }

    public function validate(array $params): bool
    {
        $required = ['InvId', 'OutSum', 'SignatureValue'];
        if (count(array_intersect_key(array_flip($required), $params)) !== count($required)) {
            return false;
        }

        $signature = $this->generateSignature(
            $params['InvId'],
            $params['OutSum'],
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

        $signature = $this->generateSignature(
            $params['InvId'],
            $params['OutSum'],
            $this->password1
        );

        return strtolower($params['SignatureValue']) === strtolower($signature);
    }

    private function generateSignature(string $invoiceId, float $amount, string $password): string
    {
        $signatureStr = implode(':', [
            $this->merchantLogin,
            $amount,
            $invoiceId,
            $password,
        ]);

        return hash('md5', $signatureStr);
    }
}
