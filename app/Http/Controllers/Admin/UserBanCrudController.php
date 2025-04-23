<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserBanRequest;

use App\Models\Secondaryuser;
use App\Models\UserBan;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserBanCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserBanCrudController extends CrudController
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
        CRUD::setModel(\App\Models\UserBan::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user-ban');
        CRUD::setEntityNameStrings('user ban', 'user bans');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        CRUD::column('user.email')->label('Email');
        CRUD::column('user.name')->label('Имя пользователя');
        CRUD::column('is_permanent')
            ->label('Перманентный')
            ->type('boolean')
            ->options([0 => 'Нет', 1 => 'Да']);
        CRUD::column('banned_until')->label('Забанен до');
        CRUD::column('reason')->label('Причина');
        CRUD::column('created_at')->label('Дата создания');

        $this->crud->addFilter([
            'name' => 'is_permanent',
            'type' => 'dropdown',
            'label' => 'Тип бана'
        ], [
            0 => 'Временный',
            1 => 'Перманентный'
        ], function($value) {
            $this->crud->addClause('where', 'is_permanent', $value);
        });

        $this->crud->addButton('line', 'unban', 'view', 'crud::buttons.unban', 'beginning');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
//        CRUD::setValidation(UserBanRequest::class);

        CRUD::setValidation([
            'user_id' => 'required|exists:users,id',
            'is_permanent' => 'boolean',
            'banned_until' => 'nullable|required_if:is_permanent,false|date|after:now',
            'reason' => 'nullable|string|max:500',
        ]);

        $user_id = request()->input('user_id');
        if ($user_id) {
            $user = SecondaryUser::find($user_id);
        }

        CRUD::addField([
            'name' => 'user_id',
            'type' => 'hidden',
            'value' => $user_id
        ]);

        CRUD::field('user_info')
            ->type('custom_html')
            ->value($this->getUserInfoHtml($user));

        CRUD::field('is_permanent')
            ->label('Перманентный бан')
            ->type('checkbox');

        CRUD::field('banned_until')
            ->label('Дата окончания бана')
            ->type('datetime_picker')
            ->hint('Укажите, если бан не перманентный')
            ->wrapper(['class' => 'form-group col-md-6']);

//        CRUD::field('reason')
//            ->label('Причина бана')
//            ->type('textarea');
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

    /**
     * @param $user
     * @return string
     */
    private function getUserInfoHtml($user): string
    {
        if (!$user) {
            return '<div class="alert alert-warning">Пользователь не найден</div>';
        }

        return '
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Информация о пользователе</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>ID:</strong> ' . $user->id . '<br>
                        <strong>Email:</strong> ' . ($user->email ?? 'Не указан') . '<br>
                        <strong>Имя:</strong> ' . ($user->name ?? 'Не указано') . '
                    </div>
                    <div class="col-md-6">
                        <strong>Телефон:</strong> ' . ($user->phone ?? 'Не указан') . '<br>
                        <strong>Статус:</strong> ' . $user->mode . '<br>
                        <strong>Забанен:</strong> ' . ($user->isBanned() ? 'Да' : 'Нет') . '
                    </div>
                </div>
            </div>
        </div>
        ';
    }

    /**
     * @return \Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|object
     */
    public function store()
    {
        $request = $this->crud->validateRequest();

        if ($request->is_permanent && $request->banned_until) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['banned_until' => 'Для перманентного бана нельзя указывать дату окончания.']);
        }

        if (!$request->is_permanent && !$request->banned_until) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['banned_until' => 'Для временного бана необходимо указать дату окончания.']);
        }

        $user = SecondaryUser::find($request->user_id);
        if ($user) {
            $user->ban(
                $request->is_permanent,
                $request->banned_until,
                $request->reason ?? 'Нарушение правил приложения'
            );

            \Alert::success('Пользователь успешно забанен.')->flash();
            return redirect(backpack_url('user-ban'));
        }

        \Alert::error('Ошибка при бане пользователя.')->flash();
        return redirect()->back();
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * Снятие бана
     */
    public function unban($id)
    {
        $userBan = UserBan::findOrFail($id);
        $user = $userBan->user;

        $user->unban();

        \Alert::success('Пользователь разбанен.')->flash();
        return redirect()->back();
    }
}
