@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      'Аналитика по городам' => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Аналитика пользователей по городам</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        @foreach ($breadcrumbs as $key => $value)
                            @if ($value === false)
                                <li class="breadcrumb-item active">{{ $key }}</li>
                            @else
                                <li class="breadcrumb-item"><a href="{{ $value }}">{{ $key }}</a></li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <div class="row">
        {{-- Сводная информация --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Сводная статистика
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ number_format($summary->total_cities ?? 0) }}</h3>
                                    <p>Городов</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-city"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ number_format($summary->total_users ?? 0) }}</h3>
                                    <p>Всего пользователей</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>{{ number_format($summary->total_males ?? 0) }}</h3>
                                    <p>Мужчины</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-mars"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($summary->total_females ?? 0) }}</h3>
                                    <p>Женщины</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-venus"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ number_format($summary->users_without_city ?? 0) }}</h3>
                                    <p>Без города</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-question"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3>
                                        @if($summary->total_users > 0)
                                            {{ round($summary->total_males / $summary->total_users * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </h3>
                                    <p>Мужчины/Женщины</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Аналитика покупок по возрастам --}}
        <div class="col-12">
            <div class="row">
                {{-- Мужчины --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-mars text-primary"></i>
                                Покупки мужчин по возрастам
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>Возрастная группа</th>
                                        <th class="text-center">Покупок</th>
                                        <th class="text-center">Покупателей</th>
                                        <th class="text-center">Средне на чел.</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $totalMalePurchases = $age_analytics['male']->sum('purchase_count');
                                        $totalMaleBuyers = $age_analytics['male']->sum('unique_users');
                                    @endphp
                                    @forelse($age_analytics['male'] as $item)
                                        <tr>
                                            <td><strong>{{ $item->age_group }}</strong></td>
                                            <td class="text-center">
                                            <span class="badge badge-dark-text bg-white text-dark badge-lg">
                                                {{ number_format($item->purchase_count) }}
                                            </span>
                                                @if($totalMalePurchases > 0)
                                                    <br><small class="text-muted">
                                                        {{ round($item->purchase_count / $totalMalePurchases * 100, 1) }}%
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                            <span class="badge badge-primary bg-white text-dark badge-lg">
                                                {{ number_format($item->unique_users) }}
                                            </span>
                                            </td>
                                            <td class="text-center">
                                            <span class="font-weight-bold text-info">
                                                @if($item->unique_users > 0)
                                                    {{ round($item->purchase_count / $item->unique_users, 1) }}
                                                @else
                                                    0
                                                @endif
                                            </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                Нет данных о покупках мужчин
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                    @if($age_analytics['male']->count() > 0)
                                        <tfoot class="bg-light">
                                        <tr>
                                            <th>Итого</th>
                                            <th class="text-center">{{ number_format($totalMalePurchases) }}</th>
                                            <th class="text-center">{{ number_format($totalMaleBuyers) }}</th>
                                            <th class="text-center">
                                                @if($totalMaleBuyers > 0)
                                                    {{ round($totalMalePurchases / $totalMaleBuyers, 1) }}
                                                @else
                                                    0
                                                @endif
                                            </th>
                                        </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Женщины --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-venus text-danger"></i>
                                Покупки женщин по возрастам
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>Возрастная группа</th>
                                        <th class="text-center">Покупок</th>
                                        <th class="text-center">Покупателей</th>
                                        <th class="text-center">Средне на чел.</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $totalFemalePurchases = $age_analytics['female']->sum('purchase_count');
                                        $totalFemaleBuyers = $age_analytics['female']->sum('unique_users');
                                    @endphp
                                    @forelse($age_analytics['female'] as $item)
                                        <tr>
                                            <td><strong>{{ $item->age_group }}</strong></td>
                                            <td class="text-center">
                                            <span class="badge badge-dark-text bg-white text-dark badge-lg">
                                                {{ number_format($item->purchase_count) }}
                                            </span>
                                                @if($totalFemalePurchases > 0)
                                                    <br><small class="text-muted">
                                                        {{ round($item->purchase_count / $totalFemalePurchases * 100, 1) }}%
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                            <span class="badge badge-info bg-white text-dark badge-lg">
                                                {{ number_format($item->unique_users) }}
                                            </span>
                                            </td>
                                            <td class="text-center">
                                            <span class="font-weight-bold text-info">
                                                @if($item->unique_users > 0)
                                                    {{ round($item->purchase_count / $item->unique_users, 1) }}
                                                @else
                                                    0
                                                @endif
                                            </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                Нет данных о покупках женщин
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                    @if($age_analytics['female']->count() > 0)
                                        <tfoot class="bg-light">
                                        <tr>
                                            <th>Итого</th>
                                            <th class="text-center">{{ number_format($totalFemalePurchases) }}</th>
                                            <th class="text-center">{{ number_format($totalFemaleBuyers) }}</th>
                                            <th class="text-center">
                                                @if($totalFemaleBuyers > 0)
                                                    {{ round($totalFemalePurchases / $totalFemaleBuyers, 1) }}
                                                @else
                                                    0
                                                @endif
                                            </th>
                                        </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Таблица с данными --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table"></i>
                        Детальная статистика по городам
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="analyticsTable">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 35%">Город</th>
                                <th style="width: 13%" class="text-center">Мужчины</th>
                                <th style="width: 13%" class="text-center">Женщины</th>
                                <th style="width: 13%" class="text-center">Всего</th>
                                <th style="width: 13%" class="text-center">% Мужчин</th>
                                <th style="width: 13%" class="text-center">% Женщин</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($analytics_data as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->city }}</strong>
                                        @if($item->city == 'Не указан')
                                            <small class="text-muted"><i class="fas fa-exclamation-triangle"></i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                    <span class="badge badge-primary bg-white badge-lg text-dark">
                                        {{ number_format($item->male_count) }}
                                    </span>
                                    </td>
                                    <td class="text-center">
                                    <span class="badge badge-info bg-white badge-lg text-dark">
                                        {{ number_format($item->female_count) }}
                                    </span>
                                    </td>
                                    <td class="text-center">
                                    <span class="badge badge-success bg-white badge-lg text-dark">
                                        {{ number_format($item->total_count) }}
                                    </span>
                                    </td>
                                    <td class="text-center">
                                    <span class="text-primary font-weight-bold">
                                        {{ $item->male_percentage }}%
                                    </span>
                                    </td>
                                    <td class="text-center">
                                    <span class="text-info font-weight-bold">
                                        {{ $item->female_percentage }}%
                                    </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        Нет данных для отображения
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


