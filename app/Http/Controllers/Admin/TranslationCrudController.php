<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\Translation;
use Illuminate\Support\Str;

class TranslationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * @var string[]
     */
    static $groups = [
        'tinderone' => 'TinderOne',
        'users' => 'Пользователи',
        'transactions' => 'Платежи'
    ];

    /**
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Translation::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/translation');
        CRUD::setEntityNameStrings('translation', 'translations');
    }

    /**
     * @return void
     */
    protected function setupListOperation()
    {
        // Добавляем кнопку экспорта
        $this->crud->addButton('top', 'export_translations', 'view', 'crud::buttons.export_translations');

        CRUD::column('group')
            ->label('Group')
            ->type('select_from_array')
            ->options($this->getGroupOptions());

        CRUD::column('key')
            ->label('Key')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('key', 'like', "%{$searchTerm}%");
            });

        CRUD::column('description')
            ->label('Description')
            ->limit(50)
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->orWhere('description', 'like', "%{$searchTerm}%");
            });

        foreach (config('locales.available_locales') as $localeCode => $localeName) {
            CRUD::column('translation_'.$localeCode)
                ->label(Str::upper($localeCode))
                ->type('text')
                ->value(function ($entry) use ($localeCode) {
                    return $entry->getTranslation($localeCode) ?? '-';
                })
                ->limit(30)
                ->orderable(false)
                ->searchLogic(false);
        }

        CRUD::column('is_active')
            ->label('Active')
            ->type('boolean')
            ->options([
                0 => 'Inactive',
                1 => 'Active'
            ]);

        CRUD::column('updated_at')
            ->label('Last Updated')
            ->type('datetime');

        $this->addFilters();
    }

    /**
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'key' => 'required|unique:translations,key|max:255',
            'group' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

        $this->addFields();
    }

    /**
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        CRUD::setValidation([
            'key' => 'required|unique:translations,key,'.CRUD::getCurrentEntryId().'|max:255',
            'group' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

        $this->overrideFieldsWithValues();
    }

    /**
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::column('group')->label('Group');
        CRUD::column('key')->label('Key');
        CRUD::column('description')->label('Description')->type('textarea');

        foreach (config('locales.available_locales') as $localeCode => $localeName) {
            CRUD::column('translation_'.$localeCode)
                ->label($localeName)
                ->type('textarea')
                ->value(function ($entry) use ($localeCode) {
                    return $entry->getTranslation($localeCode) ?? '-';
                });
        }

        CRUD::column('is_active')->label('Active')->type('boolean');
        CRUD::column('created_at')->label('Created At');
        CRUD::column('updated_at')->label('Updated At');
    }

    /**
     * @return void
     */
    protected function addFields(): void
    {
        CRUD::field('group')
            ->label('Group')
            ->type('select2_from_array')
            ->options($this->getGroupOptions())
            ->hint('Group for organizing translations')
            ->default('default');

        CRUD::field('key')
            ->label('Translation Key')
            ->type('text')
            ->hint('Unique identifier for the translation')
            ->attributes([
                'placeholder' => 'e.g., auth.login_button'
            ]);

        CRUD::field('description')
            ->label('Description')
            ->type('textarea')
            ->attributes(['rows' => 3])
            ->hint('Optional description for context');

        foreach (config('locales.available_locales') as $localeCode => $localeName) {
            CRUD::field('translation_'.$localeCode)
                ->label("Translation ($localeName)")
                ->type('textarea')
                ->fake(true)
                ->store_in('translations')
                ->attributes([
                    'rows' => 2,
                    'placeholder' => "Enter $localeName translation..."
                ])
                ->hint("Translation in $localeName language");
        }

        CRUD::field('is_active')
            ->label('Active')
            ->type('checkbox')
            ->default(true)
            ->hint('Deactivate to hide this translation');
    }

    /**
     * @return void
     */
    protected function overrideFieldsWithValues(): void
    {
        $entry = $this->crud->getCurrentEntry();

        foreach (config('locales.available_locales') as $localeCode => $localeName) {
            CRUD::modifyField('translation_'.$localeCode, [
                'value' => $entry->getTranslation($localeCode) ?? ''
            ]);
        }
    }

    /**
     * @return void
     */
    protected function addFilters(): void
    {
        CRUD::filter('group')
            ->type('dropdown')
            ->values($this->getGroupOptions())
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'group', $value);
            });

        CRUD::filter('is_active')
            ->type('dropdown')
            ->values([
                1 => 'Active',
                0 => 'Inactive',
            ])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'is_active', $value);
            });

        CRUD::filter('has_translation')
            ->type('dropdown')
            ->values([
                'empty' => 'Missing Translations',
                'filled' => 'Has Translations',
            ])
            ->whenActive(function ($value) {
                if ($value === 'empty') {
                    CRUD::addClause('where', function ($query) {
                        $query->whereNull('translations')
                            ->orWhere('translations', '{}')
                            ->orWhere('translations', '');
                    });
                } else {
                    CRUD::addClause('where', function ($query) {
                        $query->whereNotNull('translations')
                            ->where('translations', '!=', '{}')
                            ->where('translations', '!=', '');
                    });
                }
            });
    }

    /**
     * @return string[]
     */
    protected function getGroupOptions(): array
    {
        return self::$groups;
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $request = $this->crud->validateRequest();
        $item = $this->crud->create($request->all());
        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        $request = $this->crud->validateRequest();
        $item = $this->crud->update($request->get($this->crud->model->getKeyName()), $request->all());
        \Alert::success(trans('backpack::crud.update_success'))->flash();
        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportTranslations(Request $request)
    {
        $groups = $request->get('groups', []);
        $locales = $request->get('locales', array_keys(config('locales.available_locales')));

        // Если не выбраны группы, берем все
        if (empty($groups)) {
            $groups = Translation::distinct()->pluck('group')->toArray();
        }
        $errors = [];

        foreach ($groups as $group) {
            foreach ($locales as $locale) {
                try {
                    $translations = Translation::where('group', $group)
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(function ($translation) use ($locale) {
                            $value = $translation->getTranslation($locale);
                            return $value ? [$translation->key => $value] : [];
                        })
                        ->toArray();

                    if (!empty($translations)) {
                        $this->generateLanguageFile($group, $locale, $translations);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error exporting group '$group' for locale '$locale': ".$e->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            \Alert::error('Export completed with errors: '.implode(', ', $errors))->flash();
        } else {
            \Alert::success('Translations exported successfully!')->flash();
        }

        return back();
    }

    /**
     * @param  string  $group
     * @param  string  $locale
     * @param  array  $translations
     * @return string|null
     * @throws \Exception
     */
    protected function generateLanguageFile(string $group, string $locale, array $translations): ?string
    {
        $langPath = resource_path("lang/{$locale}");

        // Создаем директорию, если не существует
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        $filePath = "{$langPath}/{$group}.php";

        // Генерируем содержимое файла
        $content = "<?php\n\nreturn [\n";

        foreach ($translations as $key => $value) {
            $escapedValue = $this->escapePhpString($value);
            $content .= "    '{$key}' => '{$escapedValue}',\n";
        }

        $content .= "];\n";

        try {
            File::put($filePath, $content);
            return $filePath;
        } catch (\Exception $e) {
            throw new \Exception("Failed to write file: {$filePath}");
        }
    }

    /**
     * Escape string for PHP file
     */
    protected function escapePhpString(string $value): string
    {
        return addcslashes($value, "'\\");
    }
}