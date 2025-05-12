<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\InQueueForDeleteUserRequest;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class InQueueForDeleteUserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InQueueForDeleteUserCrudController extends CrudController
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
        CRUD::setModel(\App\Models\InQueueForDeleteUser::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/in-queue-for-delete-user');
        CRUD::setEntityNameStrings('Очередь на удаление', 'Очередь на удаление');

        CRUD::addColumn([
            'name' => 'user_profile',
            'label' => 'Профиль пользователя',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function($entry) {
                $url = url('/admin/secondaryuser/' . $entry->user_id . '/show');
                return '<a href="' . $url . '" target="_blank">Профиль</a>';
            },
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'closure',
            'function' => function ($entry) {
                return optional($entry->user)->email ?: '—';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('email', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);
        CRUD::addColumn([
            'name' => 'user_phone',
            'label' => 'Телефон',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->user->phone;
            }
        ]);

        CRUD::column('date')->label('Дата удаления');
        CRUD::addColumn([
            'name' => 'time_left_to_delete',
            'label' => 'Осталось до удаления',
            'type' => 'model_function',
            'function_name' => 'getTimeLeftToDelete',
        ]);

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        $this->crud->removeAllColumns();
        CRUD::addColumn([
            'name' => 'user_profile',
            'label' => 'Профиль пользователя',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function($entry) {
                $url = url('/admin/secondaryuser/' . $entry->user_id . '/show');
                return '<a href="' . $url . '" target="_blank">Профиль</a>';
            },
        ]);

        CRUD::column('user_id')
            ->label('ID пользователя')
            ->type('text')
            ->visibleInTable(false)
            ->visibleInShow(false)
            ->visibleInExport(false)
            ->visibleInModal(false)
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('user_id', 'like', '%' . $searchTerm . '%');
            });

        CRUD::addColumn([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'closure',
            'function' => function ($entry) {
                return optional($entry->user)->email ?: '—';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('email', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);
        CRUD::addColumn([
            'name' => 'user_phone',
            'label' => 'Телефон',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->user->phone;
            }
        ]);
        CRUD::addColumn([
            'name' => 'time_when_delete',
            'label' => 'Заказал удаление',
            'type' => 'model_function',
            'function_name' => 'getDateQueuedForDeletion',
        ]);
        CRUD::column('date')->label('Дата удаления');
        $this->crud->query->orderBy('date', 'desc');
        CRUD::addColumn([
            'name' => 'time_left_to_delete',
            'label' => 'Осталось до удаления',
            'type' => 'model_function',
            'function_name' => 'getTimeLeftToDelete',
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
        CRUD::setValidation(InQueueForDeleteUserRequest::class);

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
