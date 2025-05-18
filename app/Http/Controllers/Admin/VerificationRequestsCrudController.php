<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\VerificationRequestsRequest;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class VerificationRequestsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class VerificationRequestsCrudController extends CrudController
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

        if (!backpack_user()->can('access verification-requests')) {
            abort(403);
        }

        CRUD::setModel(\App\Models\VerificationRequests::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/verification-requests');
        CRUD::setEntityNameStrings('Запрос на верификацию', 'Запросы на верификацию');

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

        $this->crud->addColumn([
            'name' => 'user_email',
            'label' => 'Email',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->user->email;
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('email', 'like', '%' . $searchTerm . '%');
                });
            },
        ]);


        $this->crud->addColumn([
            'name' => 'verification_image',
            'label' => 'Фото для верификации',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function ($entry) {
                if (!$entry->image) {
                    return 'Нет фото';
                }

                $imageUrl = $entry->image_url;

                $html = '
            <div style="display: inline-block;">
                <img src="' . $imageUrl . '" height="80px" style="cursor: pointer; border-radius: 5px;" onclick="openGlobalModal(\'' . $imageUrl . '\')">
            </div>

            <script>
                function openGlobalModal(imageUrl) {
                    const modal = document.createElement("div");
                    modal.id = "globalImageModal";
                    modal.style.position = "fixed";
                    modal.style.top = 0;
                    modal.style.left = 0;
                    modal.style.width = "100%";
                    modal.style.height = "100%";
                    modal.style.backgroundColor = "rgba(0, 0, 0, 0.8)";
                    modal.style.display = "flex";
                    modal.style.justifyContent = "center";
                    modal.style.alignItems = "center";
                    modal.style.zIndex = 99999;

                    const closeButton = document.createElement("span");
                    closeButton.innerHTML = "&times;";
                    closeButton.style.position = "absolute";
                    closeButton.style.top = "20px";
                    closeButton.style.right = "30px";
                    closeButton.style.fontSize = "30px";
                    closeButton.style.color = "white";
                    closeButton.style.cursor = "pointer";
                    closeButton.onclick = function() {
                        closeGlobalModal();
                    };

                    const img = document.createElement("img");
                    img.src = imageUrl;
                    img.style.maxWidth = "90%";
                    img.style.maxHeight = "90%";
                    img.style.borderRadius = "5px";

                    modal.appendChild(closeButton);
                    modal.appendChild(img);
                    modal.onclick = function(e) {
                        if (e.target === modal) {
                            closeGlobalModal();
                        }
                    };

                    document.body.appendChild(modal);
                }

                function closeGlobalModal() {
                    const modal = document.getElementById("globalImageModal");
                    if (modal) {
                        document.body.removeChild(modal);
                    }
                }
            </script>
        ';

                return $html;
            },
        ]);

        CRUD::column('status')
            ->label('Статус')
            ->type('select_from_array')
            ->options(['initial' => 'В ожидании', 'approved' => 'Одобрено', 'rejected' => 'Отклонено',]);

        CRUD::column('rejection_reason')
            ->label('Причина отклонения')
            ->type('text');

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

        CRUD::column('status')
            ->label('Статус')
            ->type('select_from_array')
            ->options(['initial' => 'В ожидании', 'approved' => 'Одобрено', 'rejected' => 'Отклонено',]);

        CRUD::column('rejection_reason')
            ->label('Причина отклонения')
            ->type('text');

    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(VerificationRequestsRequest::class);
        CRUD::setFromDb();

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

        CRUD::setValidation([
            'status' => 'required|string',
            'rejection_reason' => 'nullable|string',
        ]);

        CRUD::field('status')->label('Статус')->type('select_from_array')->options([
            'initial' => 'В ожидании',
            'approved' => 'Одобрено',
            'rejected' => 'Отклонено',
        ]);

        CRUD::field('rejection_reason')->label('Rejection Reason')->type('textarea');
    }
}
