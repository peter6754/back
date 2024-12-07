<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TransactionProcessRequest;

use App\Services\Payments\PaymentsService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

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

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\TransactionProcess::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/transaction-process');
        CRUD::setEntityNameStrings('Транзакции', 'Транзакции');
        CRUD::denyAccess(['update', 'delete', 'create']);
        CRUD::orderBy('created_at', 'desc');

        CRUD::filter('Статус')
            ->type('dropdown')
            ->values([
                PaymentsService::ORDER_STATUS_COMPLETE => 'Успешно',
                PaymentsService::ORDER_STATUS_PENDING => 'Ожидание',
                PaymentsService::ORDER_STATUS_CANCEL => 'Закрыто',
            ])
            ->whenActive(function($value) {
                 CRUD::addClause('where', 'status', $value);
            });

        CRUD::filter('Тип')
            ->type('dropdown')
            ->values([
                PaymentsService::ORDER_PRODUCT_SUBSCRIPTION => 'Пакет',
                PaymentsService::ORDER_PRODUCT_SERVICE => 'Регуляное списание',
                PaymentsService::ORDER_PRODUCT_GIFT => 'Подарок',
            ])
            ->whenActive(function($value) {
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
            ->whenActive(function($value) {
                 CRUD::addClause('where', 'email', 'LIKE', "%$value%");
            });

    }

    /**
     * Define what happens when the List operation is loaded.
     *
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
                [   PaymentsService::ORDER_STATUS_COMPLETE => 'Успешно',
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
                [   PaymentsService::ORDER_PRODUCT_SUBSCRIPTION => 'Пакет',
                    PaymentsService::ORDER_PRODUCT_SERVICE => 'Регуляное списание',
                    PaymentsService::ORDER_PRODUCT_GIFT => 'Подарок',
                ]
            );
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
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
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
