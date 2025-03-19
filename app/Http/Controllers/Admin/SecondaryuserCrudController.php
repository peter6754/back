<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SecondaryuserRequest;
use App\Models\UserInformation;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use DB;

/**
 * Class SecondaryuserCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SecondaryuserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        if (! backpack_user()->can('access secondaryusers')) {
            abort(403);
        }

        CRUD::setModel(\App\Models\Secondaryuser::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/secondaryuser');
        CRUD::setEntityNameStrings('Пользователь', 'Пользователи');

        // Для отображения в таблице (списке)
        CRUD::column('id')->label('ID');
        CRUD::column('name')->label('Имя');
        CRUD::column('age')->label('Возраст');
        $this->crud->addColumn([
            'name' => 'photos_management',
            'label' => 'Фотографии',
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
                        $imageUrl = $image->image_url;
                        $isMain = $image->is_main ?? false;

                        $allUrls = $images->pluck('image_url')->map(fn ($url) => addslashes($url))->toArray();

                        $mainIndicator = $isMain ?
                            '<div style="position: absolute; top: 2px; right: 2px; background: gold; color: black; padding: 1px 4px; font-size: 9px; border-radius: 2px; font-weight: bold;">ГЛАВНОЕ</div>' : '';

                        $borderColor = $isMain ? '#ffd700' : '#ddd';

                        $html .= '
                <div style="position: relative; flex-shrink: 0;">
                    <img src="'.$imageUrl.'" height="80px" width="60px"
                         style="cursor: pointer; border: 2px solid '.$borderColor.'; border-radius: 4px; object-fit: cover;"
                         onclick="openGlobalModal('.htmlspecialchars(json_encode($allUrls)).', '.$idx.')">
                    '.$mainIndicator.'
                </div>';
                    }

                    $html .= '</div>';
                } else {
                    $html .= '<div style="color: #999; margin-bottom: 10px; text-align: center;">Нет фото</div>';
                }

                $html .= '<a href="'.url('admin/users/'.$entry->id.'/photos').'"
                     class="btn btn-sm btn-primary" target="_blank"
                     style="padding: 4px 8px; font-size: 11px; text-decoration: none;">
                    <i class="fa fa-cog" style="margin-right: 4px;"></i>Управление ('.$images->count().')
                  </a>';

                static $scriptAdded = false;
                if (!$scriptAdded) {
                    $html .= $this->getOriginalModalScript();
                    $scriptAdded = true;
                }

                return $html;
            },
        ]);

        CRUD::column('gender')
            ->label('Пол')
            ->type('select_from_array')
            ->options(['male' => 'М', 'female' => 'Ж', 'm_f' => 'М+Ж', 'm_m' => 'М+М', 'f_f' => 'Ж+Ж'])
            ->wrapper([
                'class' => function ($crud, $column, $entry, $related_key) {
                    return $entry->gender === 'male' ? 'text-primary' : 'text-danger';
                },
            ]);

        CRUD::addColumn([
            'name' => 'verification_status',
            'label' => 'Верификация',
            'type' => 'custom_html',
            'escaped' => false,
            'value' => function ($entry) {
                $verification = $entry->verificationRequest;

                if ($verification) {
                    $url = url('admin/verification-requests/'.$verification->user_id.'/edit');
                    switch ($verification->status) {
                        case 'initial':
                            $label = '<span style="color: orange;">На проверке</span>';
                            break;
                        case 'approved':
                            $label = '<span style="color: green;">Одобрено</span>';
                            break;
                        case 'rejected':
                            $label = '<span style="color: red;">Отклонено</span>';
                            break;
                        default:
                            $label = '<span>'.ucfirst($verification->status).'</span>';
                    }

                    return '<a href="'.$url.'">'.$label.'</a>';
                }

                return 'Нет заявки';
            },
        ]);

        $this->crud->addColumn([
            'name' => 'userInformation.bio',
            'label' => 'О себе',
            'type' => 'bio_with_collapse', // Используем кастомный тип
            'entity' => 'userInformation',
            'attribute' => 'bio',
            'model' => UserInformation::class,
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
                'not_decided' => 'Не решено',
            ]);
        // Жадная загрузка данных
        CRUD::addClause('with', ['activeSubscription.package.subscription']);

        // Колонка с информацией о подписке
        CRUD::addColumn([
            'name' => 'subscription_info',
            'label' => 'Подписка',
            'type' => 'custom_html',
            'value' => function ($entry) {
                if (! $entry->activeSubscription) {
                    return '<span class="text-muted">Нет активной подписки</span>';
                }

                return '
                <div class="subscription-info">
                    <span class="badge bg-primary">
                        '.$entry->activeSubscription->package->subscription->type.'
                    </span>
                    <div class="text-sm text-muted">
                        '.$entry->activeSubscription->due_date->format('d.m.Y').'
                        ('.$entry->activeSubscription->due_date->diffForHumans().')
                    </div>
                </div>
            ';
            },
        ]);
        CRUD::column('last_check')->label('Последняя проверка');
        CRUD::column('is_online')->label('Онлайн статус');
        CRUD::column('phone')->label('Телефон');
        CRUD::column('email')->label('Электронная почта');
        CRUD::column('birth_date')->label('Дата рождения')->type('date');
        CRUD::column('mode')
            ->label('Режим')
            ->type('select_from_array')
            ->options([
                'authenticated' => 'Аутентифицирован',
                'deleted' => 'Удалён',
            ]);
        CRUD::column('registration_date')->label('Дата регистрации');
        CRUD::column('username')->label('Имя пользователя');

        CRUD::addField([
            'name' => 'name',
            'label' => 'Имя',
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'username',
            'label' => 'Имя пользователя',
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => 'Телефон',
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => 'Электронная почта',
            'type' => 'email',
        ]);

        CRUD::addField([
            'name' => 'birth_date',
            'label' => 'Дата рождения',
            'type' => 'date',
        ]);

        CRUD::addField([
            'name' => 'age',
            'label' => 'Возраст',
            'type' => 'number',
        ]);

        CRUD::addField([
            'name' => 'gender',
            'label' => 'Пол',
            'type' => 'select_from_array',
            'options' => ['male' => 'Мужчина', 'female' => 'Женщина', 'm_f' => 'М+Ж', 'm_m' => 'М+М', 'f_f' => 'Ж+Ж'],
        ]);

        CRUD::addField([
            'name' => 'sexual_orientation',
            'label' => 'Сексуальная ориентация',
            'type' => 'select_from_array',
            'options' => ['hetero' => 'Гетеро', 'gay' => 'Гей', 'lesbian' => 'Лесбиянка', 'bisexual' => 'Бисексуал', 'asexual' => 'Асексуал', 'not_decided' => 'Не решено'],
        ]);

        CRUD::addField([
            'name' => 'mode',
            'label' => 'Режим',
            'type' => 'select_from_array',
            'options' => ['authenticated' => 'Аутентифицирован', 'deleted' => 'Удалён'],
        ]);

        CRUD::addField([
            'name' => 'registration_date',
            'label' => 'Дата регистрации',
            'type' => 'datetime_picker',
        ]);

        CRUD::addField([
            'name' => 'last_check',
            'label' => 'Последняя проверка',
            'type' => 'datetime_picker',
        ]);

        CRUD::addField([
            'name' => 'is_online',
            'label' => 'Онлайн статус',
            'type' => 'boolean',
        ]);

        CRUD::filter('is_online')
            ->type('dropdown')
            ->label('Статус')
            ->values([
                0 => 'Оффлайн',
                1 => 'Онлайн',
            ]);

        CRUD::filter('is_bot')
            ->type('dropdown')
            ->label('Бот')
            ->values([
                1 => 'Бот',
            ]);

        CRUD::filter('gender')
            ->type('dropdown')
            ->label('Пол')
            ->values([
                'female' => 'Женщины',
                'male' => 'Мужчины',
                'f_f' => 'Ж+Ж',
                'm_f' => 'М+Ж',
                'm_m' => 'М+М',
            ]);

        CRUD::filter('age')
            ->type('range')
            ->label('Возраст')
            ->whenActive(function ($value) {
                $range = json_decode($value);
                if ($range->from) {
                    CRUD::addClause('where', 'age', '>=', (int) $range->from);
                }
                if ($range->to) {
                    CRUD::addClause('where', 'age', '<=', (int) $range->to);
                }
            });

        CRUD::filter('has_subscription')
            ->type('dropdown')
            ->label('Есть подписка')
            ->values([
                1 => 'Да',
                0 => 'Нет',
            ])
            ->whenActive(function ($value) {
                if ($value) {
                    CRUD::addClause('whereHas', 'activeSubscription');
                } else {
                    CRUD::addClause('whereDoesntHave', 'activeSubscription');
                }
            });
        // Фильтр по версии приложения
        $this->crud->addFilter([
            'name' => 'app_version',
            'type' => 'dropdown',
            'label' => 'Версия приложения'
        ], function() {
            return DB::table('user_device_tokens')
                ->select(DB::raw("DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(application, 'version: ', -1), ',', 1)) as version"))
                ->whereNotNull('application')
                ->where('application', 'like', '%version:%')
                ->orderBy('version', 'desc')
                ->pluck('version', 'version')
                ->toArray();
        }, function($value) {
            $this->crud->query->where(function($query) use ($value) {
                $query->whereIn('id', function($subquery) use ($value) {
                    $subquery->select('user_id')
                        ->from('user_device_tokens')
                        ->where('application', 'like', '%version: ' . $value . '%');
                });
            });
        });
        // Фильтр по маркету
        $this->crud->addFilter([
            'name' => 'app_market',
            'type' => 'dropdown',
            'label' => 'Магазин приложения'
        ], function() {
            $markets = DB::table('user_device_tokens')
                ->whereNotNull('application')
                ->where('application', '!=', '')
                ->get()
                ->map(function($item) {
                    if (preg_match('/market:\s*([^,]+)/', $item->application, $matches)) {
                        return trim($matches[1]);
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values()
                ->mapWithKeys(function($market) {
                    return [$market => $market];
                })
                ->toArray();

            return $markets;
        }, function($value) {
            $this->crud->query->where(function($query) use ($value) {
                $query->whereIn('id', function($subquery) use ($value) {
                    $subquery->select('user_id')
                        ->from('user_device_tokens')
                        ->where('application', 'like', '%market: ' . $value . '%');
                });
            });
        });
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        //        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        CRUD::addButton('line', 'grant_subscription', 'view', 'crud::buttons.grant_subscription');
        CRUD::column('id')->label('ID')->visibleInTable(false)->visibleInShow(true);
        CRUD::column('name')->label('Имя');
        CRUD::column('username')->label('Логин');
        CRUD::column('phone')->label('Телефон');
        CRUD::column('email')->label('Почта');
        CRUD::column('birth_date')->label('День рождения');
        CRUD::column('age')->label('В-т');
        CRUD::column('gender')->label('Пол');
        CRUD::column('sexual_orientation')->label('Ориентация');
        CRUD::column('mode')->label('Режим');
        CRUD::column('registration_date')->label('Дата регистрации');
        $this->crud->query->orderBy('registration_date', 'desc');
        CRUD::column('app_info')
            ->label('Приложение')
            ->type('custom_html')
            ->value(function($entry) {
                $application = DB::table('user_device_tokens')
                    ->where('user_id', $entry->id)
                    ->value('application');

                if (!$application) {
                    return '-';
                }

                $info = [];
                if (preg_match('/version:\s*([^,]+)/', $application, $matches)) {
                    $info[] = "<strong>Version:</strong> " . trim($matches[1]);
                }
                if (preg_match('/market:\s*([^,]+)/', $application, $matches)) {
                    $info[] = "<strong>Market:</strong> " . trim($matches[1]);
                }

                return implode('<br>', $info);
            });
        CRUD::column('last_check')->label('Последняя проверка');
        CRUD::column('is_online')->label('Онлайн');
        CRUD::disableResponsiveTable();
        CRUD::disablePersistentTable();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
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
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    private function getOriginalModalScript()
    {
        return '
    <script>
    function openGlobalModal(imageUrls, currentIndex) {
        // Удаляем старое модальное окно, если оно есть
        closeGlobalModal();

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

        // Кнопка закрытия
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

        // Кнопки влево/вправо
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

        // Полноразмерное изображение
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

        // Добавляем элементы в модальное окно
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

        // Обработка клавиатуры
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

    /**
     * Получить количество новых пользователей по сторам за вчера и сегодня
     *
     * @return array
     */
    public static function getNewUsersByStore()
    {
        $yesterdayStart = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');
        $todayEnd = Carbon::now('Europe/Moscow')->endOfDay()->setTimezone('UTC');

        $yesterday = self::countByPeriodAndStore($yesterdayStart, $todayStart);

        $today = self::countByPeriodAndStore($todayStart, $todayEnd);

        return [
            'yesterday' => $yesterday,
            'today' => $today,
            'yesterday_total' => array_sum($yesterday),
            'today_total' => array_sum($today)
        ];
    }

    /**
     * Подсчет пользователей по периоду и сторам
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private static function countByPeriodAndStore($startDate, $endDate)
    {
        $result = DB::table('users as u')
            ->leftJoin('user_device_tokens as udt', 'u.id', '=', 'udt.user_id')
            ->where('u.registration_date', '>=', $startDate)
            ->where('u.registration_date', '<=', $endDate)
            ->selectRaw("
            CASE
                WHEN udt.application LIKE '%market: google-play%' THEN 'Google'
                WHEN udt.application LIKE '%market: ru-store%' THEN 'RuStore'
                ELSE 'Другие'
            END as store,
            COUNT(DISTINCT u.id) as count
        ")
            ->groupBy('store')
            ->pluck('count', 'store')
            ->toArray();

        return array_merge([
            'Google' => 0,
            'RuStore' => 0,
            'Другие' => 0
        ], $result);
    }
}
