<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\DB;
use App\Models\Secondaryuser;
use Illuminate\Http\Request;

class CityAnalyticsCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(Secondaryuser::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/city-analytics');
        CRUD::setEntityNameStrings('аналитика по городам', 'аналитика по городам');
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::denyAccess(['create', 'update', 'delete', 'show']);
        CRUD::setHeading('Аналитика пользователей по городам');
        CRUD::setSubheading('Статистика распределения пользователей по городам с разбивкой по полу');
    }

    /**
     * Переопределяем метод index для показа кастомной страницы
     */
    public function index()
    {
        CRUD::hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Аналитика по городам';
        $this->data['analytics_data'] = $this->getCityAnalytics();
        $this->data['summary'] = $this->getSummaryData();
        $this->data['age_analytics'] = $this->getAgeAnalytics();

        return view('admin.city_analytics', $this->data);
    }

    /**
     * Получить данные аналитики по городам
     */
    private function getCityAnalytics()
    {
        return DB::table('user_cities')
            ->leftJoin('users', 'user_cities.user_id', '=', 'users.id')
            ->select([
                DB::raw('COALESCE(NULLIF(TRIM(user_cities.formatted_address), ""), "Не указан") as city'),
                DB::raw('COUNT(CASE WHEN users.gender = "male" THEN 1 END) as male_count'),
                DB::raw('COUNT(CASE WHEN users.gender = "female" THEN 1 END) as female_count'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('ROUND(COUNT(CASE WHEN users.gender = "male" THEN 1 END) * 100.0 / COUNT(*), 1) as male_percentage'),
                DB::raw('ROUND(COUNT(CASE WHEN users.gender = "female" THEN 1 END) * 100.0 / COUNT(*), 1) as female_percentage')
            ])
            ->whereNotNull('users.id')
            ->groupBy('user_cities.formatted_address')
            ->orderBy('total_count', 'desc')
            ->get();
    }

    /**
     * Получить сводные данные
     */
    private function getSummaryData()
    {
        $summary = DB::table('user_cities')
            ->leftJoin('users', 'user_cities.user_id', '=', 'users.id')
            ->select([
                DB::raw('COUNT(DISTINCT COALESCE(NULLIF(TRIM(user_cities.formatted_address), ""), "Не указан")) as total_cities'),
                DB::raw('COUNT(*) as total_users'),
                DB::raw('COUNT(CASE WHEN users.gender = "male" THEN 1 END) as total_males'),
                DB::raw('COUNT(CASE WHEN users.gender = "female" THEN 1 END) as total_females'),
                DB::raw('COUNT(CASE WHEN TRIM(user_cities.formatted_address) = "" OR user_cities.formatted_address IS NULL THEN 1 END) as users_without_city')
            ])
            ->whereNotNull('users.id')
            ->first();

        return $summary;
    }

    /**
     * Получить аналитику покупок по возрастам
     */
    private function getAgeAnalytics()
    {
        // man data
        $maleData = DB::table('transactions')
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.status', 'succeeded')
            ->where('users.gender', 'male')
            ->whereNotNull('users.age')
            ->select([
                DB::raw('
                    CASE
                        WHEN users.age BETWEEN 18 AND 24 THEN "18-24"
                        WHEN users.age BETWEEN 25 AND 34 THEN "25-34"
                        WHEN users.age BETWEEN 35 AND 44 THEN "35-44"
                        WHEN users.age BETWEEN 45 AND 54 THEN "45-54"
                        WHEN users.age BETWEEN 55 AND 64 THEN "55-64"
                        WHEN users.age >= 65 THEN "65+"
                        ELSE "Не указан"
                    END as age_group
                '),
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('COUNT(DISTINCT users.id) as unique_users')
            ])
            ->groupBy('age_group')
            ->orderByRaw('
                CASE age_group
                    WHEN "18-24" THEN 1
                    WHEN "25-34" THEN 2
                    WHEN "35-44" THEN 3
                    WHEN "45-54" THEN 4
                    WHEN "55-64" THEN 5
                    WHEN "65+" THEN 6
                    ELSE 7
                END
            ')
            ->get();
        // woman data
        $femaleData = DB::table('transactions')
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.status', 'succeeded')
            ->where('users.gender', 'female')
            ->whereNotNull('users.age')
            ->select([
                DB::raw('
                    CASE
                        WHEN users.age BETWEEN 18 AND 24 THEN "18-24"
                        WHEN users.age BETWEEN 25 AND 34 THEN "25-34"
                        WHEN users.age BETWEEN 35 AND 44 THEN "35-44"
                        WHEN users.age BETWEEN 45 AND 54 THEN "45-54"
                        WHEN users.age BETWEEN 55 AND 64 THEN "55-64"
                        WHEN users.age >= 65 THEN "65+"
                        ELSE "Не указан"
                    END as age_group
                '),
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('COUNT(DISTINCT users.id) as unique_users')
            ])
            ->groupBy('age_group')
            ->orderByRaw('
                CASE age_group
                    WHEN "18-24" THEN 1
                    WHEN "25-34" THEN 2
                    WHEN "35-44" THEN 3
                    WHEN "45-54" THEN 4
                    WHEN "55-64" THEN 5
                    WHEN "65+" THEN 6
                    ELSE 7
                END
            ')
            ->get();

        return [
            'male' => $maleData,
            'female' => $femaleData
        ];
    }

}
