@extends(backpack_view('blank'))

@php
    use App\Models\DeletedUserHimself;
    use App\Models\InQueueForDeleteUser;use App\Models\UserActivity;
    use Backpack\CRUD\app\Library\Widget;
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

        $totalNewUsersOneDay = Secondaryuser::where('registration_date', '>=', $startOfDayMoscow)
    ->count();

        $totalNewUsers = Secondaryuser::where('registration_date', '>=', $yesterdayStart)
    ->where('registration_date', '<', $todayStart)
    ->count();

        $onlineUsers = Secondaryuser::where('is_online', 1)->count();
        $totalUsers = Secondaryuser::count();
        $onlinePercentage = $totalUsers > 0 ? (100 * $onlineUsers / $totalUsers) : 0;
        $todayOnlineMan = UserActivity::getTodayOnlineMen();
        $todayOnlineWomen = UserActivity::getTodayOnlineWomen();
        $todayOnlineTotal = UserActivity::getTodayOnlineTotal();
        $yesterdayOnlineMen = UserActivity::getYesterdayOnlineMen();
        $yesterdayOnlineWomen = UserActivity::getYesterdayOnlineWomen();
        $yesterdayOnlineTotal = UserActivity::getYesterdayTotalOnline();
        $stats = Secondaryuser::getGenderStats();


        Widget::add()
        ->to('after_content')
        ->type('div')
        ->class('row mt-3')
        ->content([

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->wrapper(['style' => 'height: 120px;'])
            ->statusBorder('start')
            ->accentColor('red')
            ->ribbon(['top', 'la-sms'])
            ->progressClass('progressbar')
            ->value((new \App\Services\External\GreenSMSService)->getBalance())
            ->description('Баланс SMS'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('primary')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value("Вчера: $totalNewUsers<br>Сегодня: $totalNewUsersOneDay")
            ->description('Всего новых пользователей'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value("Вчера: $yesterdayOnlineTotal<br>Сегодня: $todayOnlineTotal")
            ->description('всего было онлайн'),

             Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->wrapper(['style' => 'height: 120px;'])
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-globe'])
            ->progressClass('progressbar')
            ->value("<br>$onlineUsers<br>")
            ->description('Пользователи онлайн сейчас.'),

             Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->wrapper(['style' => 'height: 120px;'])
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value($stats['total'])
            ->description('всего пользователей'),
        ]);




@endphp

@section('content')
@endsection
