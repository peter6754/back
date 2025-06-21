<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MailQueueRequest;

use App\Models\MailQueue;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class MailQueueCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MailQueueCrudController extends CrudController
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
        CRUD::setModel(\App\Models\MailQueue::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mail-queue');
        CRUD::setEntityNameStrings('mail queue', 'mail queues');
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

        CRUD::column('to_email')->label('Получатель');
        CRUD::column('subject')->label('Тема');
        CRUD::column('status')
            ->label('Статус')
            ->type('select_from_array')
            ->options([
                'pending' => 'Ожидает',
                'sent' => 'Отправлено',
                'failed' => 'Ошибка'
            ]);
        CRUD::column('attempts')->label('Попыток');
        CRUD::column('created_at')->label('Создано');
        CRUD::column('sent_at')->label('Отправлено');

        CRUD::filter('status')
            ->type('select2')
            ->label('Статус')
            ->values([
                'pending' => 'Ожидает',
                'sent' => 'Отправлено',
                'failed' => 'Ошибка'
            ]);

        CRUD::addButtonFromView('line', 'resend', 'resend_mail', 'end');

    }

    protected function setupShowOperation()
    {
        CRUD::column('to_email')->label('Email получателя');
        CRUD::column('to_name')->label('Имя получателя');
        CRUD::column('subject')->label('Тема');
        CRUD::column('status')->label('Статус');
        CRUD::column('attempts')->label('Количество попыток');
        CRUD::column('error_message')->label('Ошибка');
        CRUD::column('html_body')->label('HTML тело')->type('textarea');
        CRUD::column('created_at')->label('Создано');
        CRUD::column('sent_at')->label('Отправлено');
    }

    public function resend($id)
    {
        $mail = MailQueue::findOrFail($id);

        if ($mail->status !== 'failed') {
            return back()->with('error', 'Можно переотправить только письма со статусом "Ошибка"');
        }

        $mail->update([
            'status' => 'pending',
            'error_message' => null
        ]);

        return back()->with('success', 'Письмо добавлено в очередь на переотправку');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(MailQueueRequest::class);
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
