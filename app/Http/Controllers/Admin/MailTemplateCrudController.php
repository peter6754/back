<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MailTemplateRequest;

use App\Models\MailTemplate;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class MailTemplateCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MailTemplateCrudController extends CrudController
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
        CRUD::setModel(\App\Models\MailTemplate::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mail-template');
        CRUD::setEntityNameStrings('mail template', 'mail templates');
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
        CRUD::column('name')->label('Название');
        CRUD::column('subject')->label('Тема письма');
        CRUD::column('is_active')->label('Активен')->type('boolean');
        CRUD::column('created_at')->label('Создан');

        CRUD::addButtonFromView('line', 'preview', 'preview_template', 'beginning');

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(MailTemplateRequest::class);
//        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        CRUD::setValidation(MailTemplateRequest::class);

        CRUD::field('name')
            ->label('Название')
            ->hint('Уникальное название шаблона для использования в коде');

        CRUD::field('subject')
            ->label('Тема письма')
            ->hint('Можно использовать переменные: {{variable_name}}');

        CRUD::field('html_body')
            ->label('HTML тело письма')
            ->type('textarea')
            ->attributes(['rows' => 15])
            ->hint('HTML код письма. Переменные: {{variable_name}}');

        CRUD::field('text_body')
            ->label('Текстовая версия')
            ->type('textarea')
            ->attributes(['rows' => 10])
            ->hint('Опционально. Текстовая версия письма');

        CRUD::field('variables')
            ->label('Доступные переменные')
            ->type('textarea')
            ->attributes(['rows' => 5])
            ->hint('JSON список доступных переменных, например: ["name", "email", "date"]');

        CRUD::field('is_active')
            ->label('Активен')
            ->type('boolean')
            ->default(true);

    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::column('html_body')->label('HTML код');
        CRUD::column('text_body')->label('Текстовая версия');
        CRUD::column('variables')->label('Переменные');
    }

    public function preview($id)
    {
        $template = MailTemplate::findOrFail($id);

        return view('admin.mail_template_preview', compact('template'));
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
