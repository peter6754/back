<?php

namespace App\Http\Controllers\Payments;

use Illuminate\Support\Facades\Log;
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
        // Проверяем, что InvId корректен
        try {
            $request->validate([
                'InvId' => 'required|numeric',
            ]);

            $orderId = $request->input('InvId');

            return view('payment.success', ['orderId' => $orderId]);

        } catch (\Exception $e) {
            // Логируем ошибку
            Log::error('Robokassa Success Error: ' . $e->getMessage(), $request->all());

            // Перенаправляем на страницу ошибки с сообщением
            return redirect()
                ->route('robokassa.fail')
                ->with('error', 'Неверные данные платежа.');
        }
    }

    public function fail(Request $request, string $provider)
    {
        // Получаем сообщение об ошибке (если было)
        $errorMessage = session('error') ?? 'Платеж не был завершен.';

        // Проверяем InvId, если он есть
        $orderId = null;
        if ($request->has('InvId') && is_numeric($request->input('InvId'))) {
            $orderId = $request->input('InvId');
        }

        return view('payment.fail', [
            'errorMessage' => $errorMessage,
            'orderId' => $orderId
        ]);
    }
}
