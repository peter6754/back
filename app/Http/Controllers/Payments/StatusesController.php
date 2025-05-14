<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payments\PaymentsService;

class StatusesController extends Controller
{
    public function __construct(private PaymentsService $payments)
    {

    }

    public function resultCallback(Request $request, string $provider)
    {
        if (!$this->payments->validate($provider, $request->all())) {
            \Log::error("Invalid {$provider} callback", $request->all());
            return response()->json(['code' => 1, 'message' => 'Invalid signature'], 400);
        }

//        $this->payments->
    }

    public function success(Request $request, string $provider)
    {

    }

    public function failed(Request $request, string $provider)
    {

    }


    public function status(Request $request, string $provider)
    {

    }
}
