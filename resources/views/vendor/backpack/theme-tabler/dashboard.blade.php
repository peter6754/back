@extends(backpack_view('blank'))

@php
    use App\Models\UserActivity;use Backpack\CRUD\app\Library\Widget;
    use App\Models\Secondaryuser;
    use App\Models\DeletedUser;
    use Carbon\Carbon;
        if (backpack_theme_config('show_getting_started')) {
            $widgets['before_content'][] = [
                'type'        => 'view',
                'view'        => backpack_view('inc.getting_started'),
            ];
        } else {
            $widgets['before_content'][] = [
                'type'        => 'jumbotron',
                'heading'     => trans('backpack::base.welcome'),
                'heading_class' => 'display-3 '.(backpack_theme_config('layout') === 'horizontal_overlap' ? ' text-white' : ''),
                'content'     => trans('backpack::base.use_sidebar'),
                'content_class' => backpack_theme_config('layout') === 'horizontal_overlap' ? 'text-white' : '',
                'button_link' => backpack_url('logout'),
                'button_text' => trans('backpack::base.logout'),
            ];
        }
        $startOfDayMoscow = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        $yesterdayStart = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');

        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        $newUsersMale = Secondaryuser::where('registration_date', '>=', $yesterdayStart)
    ->where('registration_date', '<', $todayStart)
    ->where('gender', 'male')
    ->count();

        $newUsersFemale = Secondaryuser::where('registration_date', '>=', $yesterdayStart)
    ->where('registration_date', '<', $todayStart)
    ->where('gender', 'female')
    ->count();

        $newUsersMaleOneDay = Secondaryuser::where('registration_date', '>=', $startOfDayMoscow)
    ->where('gender', 'male')
    ->count();

        $newUsersFemaleOneDay = Secondaryuser::where('registration_date', '>=', $startOfDayMoscow)
    ->where('gender', 'female')
    ->count();

        $totalNewUsersOneDay = Secondaryuser::where('registration_date', '>=', $startOfDayMoscow)
    ->count();
        $totalOneDayPercentage = $totalNewUsersOneDay > 0 ? (100 * $totalNewUsersOneDay / 1000) : 0;
        $maleOneDayPercentage = $totalNewUsersOneDay > 0 ? (100 * $newUsersMaleOneDay / $totalNewUsersOneDay) : 0;
        $femaleOneDayPercentage = $totalNewUsersOneDay > 0 ? (100 * $newUsersFemaleOneDay / $totalNewUsersOneDay) : 0;

        $totalNewUsers = Secondaryuser::where('registration_date', '>=', $yesterdayStart)
    ->where('registration_date', '<', $todayStart)
    ->count();
        $totalPercentage = $totalNewUsers > 0 ? (100 * $totalNewUsers / 1000) : 0;
        $malePercentage = $totalNewUsers > 0 ? (100 * $newUsersMale / $totalNewUsers) : 0;
        $femalePercentage = $totalNewUsers > 0 ? (100 * $newUsersFemale / $totalNewUsers) : 0;

        $onlineUsers = Secondaryuser::where('is_online', 1)->count();
        $totalUsers = Secondaryuser::count();
        $onlinePercentage = $totalUsers > 0 ? (100 * $onlineUsers / $totalUsers) : 0;

        $deletedUsersOneDay = DeletedUser::where('date', '>=', Carbon::now()->subDay())
    ->count();
        $deletedUsersOneDayPercentage = $totalUsers > 0 ? (100 * $deletedUsersOneDay / $totalUsers) : 0;
        $deletedUsersTotal = Secondaryuser::where('mode', 'deleted')->count();

        $todayOnlineMan = UserActivity::getTodayOnlineMen();
        $todayOnlineWomen = UserActivity::getTodayOnlineWomen();
        $todayOnlineTotal = UserActivity::getTodayOnlineTotal();
        $yesterdayOnlineMen = UserActivity::getYesterdayOnlineMen();
        $yesterdayOnlineWomen = UserActivity::getYesterdayOnlineWomen();
        $yesterdayOnlineTotal = UserActivity::getYesterdayTotalOnline();


        Widget::add()
        ->to('after_content')
        ->type('div')
        ->class('row mt-3')
        ->content([
            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('primary')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($totalNewUsers)
            ->description('Новых пользователей вчера.')
            ->progress($totalPercentage)
            ->hint(5000 - $totalNewUsers .' до следующего этапа.'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('blue')
            ->ribbon(['top', 'la-male'])
            ->progressClass('progressbar')
            ->value($newUsersMale)
            ->description('Мужчин вчера.')
            ->progress($malePercentage)
            ->hint($totalNewUsers > 0 ? 'Из общего числа пользователей.' : 'Нет новых пользователей.'),

        Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('pink')
            ->ribbon(['top', 'la-female'])
            ->progressClass('progressbar')
            ->value($newUsersFemale)
            ->description('Женщин вчера.')
            ->progress($femalePercentage)
            ->hint($totalNewUsers > 0 ? 'Из общего числа пользователей.' : 'Нет новых пользователей.'),

        Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-globe'])
            ->progressClass('progressbar')
            ->value($onlineUsers)
            ->description('Пользователи онлайн сейчас.')
            ->progress($onlinePercentage)
            ->hint($totalUsers > 0 ? 'Из общего числа пользователей.' : 'Нет пользователей.'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('primary')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($totalNewUsersOneDay)
            ->description('Новых пользователей сегодня.')
            ->progress($totalOneDayPercentage)
            ->hint(5000 - $totalNewUsersOneDay .' до следующего этапа.'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('blue')
            ->ribbon(['top', 'la-male'])
            ->progressClass('progressbar')
            ->value($newUsersMaleOneDay)
            ->description('Мужчин сегодня.')
            ->progress($maleOneDayPercentage)
            ->hint($totalNewUsersOneDay > 0 ? 'Из общего числа пользователей.' : 'Нет новых пользователей.'),

        Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('pink')
            ->ribbon(['top', 'la-female'])
            ->progressClass('progressbar')
            ->value($newUsersFemaleOneDay)
            ->description('Женщин сегодня.')
            ->progress($femaleOneDayPercentage)
            ->hint($totalNewUsersOneDay > 0 ? 'Из общего числа пользователей.' : 'Нет новых пользователей.'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('red')
            ->ribbon(['top', 'la-globe'])
            ->progressClass('progressbar')
            ->value($deletedUsersOneDay)
            ->description('Пользователи удалено за день.')
            ->progress($deletedUsersOneDayPercentage)
            ->hint($totalUsers > 0 ? 'Из общего числа пользователей.' : 'Нет пользователей.'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($totalUsers)
            ->description('всего пользователей'),

             Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('red')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($deletedUsersTotal)
            ->description('всего пользователей удалено'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($todayOnlineMan)
            ->description('всего было онлайн за сегодня мужчин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($todayOnlineWomen)
            ->description('всего было онлайн за сегодня женщин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($todayOnlineTotal)
            ->description('всего было онлайн за сегодня'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($yesterdayOnlineMen)
            ->description('всего было онлайн вчера оналайн мужчин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($yesterdayOnlineWomen)
            ->description('всего было вчера онлайн женщин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($yesterdayOnlineTotal)
            ->description('всего было вчера онлайн'),
            ]);




@endphp

@section('content')
@endsection
