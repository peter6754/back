<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdvertisementRequest;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class AdvertisementCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Advertisement::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/advertisement');
        CRUD::setEntityNameStrings('реклама', 'реклама');

        CRUD::addButtonFromView('line', 'manage_photos', 'advertisement_manage_photos', 'beginning');
    }

    protected function setupListOperation()
    {

        CRUD::addColumn([
            'name' => 'primary_image',
            'label' => 'Фото',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function ($entry) {
                $images = $entry->images;
                $html = '';

                if ($images->isNotEmpty()) {
                    $count = $images->count();
                    $width = $count * 70;

                    $html .= '<div style="display: flex; width: '.$width.'px; gap: 5px; margin-bottom: 10px;">';

                    foreach ($images as $idx => $image) {
                        $service = app(\App\Services\AdvertisementService::class);
                        $advertisementImages = $service->getAdvertisementImagesWithUrls($entry);

                        if (!empty($advertisementImages)) {
                            $proxyUrl = url("admin/image-proxy/{$advertisementImages[$idx]['fid']}");

                            $allUrls = collect($advertisementImages)->pluck('fid')->map(function($fid) {
                                return url("admin/image-proxy/{$fid}");
                            })->toArray();

                            $isMain = $image->is_primary ?? false;

                            $mainIndicator = $isMain ?
                                '<div style="position: absolute; top: 2px; right: 2px; background: gold; color: black; padding: 1px 4px; font-size: 9px; border-radius: 2px; font-weight: bold;">первое</div>' : '';

                            $borderColor = $isMain ? '#ffd700' : '#ddd';

                            $html .= '
                    <div style="position: relative; flex-shrink: 0;">
                        <img src="'.$proxyUrl.'" height="80px" width="60px"
                             style="cursor: pointer; border: 2px solid '.$borderColor.'; border-radius: 4px; object-fit: cover;"
                             onclick="openGlobalModal('.htmlspecialchars(json_encode($allUrls)).', '.$idx.')">
                        '.$mainIndicator.'
                    </div>';
                        }
                    }

                    $html .= '</div>';

                } else {
                    $html .= '<div style="color: #999; margin-bottom: 10px; text-align: center;">Нет фото</div>';
                }

                static $scriptAdded = false;
                if (!$scriptAdded) {
                    $html .= $this->getModalScript();
                    $scriptAdded = true;
                }

                return $html;
            },
        ]);


        CRUD::addColumn([
            'name' => 'title',
            'label' => 'Название',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'link',
            'label' => 'Ссылка',
            'type' => 'text',
            'limit' => 50,
        ]);

        // Прогресс показов
        CRUD::addColumn([
            'name' => 'impressions_progress',
            'label' => 'Показы',
            'type' => 'closure',
            'function' => function($entry) {
                if ($entry->impressions_limit == 0) {
                    return '<span class="badge badge-info">' . $entry->impressions_count . ' / ∞</span>';
                }
                $percent = $entry->impressions_progress;
                $color = $percent >= 100 ? 'success' : ($percent >= 80 ? 'warning' : 'info');
                return '<div class="progress" style="min-width: 100px;">
                    <div class="progress-bar bg-'.$color.'" role="progressbar" style="width: '.$percent.'%">
                        '.$entry->impressions_count.' / '.$entry->impressions_limit.'
                    </div>
                </div>';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'start_date',
            'label' => 'Начало',
            'type' => 'datetime',
            'format' => 'DD.MM.YYYY HH:mm',
        ]);

        CRUD::addColumn([
            'name' => 'end_date',
            'label' => 'Окончание',
            'type' => 'datetime',
            'format' => 'DD.MM.YYYY HH:mm',
        ]);

        CRUD::addColumn([
            'name' => 'is_currently_active',
            'label' => 'Статус',
            'type' => 'closure',
            'function' => function($entry) {
                if ($entry->isCurrentlyActive()) {
                    return '<span class="badge badge-success">Активна</span>';
                }
                if (!$entry->is_active) {
                    return '<span class="badge badge-secondary">Отключена</span>';
                }
                if ($entry->hasReachedImpressionsLimit()) {
                    return '<span class="badge badge-warning">Лимит достигнут</span>';
                }
                return '<span class="badge badge-danger">Неактивна</span>';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'order',
            'label' => 'Порядок',
            'type' => 'number',
        ]);

        CRUD::addFilter([
            'name' => 'is_active',
            'type' => 'dropdown',
            'label' => 'Статус',
        ], [
            1 => 'Активные',
            0 => 'Неактивные',
        ], function($value) {
            CRUD::addClause('where', 'is_active', $value);
        });

        CRUD::addFilter([
            'name' => 'date_range',
            'type' => 'date_range',
            'label' => 'Период показа',
        ], false, function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'start_date', '>=', $dates->from);
            CRUD::addClause('where', 'end_date', '<=', $dates->to);
        });
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(AdvertisementRequest::class);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Название рекламы',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'Например: Акция на товар X',
            ],
        ]);

        CRUD::addField([
            'name' => 'link',
            'label' => 'Ссылка для перехода',
            'type' => 'url',
            'attributes' => [
                'placeholder' => 'https://example.com/promo',
            ],
            'hint' => 'URL, на который будет вести реклама',
        ]);

        // Загрузка изображений
        CRUD::addField([
            'name' => 'photos',
            'label' => 'Изображения рекламы',
            'type' => 'upload_multiple',
            'upload' => true,
            'disk' => 'public',
            'hint' => 'Можно загрузить несколько изображений. Первое будет основным.',
        ]);

        CRUD::addField([
            'name' => 'impressions_limit',
            'label' => 'Лимит показов',
            'type' => 'number',
            'attributes' => [
                'min' => 0,
                'step' => 1,
            ],
            'default' => 0,
            'hint' => 'Максимальное количество показов. 0 = без ограничений',
        ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Дата начала показа',
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD.MM.YYYY HH:mm',
                'language' => 'ru',
            ],
            'allows_null' => true,
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'Дата окончания показа',
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD.MM.YYYY HH:mm',
                'language' => 'ru',
            ],
            'allows_null' => true,
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Активна',
            'type' => 'switch',
            'default' => true,
            'hint' => 'Включить/выключить рекламу',
        ]);

        CRUD::addField([
            'name' => 'order',
            'label' => 'Порядок отображения',
            'type' => 'number',
            'attributes' => [
                'min' => 0,
            ],
            'default' => 0,
            'hint' => 'Чем меньше число, тем выше приоритет показа',
        ]);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(AdvertisementRequest::class);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Название рекламы',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'Например: Акция на товар X',
            ],
        ]);

        CRUD::addField([
            'name' => 'link',
            'label' => 'Ссылка для перехода',
            'type' => 'url',
            'attributes' => [
                'placeholder' => 'https://example.com/promo',
            ],
            'hint' => 'URL, на который будет вести реклама',
        ]);

        CRUD::addField([
            'name' => 'impressions_limit',
            'label' => 'Лимит показов',
            'type' => 'number',
            'attributes' => [
                'min' => 0,
                'step' => 1,
            ],
            'default' => 0,
            'hint' => 'Максимальное количество показов. 0 = без ограничений',
        ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Дата начала показа',
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD.MM.YYYY HH:mm',
                'language' => 'ru',
            ],
            'allows_null' => true,
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'Дата окончания показа',
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD.MM.YYYY HH:mm',
                'language' => 'ru',
            ],
            'allows_null' => true,
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Активна',
            'type' => 'switch',
            'default' => true,
            'hint' => 'Включить/выключить рекламу',
        ]);

        CRUD::addField([
            'name' => 'order',
            'label' => 'Порядок отображения',
            'type' => 'number',
            'attributes' => [
                'min' => 0,
            ],
            'default' => 0,
            'hint' => 'Чем меньше число, тем выше приоритет показа',
        ]);

        CRUD::addField([
            'name' => 'manage_photos_link',
            'label' => 'Управление изображениями',
            'type' => 'custom_html',
            'value' => '
            <div class="form-group">
                <label>Управление изображениями</label>
                <div>
                    <a href="'.url('admin/advertisement/'.$this->crud->getCurrentEntry()->id.'/photos').'"
                       class="btn btn-primary">
                        <i class="la la-images"></i> Перейти к управлению фотографиями
                    </a>
                    <p class="help-block">Загрузка и управление изображениями осуществляется на отдельной странице</p>
                </div>
            </div>
        '
        ]);
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::addColumn([
            'name' => 'all_images',
            'label' => 'Все изображения',
            'type' => 'closure',
            'function' => function($entry) {
                $html = '<div class="row">';
                foreach ($entry->images as $image) {
                    $primary = $image->is_primary ? '<span class="badge badge-primary">Основное</span>' : '';
                    $html .= '<div class="col-md-3 mb-3">
                        <img src="'.$image->url.'" class="img-fluid" />
                        '.$primary.'
                    </div>';
                }
                $html .= '</div>';
                return $html;
            },
            'escaped' => false,
        ]);
    }

    /**
     * Рендер текущих изображений с возможностью удаления
     */
    protected function renderCurrentImages($entry)
    {
        if ($entry->images->isEmpty()) {
            return '<p class="text-muted">Нет загруженных изображений</p>
                    <a href="'.url('admin/advertisement/'.$entry->id.'/photos').'" class="btn btn-primary">
                        <i class="la la-images"></i> Управление фотографиями
                    </a>';
        }

        $service = app(\App\Services\AdvertisementService::class);
        $images = $service->getAdvertisementImagesWithUrls($entry);

        $html = '<div class="mb-3">
                    <a href="'.url('admin/advertisement/'.$entry->id.'/photos').'" class="btn btn-primary mb-3">
                        <i class="la la-images"></i> Управление фотографиями
                    </a>
                 </div>';

        $html .= '<div class="row mb-3">';
        foreach ($images as $image) {
            $proxyUrl = url("admin/image-proxy/{$image['fid']}");
            $primary = $image['is_primary'] ? '<span class="badge badge-primary">Основное</span>' : '';
            $html .= '<div class="col-md-2 mb-2">
                <div class="card">
                    <img src="'.$proxyUrl.'" class="card-img-top" />
                    <div class="card-body p-2 text-center">
                        '.$primary.'
                        <form action="'.url('admin/advertisement-image/'.$image['id'].'/delete').'"
                              method="POST"
                              onsubmit="return confirm(\'Удалить изображение?\')">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                            <button type="submit" class="btn btn-sm btn-danger mt-1">
                                <i class="la la-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Переопределяем store для работы с фото через сервис
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();

        /** @var AdvertisementService $service */
        $service = app(AdvertisementService::class);

        $photos = $request->file('photos') ?? [];

        $data = $request->except(['photos']);

        $advertisement = $service->createAdvertisement($data, $photos);

        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        return redirect($this->crud->route);
    }

    /**
     * Переопределяем update для работы с фото через сервис
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();

        /** @var AdvertisementService $service */
        $service = app(AdvertisementService::class);

        $id = $this->crud->getCurrentEntryId();
        $advertisement = Advertisement::findOrFail($id);

        $photos = $request->file('photos') ?? [];

        $data = $request->except(['photos', '_token', '_method']);

        $advertisement = $service->updateAdvertisement($advertisement, $data, $photos);

        \Alert::success(trans('backpack::crud.update_success'))->flash();

        return redirect($this->crud->route);
    }

    private function getModalScript()
    {
        return '
<script>
function openGlobalModal(imageUrls, currentIndex) {
    closeGlobalModal();

    const modal = document.createElement("div");
    modal.id = "globalImageModal";
    modal.style.position = "fixed";
    modal.style.top = "0";
    modal.style.left = "0";
    modal.style.width = "100%";
    modal.style.height = "100%";
    modal.style.backgroundColor = "rgba(0, 0, 0, 0.8)";
    modal.style.display = "flex";
    modal.style.justifyContent = "center";
    modal.style.alignItems = "center";
    modal.style.zIndex = "99999";

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

    const leftButton = document.createElement("span");
    leftButton.innerHTML = "&#8592;";
    leftButton.style.position = "absolute";
    leftButton.style.left = "40px";
    leftButton.style.top = "50%";
    leftButton.style.fontSize = "40px";
    leftButton.style.color = "white";
    leftButton.style.cursor = "pointer";
    leftButton.style.userSelect = "none";
    leftButton.onclick = function(e) {
        e.stopPropagation();
        showImage(currentIndex - 1);
    };

    const rightButton = document.createElement("span");
    rightButton.innerHTML = "&#8594;";
    rightButton.style.position = "absolute";
    rightButton.style.right = "40px";
    rightButton.style.top = "50%";
    rightButton.style.fontSize = "40px";
    rightButton.style.color = "white";
    rightButton.style.cursor = "pointer";
    rightButton.style.userSelect = "none";
    rightButton.onclick = function(e) {
        e.stopPropagation();
        showImage(currentIndex + 1);
    };

    const img = document.createElement("img");
    img.style.maxWidth = "90%";
    img.style.maxHeight = "90%";
    img.style.borderRadius = "5px";

    function showImage(idx) {
        if (idx < 0) idx = imageUrls.length - 1;
        if (idx >= imageUrls.length) idx = 0;
        currentIndex = idx;
        img.src = imageUrls[currentIndex];
    }

    showImage(currentIndex);

    modal.appendChild(closeButton);
    if (imageUrls.length > 1) {
        modal.appendChild(leftButton);
        modal.appendChild(rightButton);
    }
    modal.appendChild(img);

    modal.onclick = function(e) {
        if (e.target === modal) {
            closeGlobalModal();
        }
    };

    document.body.appendChild(modal);

    document.addEventListener("keydown", function modalKeyHandler(e) {
        if (document.getElementById("globalImageModal")) {
            switch(e.key) {
                case "Escape":
                    closeGlobalModal();
                    break;
                case "ArrowLeft":
                    if (imageUrls.length > 1) showImage(currentIndex - 1);
                    break;
                case "ArrowRight":
                    if (imageUrls.length > 1) showImage(currentIndex + 1);
                    break;
            }
        } else {
            document.removeEventListener("keydown", modalKeyHandler);
        }
    });
}

function closeGlobalModal() {
    const modal = document.getElementById("globalImageModal");
    if (modal) {
        document.body.removeChild(modal);
    }
}
</script>';
    }
}
