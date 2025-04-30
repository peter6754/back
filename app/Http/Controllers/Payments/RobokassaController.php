<?php

namespace App\Http\Controllers\Payments;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use App\Services\RobokassaService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

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

    /**
     * ResultURL — асинхронный callback от Robokassa (POST!)
     * @param Request $request
     * @return ResponseFactory|Application|Response|object
     * @throws \Exception
     */
    public function result(Request $request)
    {
        $orderId = $request->input('InvId');
        $amount = $request->input('OutSum');

        $response = $this->robokassa->opState($orderId);
        if (!empty($response['State']['Code']) && $response['State']['Code'] == 100) {
            echo "SUCCESS";
        }

        // Обновляем статус заказа в БД (например: $order->markAsPaid())
        // ...

        // Обязательный ответ для Robokassa (иначе будет повторный запрос)
        return response("OK{$orderId}", 200);
    }


    public function handleResult(Request $request)
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
            $order = Transaction::firstOrCreate(
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


    /**
     * Создание платежа и редирект на Robokassa
     * @param Request $request
     * @return RedirectResponse
     */
    public function createPayment(Request $request)
    {
        $amount = 1000; // Сумма платежа
        $orderId = 123; // ID заказа в вашей системе
        $description = 'Оплата заказа #' . $orderId;
        $email = $request->user()->email; // Email пользователя (если нужен)

        // Генерируем URL для оплаты
        $paymentUrl = $this->robokassa->getPaymentUrl(
            amount: $amount,
            invoiceId: $orderId,
            description: $description,
            email: $email
        );

        // Редирект на Robokassa
        return redirect()->away($paymentUrl);
    }
}
