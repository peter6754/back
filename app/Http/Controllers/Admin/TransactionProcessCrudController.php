<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TransactionProcessRequest;

use App\Models\BoughtSubscriptions;
use App\Models\TransactionProcess;
use App\Models\Transactions;
use App\Services\Payments\PaymentsService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Carbon\Carbon;

/**
 * Class TransactionProcessCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TransactionProcessCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\InlineCreateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\TransactionProcess::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/transaction-process');
        CRUD::setEntityNameStrings('Транзакции', 'Транзакции');
        CRUD::denyAccess(['update', 'delete', 'create']);
        CRUD::orderBy('created_at', 'desc');

        $today = TransactionProcess::getTodaySubscriptionsStats();
//        $genders = ['m_f', 'm_m', 'f_f'];
        $todayMen = Transactions::getTodayTransactionsSumForMen();
        $todayWomen = Transactions::getTodayTransactionsSumForWomen();
        $yesterdayMen = Transactions::getYesterdayTransactionsSumForMen();
        $yesterdayWomen = Transactions::getYesterdayTransactionsSumForWomen();
//        $todayOther = Transactions::getTodayTransactionsSumForGenders($genders);
//        $yesterdayOther = Transactions::getYesterdayTransactionsSumForGenders($genders);
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');
        $todayEnd = Carbon::now('Europe/Moscow')->endOfDay()->setTimezone('UTC');
        $yesterday = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');
        $todayExpired = BoughtSubscriptions::getExpiredSubscriptionsStats($todayStart, $todayEnd);
        $yesterdayExpired = BoughtSubscriptions::getExpiredSubscriptionsStats($yesterday, $todayStart);

        Widget::add()->to('before_content')->type('div')->class('row')->content([
            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value("Куплено подписок: {$today['count']} <br>Сумма: {$today['total']} ₽")
                ->description('Статистика подписок за сегодня (новый метод)'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value("Ж: $todayWomen->count на сумму: $todayWomen->sum <br>
                         М: $todayMen->count на сумму: $todayMen->sum <br> Итого: " . ($todayWomen->sum + $todayMen->sum))
                ->description('Сегодня подключили подписок'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value("Ж: $yesterdayWomen->count на сумму: $yesterdayWomen->sum <br>
                         М: $yesterdayMen->count на сумму: $yesterdayMen->sum <br> Итого: " . ($yesterdayWomen->sum + $yesterdayMen->sum))
                ->description('Вчера подключили подписок'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value("Ж: {$todayExpired->count_women} на сумму: {$todayExpired->total_sum_women} <br>
                         М: {$todayExpired->count_men} на сумму: {$todayExpired->total_sum_men} <br> Итого: " . ($todayExpired->total_sum_women + $todayExpired->total_sum_men))
                ->description('Сегодня закончилось подписок'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value("Ж: {$yesterdayExpired->count_women} на сумму: {$yesterdayExpired->total_sum_women} <br>
                         М: {$yesterdayExpired->count_men} на сумму: {$yesterdayExpired->total_sum_men} <br> Итого: " . ($yesterdayExpired->total_sum_women + $yesterdayExpired->total_sum_men))
                ->description('Вчера закончилось подписок'),
        ]);


        CRUD::addColumn([
            'name' => 'user_profile',
            'label' => 'Профиль',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function ($entry) {
                $url = url('/admin/secondaryuser/' . $entry->user_id . '/show');
                return '<a href="' . $url . '" target="_blank">Профиль</a>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'subscription_info',
            'label' => 'Подписка',
            'type' => 'closure',
            'function' => function ($entry) {
                $package = optional($entry->boughtSubscription)->package;
                $subscription = optional($package)->subscription;
                $term = optional($package)->term;

                if (!$subscription || !$term) return '—';

                $termMap = [
                    'one_month' => '1 мес',
                    'six_months' => '6 мес',
                    'year' => '12 мес',
                ];

                return $subscription->type . ' (' . ($termMap[$term] ?? $term) . ')';
            }
        ]);

        CRUD::filter('id')->type('text')->whenActive(function ($value) {
            CRUD::addClause('where', 'id', 'LIKE', "$value");
        });

        CRUD::filter('Статус')
            ->type('dropdown')
            ->values([
                PaymentsService::ORDER_STATUS_COMPLETE => 'Успешно',
                PaymentsService::ORDER_STATUS_PENDING => 'Ожидание',
                PaymentsService::ORDER_STATUS_CANCEL => 'Закрыто',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'status', $value);
            });

        CRUD::filter('Тип')
            ->type('dropdown')
            ->values([
                PaymentsService::ORDER_PRODUCT_SUBSCRIPTION => 'Подписка',
                PaymentsService::ORDER_PRODUCT_SERVICE => 'Пакет сервис',
                PaymentsService::ORDER_PRODUCT_GIFT => 'Подарок',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'type', $value);
            });

        CRUD::filter('created_at')
            ->type('date_range')
            ->label('Создано')
            ->date_range_options([
                'timePicker' => true,
                'locale' => ['format' => 'YYYY-MM-DD HH:mm:ss'],
            ])
            ->whenActive(function ($value) {
                $dates = json_decode($value);
                if (!empty($dates->from) && !empty($dates->to)) {
                    CRUD::addClause('where', 'created_at', '>=', $dates->from);
                    CRUD::addClause('where', 'created_at', '<=', $dates->to);
                }
            });

        CRUD::filter('email')
            ->type('text')
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'email', 'LIKE', "%$value%");
            });

        CRUD::filter('term')
            ->type('dropdown')
            ->label('Длительность подписки')
            ->values([
                'one_month' => '1 мес',
                'six_months' => '6 мес',
                'year' => '12 мес',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('whereHas', 'boughtSubscription.package', function ($q) use ($value) {
                    $q->where('term', $value);
                });
            });

        CRUD::filter('type')
            ->type('dropdown')
            ->label('Название пакета')
            ->values([
                'Tinderone Plus+' => 'Tinderone Plus+',
                'Tinderone Gold' => 'Tinderone Gold',
                'Tinderone Premium' => 'Tinderone Premium',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('whereHas', 'boughtSubscription.package.subscription', function ($q) use ($value) {
                    $q->where('type', $value);
                });
            });

        CRUD::button('')->stack('line')->view('crud::buttons.quick')->meta([
            'icon' => 'la la-user-times',
            'class' => 'btn btn-primary',
            'access' => true,
            'wrapper' => [
                'title' => 'Unsubscribe user',
                'target' => '_blank',
                'element' => 'a',
                'href' => function ($entry) {
                    if (empty($entry->subscription_id) || empty($entry->subscriber_id)) {
                        return "javascript:alert('Это не подписка');";
                    }
                    $buildParams = http_build_query([
                        "SubscriptionId" => $entry->subscription_id,
                        "SubscriberId" => $entry->subscriber_id,
                    ]);
                    return url('https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/Unsubscribe?' . $buildParams);

                },
//                'href' => url('https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/Unsubscribe?SubscriptionId='.$entry->sub.'&SubscriberId=e5e44d67-0691-4432-8f03-6bab9424f552'),
            ]
        ]);

    }

    /**
     * Define what happens when the List operation is loaded.
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
//        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */

        CRUD::column('id')->type('number')->label('ID Заказа');
        CRUD::column('subscription_id')->label('subscription_id')->visibleInTable(false)->visibleInShow(true);
        CRUD::column('subscriber_id')->label('subscriber_id')->visibleInTable(false)->visibleInShow(true);
        CRUD::column('transaction_id')->label('transaction_id')->visibleInTable(false)->visibleInShow(true);
        CRUD::column('provider')->type('text')->label('Провайдер');
        CRUD::column('user_id')->label('ID пользователя')->visibleInTable(false)->visibleInShow(true);
        CRUD::column('price')->type('number')->label('Сумма');
        CRUD::column('status')
            ->label('Статус')
            ->type('select_from_array')
            ->options(
                [PaymentsService::ORDER_STATUS_COMPLETE => 'Успешно',
                    PaymentsService::ORDER_STATUS_PENDING => 'Ожидание',
                    PaymentsService::ORDER_STATUS_CANCEL => 'Закрыто',
                ]
            );
        CRUD::column('email')->type('email')->label('Email');
        CRUD::column('purchased_at')->type('datetime')->label('Куплено');
        CRUD::column('updated_at')->type('datetime')->label('Обновлено');
        CRUD::column('created_at')->type('datetime')->label('Создано');
        CRUD::column('type')
            ->label('Тип')
            ->type('select_from_array')
            ->options(
                [PaymentsService::ORDER_PRODUCT_SUBSCRIPTION => 'Подписка',
                    PaymentsService::ORDER_PRODUCT_SERVICE => 'Сервсис пакет',
                    PaymentsService::ORDER_PRODUCT_GIFT => 'Подарок',
                ]
            );

    }

    /**
     * Define what happens when the Create operation is loaded.
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(TransactionProcessRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        $this->setupCreateOperation();
    }
}
