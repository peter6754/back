<?php

namespace App\Services\Payments\Contracts;

interface PaymentProviderInterface
{
    public function getProviderName(): string;

    public function payment(array $params): array;

    public function validate(array $params): bool;

    public function success(array $params): bool;
}
