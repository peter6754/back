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

        // Для отображения в таблице (списке)
        CRUD::column('id')->label('ID');
        CRUD::column('name')->label('Имя');
        $this->crud->addColumn([
            'name' => 'random_image',
            'label' => 'Фотография',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function ($entry) {
                $images = $entry->images;
                if ($images->isNotEmpty()) {
                    $randomImage = $images->random();
                    return '<img src="' . $randomImage->image_url . '" height="60px" width="60px">';
                }
                return 'Нет фото';
            },
        ]);
        CRUD::column('username')->label('Имя пользователя');
        CRUD::column('phone')->label('Телефон');
        CRUD::column('email')->label('Электронная почта');
        CRUD::column('birth_date')->label('Дата рождения');
        CRUD::column('age')->label('Возраст');
        CRUD::column('gender')
            ->label('Пол')
            ->type('select_from_array')
            ->options(['male' => 'Мужчина', 'female' => 'Женщина'])
            ->wrapper([
                'class' => function ($crud, $column, $entry, $related_key) {
                    return $entry->gender === 'male' ? 'text-primary' : 'text-danger';
                },
            ]);
        CRUD::column('sexual_orientation')
            ->label('Сексуальная ориентация')
            ->type('select_from_array')
            ->options([
                'hetero' => 'Гетеро',
                'gay' => 'Гей',
                'lesbian' => 'Лесбиянка',
                'bisexual' => 'Бисексуал',
                'asexual' => 'Асексуал',
                'not_decided' => 'Не решено'
            ]);
        CRUD::column('mode')
            ->label('Режим')
            ->type('select_from_array')
            ->options([
                'authenticated' => 'Аутентифицирован',
                'deleted' => 'Удалён'
            ]);
        CRUD::column('registration_date')->label('Дата регистрации');
        CRUD::column('last_check')->label('Последняя проверка');
        CRUD::column('is_online')->label('Онлайн статус');

        CRUD::addField([
            'name' => 'name',
            'label' => 'Имя',
            'type' => 'text'
        ]);

        CRUD::addField([
            'name' => 'username',
            'label' => 'Имя пользователя',
            'type' => 'text'
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => 'Телефон',
            'type' => 'text'
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => 'Электронная почта',
            'type' => 'email'
        ]);

        CRUD::addField([
            'name' => 'birth_date',
            'label' => 'Дата рождения',
            'type' => 'date'
        ]);

        CRUD::addField([
            'name' => 'age',
            'label' => 'Возраст',
            'type' => 'number'
        ]);

        CRUD::addField([
            'name' => 'gender',
            'label' => 'Пол',
            'type' => 'select_from_array',
            'options' => ['male' => 'Мужчина', 'female' => 'Женщина']
        ]);

        CRUD::addField([
            'name' => 'sexual_orientation',
            'label' => 'Сексуальная ориентация',
            'type' => 'select_from_array',
            'options' => ['hetero' => 'Гетеро', 'gay' => 'Гей', 'lesbian' => 'Лесбиянка', 'bisexual' => 'Бисексуал', 'asexual' => 'Асексуал', 'not_decided' => 'Не решено']
        ]);

        CRUD::addField([
            'name' => 'mode',
            'label' => 'Режим',
            'type' => 'select_from_array',
            'options' => ['authenticated' => 'Аутентифицирован', 'deleted' => 'Удалён']
        ]);

        CRUD::addField([
            'name' => 'registration_date',
            'label' => 'Дата регистрации',
            'type' => 'datetime_picker'
        ]);

        CRUD::addField([
            'name' => 'last_check',
            'label' => 'Последняя проверка',
            'type' => 'datetime_picker'
        ]);

        CRUD::addField([
            'name' => 'is_online',
            'label' => 'Онлайн статус',
            'type' => 'boolean'
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
//        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        CRUD::column('id')->label('ID');
        $this->crud->addColumn([
            'name' => 'images', // указываем на отношение
            'label' => 'User Images',
            'type' => 'custom_html',
            'escaped' => false, // чтобы HTML не экранировался
            'value' => function($entry) {
                // Собираем все изображения пользователя
                $imagesHtml = '';
                foreach ($entry->images as $image) {
                    $imagesHtml .= '<img src="' . $image->image_url . '" height="60px" width="60px" style="margin-right: 5px;">';
                }
                return $imagesHtml;
            },
        ]);
        CRUD::column('name')->label('Имя');
        CRUD::column('username')->label('Логин');
        CRUD::column('phone')->label('Телефон');
        CRUD::column('email')->label('Почта');
        CRUD::column('birth_date')->label('День рождения');
        CRUD::column('age')->label('Возраст');
        CRUD::column('gender')->label('Пол');
        CRUD::column('sexual_orientation')->label('Ориентация');
        CRUD::column('mode')->label('Режим');
        CRUD::column('registration_date')->label('Дата регистрации');
        CRUD::column('last_check')->label('Последняя проверка');
        CRUD::column('is_online')->label('Онлайн статус');
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
