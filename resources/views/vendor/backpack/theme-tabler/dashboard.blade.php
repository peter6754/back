@extends(backpack_view('blank'))

@php
    use Backpack\CRUD\app\Library\Widget;
    use App\Models\Secondaryuser;
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
        $newUsersMale = Secondaryuser::where('registration_date', '>=', Carbon::now()->subDays(7))
    ->where('gender', 'male')
    ->count();

        $newUsersFemale = Secondaryuser::where('registration_date', '>=', Carbon::now()->subDays(7))
    ->where('gender', 'female')
    ->count();

        $totalNewUsers = $newUsersMale + $newUsersFemale;
        $totalPercentage = $totalNewUsers > 0 ? (100 * $totalNewUsers / 1000) : 0;
        $malePercentage = $totalNewUsers > 0 ? (100 * $newUsersMale / $totalNewUsers) : 0;
        $femalePercentage = $totalNewUsers > 0 ? (100 * $newUsersFemale / $totalNewUsers) : 0;

        $onlineUsers = Secondaryuser::where('is_online', 1)->count();
        $totalUsers = Secondaryuser::count();
        $onlinePercentage = $totalUsers > 0 ? (100 * $onlineUsers / $totalUsers) : 0;


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
            ->description('Новых пользователей за 7 дней.')
            ->progress($totalPercentage)
            ->hint(1000 - $totalNewUsers .' до следующего этапа.'),

            Widget::make()
            ->type('progress')
            ->class('card mb-3')
            ->statusBorder('start')
            ->accentColor('blue')
            ->ribbon(['top', 'la-male'])
            ->progressClass('progressbar')
            ->value($newUsersMale)
            ->description('Мужчин за последние 7 дней.')
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
            ->description('Женщин за последние 7 дней.')
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
            ]);


@endphp

@section('content')
@endsection
