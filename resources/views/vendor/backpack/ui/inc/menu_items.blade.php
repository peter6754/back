{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-item title="Пользователи" icon="la la-question" :link="backpack_url('secondaryuser')" />
<x-backpack::menu-item title="Администраторы" icon="la la-question" :link="backpack_url('user')" />

<x-backpack::menu-item title="Подписки" icon="la la-question" :link="backpack_url('bought-subscriptions')" />

<x-backpack::menu-item title="Запросы верификации" icon="la la-question" :link="backpack_url('verification-requests')" />

<x-backpack::menu-item title="Очередь на удаление" icon="la la-question" :link="backpack_url('in-queue-for-delete-user')" />
