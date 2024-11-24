<?php

namespace App\Http\Controllers\Payments;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use App\Services\ExpoNotificationService;
use App\Services\Payments\PaymentsService;

class StatusesController extends Controller
{
    public function __construct(private readonly PaymentsService $payments)
    {

    }

    /**
     * @param Request $request
     * @param string $provider
     * @return JsonResponse|void
     */
    public function resultCallback(Request $request, string $provider)
    {
        $getResults = $this->payments->driver($provider)->callbackResult($request->all());

        if (empty($getResults)) {
            return response()->json([
                'meta' => [
                    'error' => null,
                    'status' => 200
                ],
                'data' => 'Invalid signature'
            ]);
        }
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return object|RedirectResponse
     */
    public function success(Request $request, string $provider)
    {
        // Get current order
        $getOrder = $this->payments->driver($provider)->successPage($request->all());

        if (!empty($getOrder)) {
            return view('payment.success', [
                'orderId' => $getOrder['id'] ?? null
            ]);
        }

        // Redirect to fail
        return redirect()->route('statuses.fail', ['provider' => $provider])
            ->with('error', 'Неверные данные платежа.');
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return Factory|View|Application|object
     */
    public function fail(Request $request, string $provider)
    {
        // Получаем сообщение об ошибке (если было)
        $errorMessage = session('error') ?? 'Платеж не был завершен.';

        // Проверяем Order, если он есть
        $getOrder = $this->payments->driver($provider)->errorPage($request->all());
        $orderId = $getOrder['id'] ?? null;

        return view('payment.fail', [
            'errorMessage' => $errorMessage,
            'orderId' => $orderId
        ]);
    }
}
