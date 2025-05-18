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

        if (!backpack_user()->can('access queue-for-delete-user')) {
            abort(403);
        }

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

        $this->crud->addColumn([
            'name' => 'random_image',
            'label' => 'Фотография',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function ($entry) {
                $images = $entry->images;
                if ($images->isNotEmpty()) {
                    $image = $images->first();
                    $imageUrl = $image->image_url;

                    $imageHtml = '
                <img src="' . $imageUrl . '" height="80px" width="60px" style="cursor: pointer;" onclick="openGlobalModal(\'' . $imageUrl . '\')">
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
                    return $imageHtml;
                }
                return 'Нет фото';
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
