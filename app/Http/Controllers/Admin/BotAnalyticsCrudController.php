<?php

namespace App\Http\Controllers\Admin;

use App\Models\Transactions;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\DB;
use App\Models\Secondaryuser;
use App\Models\UserReaction;
use Carbon\Carbon;

class BotAnalyticsCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(Secondaryuser::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/bot-analytics');
        CRUD::setEntityNameStrings('аналитика ботов', 'аналитика ботов');
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::denyAccess(['create', 'update', 'delete', 'show']);
        CRUD::setHeading('Аналитика активности ботов');
        CRUD::setSubheading('Статистика лайков ботов и конверсии в покупки');
    }

    /**
     * Переопределяем метод index для показа кастомной страницы
     */
    public function index()
    {
        CRUD::hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Аналитика ботов';
        $this->data['today_analytics'] = $this->getTodayAnalytics();
        $this->data['weekly_analytics'] = $this->getWeeklyAnalytics();
        $this->data['bot_summary'] = $this->getBotSummary();
        $this->data['conversion_analytics'] = $this->getConversionAnalytics();

        return view('admin.bot_analytics', $this->data);
    }

    /**
     * Получить данные за сегодня
     */
    private function getTodayAnalytics()
    {
        $today = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        $likedUsersQuery = UserReaction::select('user_id')
            ->whereIn('reactor_id', function($query) {
                $query->select('id')
                    ->from('users')
                    ->where('is_bot', 1);
            })
            ->whereBetween('date', [$today, $todayEnd])
            ->distinct();

        $totalLikedUsers = $likedUsersQuery->count();

        $purchasedUsers = Transactions::whereIn('user_id', function($query) use ($today, $todayEnd) {
            $query->select('user_id')
                ->from('user_reactions')
                ->whereIn('reactor_id', function($subQuery) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('is_bot', 1);
                })
                ->whereBetween('date', [$today, $todayEnd])
                ->distinct();
        })
            ->whereBetween('purchased_at', [$today, $todayEnd])
            ->where('price', '>', 0)
            ->where('status', 'succeeded')
            ->distinct()
            ->count('user_id');

        $totalRevenue = Transactions::whereIn('user_id', function($query) use ($today, $todayEnd) {
            $query->select('user_id')
                ->from('user_reactions')
                ->whereIn('reactor_id', function($subQuery) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('is_bot', 1);
                })
                ->whereBetween('date', [$today, $todayEnd])
                ->distinct();
        })
            ->whereBetween('purchased_at', [$today, $todayEnd])
            ->where('price', '>', 0)
            ->where('status', 'succeeded')
            ->sum('price');

        $totalLikes = UserReaction::whereIn('reactor_id', function($query) {
            $query->select('id')
                ->from('users')
                ->where('is_bot', 1);
        })
            ->whereBetween('date', [$today, $todayEnd])
            ->count();

        return [
            'total_likes' => $totalLikes,
            'unique_liked_users' => $totalLikedUsers,
            'purchased_users' => $purchasedUsers,
            'conversion_rate' => $totalLikedUsers > 0 ? round(($purchasedUsers / $totalLikedUsers) * 100, 2) : 0,
            'total_revenue' => $totalRevenue ?: 0,
            'date' => $today->format('d.m.Y')
        ];
    }

    /**
     * Получить данные за последние 7 дней
     */
    private function getWeeklyAnalytics()
    {
        $weeklyData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateEnd = $date->copy()->endOfDay();

            $likedUsersCount = UserReaction::whereIn('reactor_id', function($query) {
                $query->select('id')
                    ->from('users')
                    ->where('is_bot', 1);
            })
                ->whereBetween('date', [$date, $dateEnd])
                ->distinct()
                ->count('user_id');

            $purchased = Transactions::whereIn('user_id', function($query) use ($date, $dateEnd) {
                $query->select('user_id')
                    ->from('user_reactions')
                    ->whereIn('reactor_id', function($subQuery) {
                        $subQuery->select('id')
                            ->from('users')
                            ->where('is_bot', 1);
                    })
                    ->whereBetween('date', [$date, $dateEnd])
                    ->distinct();
            })
                ->whereBetween('purchased_at', [$date, $dateEnd])
                ->where('price', '>', 0)
                ->where('status', 'succeeded')
                ->distinct()
                ->count('user_id');

            $weeklyData[] = [
                'date' => $date->format('d.m'),
                'liked_users' => $likedUsersCount,
                'purchased_users' => $purchased,
                'conversion_rate' => $likedUsersCount > 0 ? round(($purchased / $likedUsersCount) * 100, 1) : 0
            ];
        }

        return $weeklyData;
    }

    /**
     * Получить сводные данные по ботам
     */
    private function getBotSummary()
    {
        $totalBots = Secondaryuser::where('is_bot', 1)->count();


        $weekAgo = Carbon::today()->subDays(7);
        $activeBots = UserReaction::whereIn('reactor_id', function($query) {
            $query->select('id')
                ->from('users')
                ->where('is_bot', 1);
        })
            ->where('date', '>=', $weekAgo)
            ->distinct()
            ->count('reactor_id');

        return [
            'total_bots' => $totalBots,
            'active_bots' => $activeBots,
            'inactive_bots' => $totalBots - $activeBots,
            'activity_percentage' => $totalBots > 0 ? round(($activeBots / $totalBots) * 100, 1) : 0
        ];
    }

    /**
     * Получить аналитику конверсии по времени
     */
    private function getConversionAnalytics()
    {

        $weeks = [];

        for ($i = 3; $i >= 0; $i--) {

            $endDate = Carbon::today()->subWeeks($i)->endOfDay();
            $startDate = Carbon::today()->subWeeks($i + 1)->addDay()->startOfDay();

            if ($i === 0) {
                $endDate = Carbon::today()->endOfDay();
                $startDate = Carbon::today()->subWeeks(1)->addDay()->startOfDay();
            }

            $likedUsersCount = UserReaction::whereIn('reactor_id', function($query) {
                $query->select('id')
                    ->from('users')
                    ->where('is_bot', 1);
            })
                ->whereBetween('date', [$startDate, $endDate])
                ->distinct()
                ->count('user_id');

            $purchased = Transactions::whereIn('user_id', function($query) use ($startDate, $endDate) {
                $query->select('user_id')
                    ->from('user_reactions')
                    ->whereIn('reactor_id', function($subQuery) {
                        $subQuery->select('id')
                            ->from('users')
                            ->where('is_bot', 1);
                    })
                    ->whereBetween('date', [$startDate, $endDate])
                    ->distinct();
            })
                ->whereBetween('purchased_at', [$startDate, $endDate])
                ->where('price', '>', 0)
                ->where('status', 'succeeded')
                ->distinct()
                ->count('user_id');

            $revenue = Transactions::whereIn('user_id', function($query) use ($startDate, $endDate) {
                $query->select('user_id')
                    ->from('user_reactions')
                    ->whereIn('reactor_id', function($subQuery) {
                        $subQuery->select('id')
                            ->from('users')
                            ->where('is_bot', 1);
                    })
                    ->whereBetween('date', [$startDate, $endDate])
                    ->distinct();
            })
                ->whereBetween('purchased_at', [$startDate, $endDate])
                ->where('price', '>', 0)
                ->where('status', 'succeeded')
                ->sum('price');

            $weeks[] = [
                'period' => $startDate->format('d.m') . ' - ' . $endDate->format('d.m'),
                'liked_users' => $likedUsersCount,
                'purchased_users' => $purchased,
                'conversion_rate' => $likedUsersCount > 0 ? round(($purchased / $likedUsersCount) * 100, 1) : 0,
                'revenue' => $revenue ?: 0
            ];
        }

        return $weeks;
    }

}
