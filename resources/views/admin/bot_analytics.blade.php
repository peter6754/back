@extends(backpack_view('blank'))

@section('content')
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="la la-robot"></i> {{ $title }}</h2>
                <p class="text-muted">Статистика активности ботов и конверсии в покупки</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="la la-info-circle"></i> Общая информация по ботам</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border-right">
                                    <h3 class="text-primary">{{ number_format($bot_summary['total_bots']) }}</h3>
                                    <small class="text-muted">Всего ботов</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-right">
                                    <h3 class="text-success">{{ number_format($bot_summary['active_bots']) }}</h3>
                                    <small class="text-muted">Активные боты</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-right">
                                    <h3 class="text-warning">{{ number_format($bot_summary['inactive_bots']) }}</h3>
                                    <small class="text-muted">Неактивные боты</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-info">{{ $bot_summary['activity_percentage'] }}%</h3>
                                <small class="text-muted">Активность</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success"
                                     style="width: {{ $bot_summary['activity_percentage'] }}%">
                                </div>
                            </div>
                            <small class="text-muted">Процент активных ботов за последние 7 дней</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="la la-calendar-day"></i> Статистика за сегодня ({{ $today_analytics['date'] }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="border-right">
                                    <h4 class="text-primary">{{ number_format($today_analytics['total_likes']) }}</h4>
                                    <small class="text-muted">Всего лайков</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-right">
                                    <h4 class="text-info">{{ number_format($today_analytics['unique_liked_users']) }}</h4>
                                    <small class="text-muted">Получили лайки</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-right">
                                    <h4 class="text-success">{{ number_format($today_analytics['purchased_users']) }}</h4>
                                    <small class="text-muted">Купили подписку</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-right">
                                    <h4 class="text-warning">{{ $today_analytics['conversion_rate'] }}%</h4>
                                    <small class="text-muted">Конверсия</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h4 class="text-success">₽{{ number_format($today_analytics['total_revenue'], 2) }}</h4>
                                <small class="text-muted">Доход от конверсии</small>
                            </div>
                        </div>

                        @if($today_analytics['unique_liked_users'] > 0)
                            <div class="mt-3">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success"
                                         style="width: {{ $today_analytics['conversion_rate'] }}%">
                                    </div>
                                </div>
                                <small class="text-muted">Конверсия лайков в покупки</small>
                            </div>
                        @else
                            <div class="mt-3 text-center">
                                <i class="la la-info-circle text-info"></i>
                                <small class="text-muted">Сегодня активности ботов не зафиксировано</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="la la-chart-line"></i> Динамика за последние 7 дней</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Получили лайки</th>
                                    <th>Купили подписку</th>
                                    <th>Конверсия</th>
                                    <th>Прогресс</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($weekly_analytics as $day)
                                    <tr>
                                        <td><strong>{{ $day['date'] }}</strong></td>
                                        <td>
                                        <span class="badge badge-info">
                                            {{ number_format($day['liked_users']) }}
                                        </span>
                                        </td>
                                        <td>
                                        <span class="badge badge-success">
                                            {{ number_format($day['purchased_users']) }}
                                        </span>
                                        </td>
                                        <td>
                                            <strong class="text-warning">{{ $day['conversion_rate'] }}%</strong>
                                        </td>
                                        <td style="width: 200px;">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success"
                                                     style="width: {{ $day['conversion_rate'] }}%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="la la-chart-bar"></i> Анализ конверсии за последние 4 недели</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>Период</th>
                                    <th>Получили лайки</th>
                                    <th>Купили подписку</th>
                                    <th>Конверсия</th>
                                    <th>Доход</th>
                                    <th>Эффективность</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $revenues = collect($conversion_analytics)->pluck('revenue');
                                    $maxRevenue = $revenues->count() > 0 ? $revenues->max() : 0;
                                @endphp
                                @foreach($conversion_analytics as $week)
                                    <tr>
                                        <td><strong>{{ $week['period'] }}</strong></td>
                                        <td>
                                        <span class="badge badge-info badge-lg">
                                            {{ number_format($week['liked_users']) }}
                                        </span>
                                        </td>
                                        <td>
                                        <span class="badge badge-success badge-lg">
                                            {{ number_format($week['purchased_users']) }}
                                        </span>
                                        </td>
                                        <td>
                                        <span class="badge
                                            @if($week['conversion_rate'] >= 10) badge-success
                                            @elseif($week['conversion_rate'] >= 5) badge-warning
                                            @else badge-secondary
                                            @endif">
                                            {{ $week['conversion_rate'] }}%
                                        </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">₽{{ number_format($week['revenue'], 2) }}</strong>
                                        </td>
                                        <td style="width: 150px;">
                                            @if($maxRevenue > 0)
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-success"
                                                         style="width: {{ ($week['revenue'] / $maxRevenue) * 100 }}%">
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .border-right {
            border-right: 1px solid #dee2e6;
        }

        .border-right:last-child {
            border-right: none;
        }

        .badge-lg {
            font-size: 0.9em;
            padding: 0.5em 0.75em;
        }

        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .progress {
            border-radius: 4px;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
    </style>
@endsection
