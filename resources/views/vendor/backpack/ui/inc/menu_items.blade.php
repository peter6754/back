{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>


@if (backpack_user()->can('access secondaryusers'))
<x-backpack::menu-item title="Пользователи" icon="la la-question" :link="backpack_url('secondaryuser')" />
@endif

@if(backpack_user()->hasRole('Superadmin'))
<x-backpack::menu-item title="Администраторы" icon="la la-question" :link="backpack_url('user')" />
@endif

@if (backpack_user()->can('access bought-subscriptions'))
<x-backpack::menu-item title="Подписки" icon="la la-question" :link="backpack_url('bought-subscriptions')" />
@endif

@if (backpack_user()->can('access verification-requests'))
<x-backpack::menu-item title="Запросы верификации" icon="la la-question" :link="backpack_url('verification-requests')" />
@endif

@if (backpack_user()->can('access queue-for-delete-user'))
<x-backpack::menu-item title="Очередь на удаление" icon="la la-question" :link="backpack_url('in-queue-for-delete-user')" />
@endif

@if(backpack_user()->hasRole('Superadmin'))
<x-backpack::menu-dropdown title="Управление доступом" icon="la la-lock">
    <x-backpack::menu-dropdown-item title="Пользователи" icon="la la-user" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Роли" icon="la la-group" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Права" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>
@endif

<x-backpack::menu-item title="Статистика(временно)" icon="la la-question" :link="backpack_url('statistics')" />
