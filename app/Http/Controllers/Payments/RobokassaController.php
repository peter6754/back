<?php

namespace App\Http\Controllers\Payments;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use App\Services\RobokassaService;
use Illuminate\Http\Request;
use App\Models\Transactions;

class RobokassaController extends Controller
{
    /**
     * @var RobokassaService
     */
    protected RobokassaService $robokassa;

    /**
     *
     */
    public function __construct()
    {
        $this->robokassa = new RobokassaService();
    }

    /**
     * SuccessURL — пользователь вернулся после успешной оплаты
     * @param Request $request
     * @return Factory|View|Application|RedirectResponse|object
     */
    public function success(Request $request)
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

    /**
     * FailURL — пользователь вернулся при отмене платежа
     * @param Request $request
     * @return Factory|View|Application|object
     */
    public function fail(Request $request)
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

    public function result(Request $request)
    {
        // 1. Логирование входящего запроса
        Log::channel('robokassa')->info('GET ResultURL callback:', $request->all());

        // 2. Проверка обязательных параметров
        $requiredParams = ['InvId', 'OutSum', 'SignatureValue'];
        if (!$request->has($requiredParams)) {
            Log::error('Robokassa: Missing parameters', ['received' => $request->all()]);
            abort(400, 'Required parameters: ' . implode(', ', $requiredParams));
        }

        // 3. Валидация типа данных
        if (!is_numeric($request->InvId) || !is_numeric($request->OutSum)) {
            Log::error('Robokassa: Invalid parameter types', [
                'InvId' => $request->InvId,
                'OutSum' => $request->OutSum
            ]);
            abort(422, 'Invalid parameter types');
        }

        // 4. Проверка подписи (md5(OutSum:InvId:Password2))
        $signature = strtolower(md5(sprintf('%s:%s:%s',
            $request->OutSum,
            $request->InvId,
            config('robokassa.password2') // Ваш Pass2 из настроек
        )));

        if (strtolower($request->SignatureValue) !== $signature) {
            Log::error('Robokassa: Signature mismatch', [
                'received' => $request->SignatureValue,
                'calculated' => $signature
            ]);
            abort(403, 'Invalid signature');
        }

        // 5. Обработка заказа (идемпотентная)
        try {
            $order = Transactions::firstOrCreate(
                ['invoice_id' => $request->InvId],
                [
                    'amount' => $request->OutSum,
                    'status' => 'pending'
                ]
            );

            // Если уже оплачен - просто подтверждаем
            if ($order->status === 'succeeded') {
                return response("OK{$request->InvId}");
            }

            // Обновляем статус
            $order->update([
                'status' => 'succeeded',
                'paid_at' => now()
            ]);

            // Здесь можно добавить:
            // - Активацию услуги
            // - Отправку уведомления
            // - event(new PaymentReceived($order))

        } catch (\Exception $e) {
            Log::error("Robokassa: Order processing failed - {$e->getMessage()}");
            abort(500, 'Order processing error');
        }

        // 6. Обязательный ответ для Robokassa
        return response("OK{$request->InvId}")
            ->header('Content-Type', 'text/plain');
    }
}
