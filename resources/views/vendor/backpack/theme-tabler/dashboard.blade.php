@extends(backpack_view('blank'))

@php
    use App\Models\DeletedUserHimself;
    use App\Models\UserActivity;
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

        $totalNewUsers = Secondaryuser::where('registration_date', '>=', $yesterdayStart)
    ->where('registration_date', '<', $todayStart)
    ->count();

        $onlineUsers = Secondaryuser::where('is_online', 1)->count();
        $totalUsers = Secondaryuser::count();
        $onlinePercentage = $totalUsers > 0 ? (100 * $onlineUsers / $totalUsers) : 0;

        $deletedUsersOneDay = DeletedUser::where('date', '>=', Carbon::now()->subDay())
    ->count();
        $deletedUsersOneDayPercentage = $totalUsers > 0 ? (100 * $deletedUsersOneDay / $totalUsers) : 0;
        $deletedUsersTotal = DeletedUserHimself::countDeletedUsers();

        $todayOnlineMan = UserActivity::getTodayOnlineMen();
        $todayOnlineWomen = UserActivity::getTodayOnlineWomen();
        $todayOnlineTotal = UserActivity::getTodayOnlineTotal();
        $yesterdayOnlineMen = UserActivity::getYesterdayOnlineMen();
        $yesterdayOnlineWomen = UserActivity::getYesterdayOnlineWomen();
        $yesterdayOnlineTotal = UserActivity::getYesterdayTotalOnline();
        $stats = Secondaryuser::getGenderStats();
        $valueText = "Мужчины: {$stats['male']}<br>";
        $valueText .= "Женщины: {$stats['female']}<br>";
        $valueText .= "М+Ж: {$stats['m_f']}<br>";
        $valueText .= "М+М: {$stats['m_m']}<br>";
        $valueText .= "Ж+Ж: {$stats['f_f']}<br>";
        $valueText .= "Всего: {$stats['total']}";


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
            ->value("Вчера: $totalNewUsers<br>Сегодня: $totalNewUsersOneDay")
            ->description('Всего новых пользователей'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('blue')
            ->ribbon(['top', 'la-male'])
            ->progressClass('progressbar')
            ->value("Вчера: $newUsersMale<br>Сегодня: $newUsersMaleOneDay")
            ->description('Новых пользователей мужчин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('pink')
            ->ribbon(['top', 'la-female'])
            ->progressClass('progressbar')
            ->value("Вчера: $newUsersFemale<br>Сегодня: $newUsersFemaleOneDay")
            ->description('Новых пользователей женщин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value("Вчера: $yesterdayOnlineMen<br>Сегодня: $todayOnlineMan")
            ->description('всего было онлайн мужчин'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-user'])
            ->progressClass('progressbar')
            ->value("Вчера: $yesterdayOnlineWomen<br>Сегодня: $todayOnlineWomen")
            ->description('всего было онлайн женщин'),

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
            ->statusBorder('start')
            ->accentColor('green')
            ->ribbon(['top', 'la-globe'])
            ->progressClass('progressbar')
            ->value($onlineUsers)
            ->description('Пользователи онлайн сейчас.'),

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
            ->value($valueText)
            ->description('всего пользователей'),

            ]);




@endphp

@section('content')
@endsection
