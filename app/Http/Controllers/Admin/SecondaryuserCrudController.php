<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SecondaryuserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SecondaryuserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
 

class SecondaryuserCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Secondaryuser::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/secondaryuser');
        CRUD::setEntityNameStrings('Пользователь', 'Пользователи');
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
        //        CRUD::column('id');
        CRUD::column('name');
        CRUD::column('username');
        CRUD::column('phone');
        CRUD::column('email');
        CRUD::column('birth_date');
        CRUD::column('age');
        CRUD::column('gender');
        CRUD::column('sexual_orientation');
        CRUD::column('mode');
        CRUD::column('registration_date');
        CRUD::column('last_check');
        CRUD::column('is_online');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
//        CRUD::setValidation(SecondaryuserRequest::class);
//        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        CRUD::setValidation(SecondaryuserRequest::class);

        CRUD::field('name');
        CRUD::field('username');
        CRUD::field('phone');
        CRUD::field('email');
        CRUD::field('birth_date')->type('date');
        CRUD::field('lat');
        CRUD::field('long');
        CRUD::field('age');
        CRUD::field('gender')->type('enum');
        CRUD::field('sexual_orientation')->type('enum');
        CRUD::field('mode')->type('enum');
        CRUD::field('registration_date')->type('datetime');
        CRUD::field('last_check')->type('datetime');
        CRUD::field('is_online')->type('boolean');
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
