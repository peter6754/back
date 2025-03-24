<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BoughtSubscriptionsRequest;
use App\Models\BoughtSubscriptions;
use App\Models\Secondaryuser;
use App\Models\SubscriptionPackages;
use App\Models\Transactions;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Support\Str;

/**
 * Class BoughtSubscriptionsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BoughtSubscriptionsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\BoughtSubscriptions::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/bought-subscriptions');
        CRUD::setEntityNameStrings('Подписку', 'Подписки');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        $genders = ['m_f', 'm_m', 'f_f'];
        $todayMenSum = Transactions::getTodayTransactionsSumForMen();
        $todayWomenSum = Transactions::getTodayTransactionsSumForWomen();
        $yesterdayMenSum = Transactions::getYesterdayTransactionsSumForMen();
        $yesterdayWomenSum = Transactions::getYesterdayTransactionsSumForWomen();
        $todayOtherSum = Transactions::getTodayTransactionsSumForGenders($genders);
        $yesterdayOtherSum = Transactions::getYesterdayTransactionsSumForGenders($genders);

        Widget::add()->to('before_content')->type('div')->class('row')->content([

            //widget made using fluent syntax
            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value($todayMenSum)
                ->description('Сегодня подписок мужчин на сумму'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value($todayWomenSum)
                ->description('Сегодня подписок женщин на сумму'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value($yesterdayMenSum)
                ->description('Вчера подписок мужчин на сумму'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value($yesterdayWomenSum)
                ->description('Вчера подписок женщин на сумму'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value($todayOtherSum)
                ->description('Сегодня подписок остальных на сумму'),

            Widget::make()
                ->type('progress')
                ->class('card mb-3')
                ->statusBorder('start')
                ->accentColor('green')
                ->ribbon(['top', 'la-ruble-sign'])
                ->progressClass('progressbar')
                ->value($yesterdayOtherSum)
                ->description('Вчера подписок остальных на сумму')
        ]);

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(BoughtSubscriptionsRequest::class);
//        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        $user_id = request()->input('user_id');

        CRUD::addField([
            'name' => 'user_id',
            'type' => 'hidden',
            'value' => $user_id
        ]);

        // Информация о пользователе
        CRUD::addField([
            'name' => 'user_info',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info mb-0">
            Выдаётся пользователю: <strong>'.Secondaryuser::find($user_id)->email.'</strong>
        </div>'
        ]);

        CRUD::addField([
            'name' => 'package_id',
            'label' => 'Пакет',
            'type' => 'select',
            'entity' => 'package',
            'model' => SubscriptionPackages::class,
            'attribute' => 'description',
        ]);

        CRUD::addField([
            'name' => 'due_date',
            'label' => 'Дата окончания',
            'type' => 'datetime',
            'datetime_picker_options' => [
                'format' => 'YYYY-MM-DD HH:mm:ss',
                'language' => 'ru'
            ],
        ]);

    }

    public function store()
    {

        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();

        try {
            \DB::beginTransaction();

            // Генерация UUID
            $transactionId = (string) Str::uuid();

            // 1. Создаем транзакцию
            $transaction = Transactions::create([
                'id' => $transactionId,
                'user_id' => $request->input('user_id'),
                'price' => 0,
                'type' => 'subscription_package',
                'purchased_at' => now(),
                'status' => 'succeeded'
            ]);

            // Проверяем, создана ли транзакция
            if (!$transaction) {
                throw new \Exception('Ошибка при создании транзакции.');
            }

            // 2. Создаем подписку
            BoughtSubscriptions::create([
                'package_id' => $request->input('package_id'),
                'due_date' => $request->input('due_date'),
                'transaction_id' => $transactionId // Используем UUID
            ]);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Alert::error('Ошибка: ' . $e->getMessage())->flash();
            return back()->withInput();
        }

        \Alert::success('Подписка успешно создана')->flash();
        return redirect($this->crud->route);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

}
