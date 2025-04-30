<?php

namespace App\Services;

use Robokassa\Robokassa;

class RobokassaService
{
    /**
     * @var Robokassa
     */
    protected Robokassa $robokassa;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->robokassa = new Robokassa([
            'login' => config('robokassa.merchant_login'),
            'password1' => config('robokassa.password1'),
            'password2' => config('robokassa.password2'),
        ]);
    }

    /**
     * @param $invoiceID
     * @return array
     * @throws \Exception
     */
    public function opState($invoiceID): array
    {
        return $this->robokassa->opState($invoiceID);
    }
}
