<?php

namespace App\Http\Controllers\Payments;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RobokassaController extends Controller
{
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

// robokassa/result?out_summ=0.010000&OutSum=0.010000&inv_id=864243694&InvId=864243694&crc=E057E2BA6B466C0E53546021F6FAD83E&SignatureValue=E057E2BA6B466C0E53546021F6FAD83E&PaymentMethod=BankCard&IncSum=0.010000&IncCurrLabel=BankCardPSBR&EMail=enternetacum@yandex.ru&Fee=0.000000
// robokassa/result?out_summ=0.010000&OutSum=0.010000&inv_id=1343225391&InvId=1343225391&crc=6BFDAB1554FC55CD76ACDA61CA3D51D7&SignatureValue=6BFDAB1554FC55CD76ACDA61CA3D51D7&PaymentMethod=BankCard&IncSum=0.010000&IncCurrLabel=BankCardPSBR&EMail=enternetacum@yandex.ru&Fee=0.000000
//185.59.216.65 - - [03/May/2025:00:01:41 +0300] "GET /robokassa/result?out_summ=0.010000&OutSum=0.010000&inv_id=1796139958&InvId=1796139958&crc=70C0F9C24736A30E388B113A06E6BCB6&SignatureValue=70C0F9C24736A30E388B113A06E6BCB6&PaymentMethod=BankCard&IncSum=0.010000&IncCurrLabel=BankCardPSBR&EMail=enternetacum@yandex.ru&Fee=0.000000 HTTP/1.1" 200 55 "-" ".NET Framework/v4.0.30319"
//185.59.216.65 - - [03/May/2025:00:02:41 +0300] "GET /robokassa/result?out_summ=0.010000&OutSum=0.010000&inv_id=1796139958&InvId=1796139958&crc=70C0F9C24736A30E388B113A06E6BCB6&SignatureValue=70C0F9C24736A30E388B113A06E6BCB6&PaymentMethod=BankCard&IncSum=0.010000&IncCurrLabel=BankCardPSBR&EMail=enternetacum@yandex.ru&Fee=0.000000 HTTP/1.1" 200 55 "-" ".NET Framework/v4.0.30319"
//185.59.216.65 - - [03/May/2025:00:03:42 +0300] "GET /robokassa/result?out_summ=0.010000&OutSum=0.010000&inv_id=1796139958&InvId=1796139958&crc=70C0F9C24736A30E388B113A06E6BCB6&SignatureValue=70C0F9C24736A30E388B113A06E6BCB6&PaymentMethod=BankCard&IncSum=0.010000&IncCurrLabel=BankCardPSBR&EMail=enternetacum@yandex.ru&Fee=0.000000 HTTP/1.1" 200 55 "-" ".NET Framework/v4.0.30319"
//185.59.216.65 - - [03/May/2025:00:04:43 +0300] "GET /robokassa/result?out_summ=0.010000&OutSum=0.010000&inv_id=1796139958&InvId=1796139958&crc=70C0F9C24736A30E388B113A06E6BCB6&SignatureValue=70C0F9C24736A30E388B113A06E6BCB6&PaymentMethod=BankCard&IncSum=0.010000&IncCurrLabel=BankCardPSBR&EMail=enternetacum@yandex.ru&Fee=0.000000 HTTP/1.1" 200 55 "-" ".NET Framework/v4.0.30319"
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

        $getOrder = $this->robokassa->opState($request->InvId);
        print_r($getOrder);

        // 5. Обработка заказа (идемпотентная)
//        try {
//            $order = Transactions::firstOrCreate(
//                ['invoice_id' => $request->InvId],
//                [
//                    'amount' => $request->OutSum,
//                    'status' => 'pending'
//                ]
//            );
//
//            // Если уже оплачен - просто подтверждаем
//            if ($order->status === 'succeeded') {
//                return response("OK{$request->InvId}");
//            }
//
//            // Обновляем статус
//            $order->update([
//                'status' => 'succeeded',
//                'paid_at' => now()
//            ]);
//
//            // Здесь можно добавить:
//            // - Активацию услуги
//            // - Отправку уведомления
//            // - event(new PaymentReceived($order))
//
//        } catch (\Exception $e) {
//            Log::error("Robokassa: Order processing failed - {$e->getMessage()}");
//            abort(500, 'Order processing error');
//        }

        // 6. Обязательный ответ для Robokassa
        return response("OK{$request->InvId}")
            ->header('Content-Type', 'text/plain');
    }
}
