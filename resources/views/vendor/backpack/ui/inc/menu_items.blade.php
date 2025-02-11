<span class="nav-separator">Навигация</span>
{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item">
    <a class="nav-link" href="{{ backpack_url('dashboard') }}">
        <i class="la la-home nav-icon"></i>
        {{ trans('backpack::base.dashboard') }}
    </a>
</li>

@if (backpack_user()->can('access secondaryusers'))
    <x-backpack::menu-item title="Пользователи" icon="la la-question" :link="backpack_url('secondaryuser')"/>
@endif

@if (backpack_user()->can('access verification-requests'))
    <x-backpack::menu-item title="Запросы верификации" icon="la la-question"
                           :link="backpack_url('verification-requests')"/>
@endif

@if (backpack_user()->can('access queue-for-delete-user'))
    <x-backpack::menu-item title="Очередь на удаление" icon="la la-question"
                           :link="backpack_url('in-queue-for-delete-user')"/>
@endif

<span class="nav-separator">Финансы и прочее</span>
<x-backpack::menu-item title="Транзакции" icon="la la-question" :link="backpack_url('transaction-process')" />
@if (backpack_user()->can('access bought-subscriptions'))
    <x-backpack::menu-item title="Подписки" icon="la la-question" :link="backpack_url('bought-subscriptions')"/>
@endif


<span class="nav-separator">Администрирование</span>
@if(backpack_user()->hasRole('Superadmin'))
    <x-backpack::menu-dropdown title="Администраторы" icon="la la-lock">
        <x-backpack::menu-dropdown-item title="Пользователи" icon="la la-user" :link="backpack_url('user')"/>
        <x-backpack::menu-dropdown-item title="Права" icon="la la-key" :link="backpack_url('permission')"/>
        <x-backpack::menu-dropdown-item title="Роли" icon="la la-group" :link="backpack_url('role')"/>
    </x-backpack::menu-dropdown>
@endif

<x-backpack::menu-dropdown title="Почтовая система" icon="la la-envelope">
    <x-backpack::menu-dropdown-item title="Шаблоны писем" icon="la la-file-text-o" :link="backpack_url('mail-template')" />
    <x-backpack::menu-dropdown-item title="Очередь писем" icon="la la-list" :link="backpack_url('mail-queue')" />
    <x-backpack::menu-dropdown-item title="Отправить письмо" icon="la la-paper-plane" :link="backpack_url('send-mail')" />
</x-backpack::menu-dropdown>

<x-backpack::menu-item title="Статистика(временно)" icon="la la-question" :link="backpack_url('statistics')"/>

<span class="nav-separator">Утилиты</span>
<x-backpack::menu-item title='Log Manager' icon='la la-terminal' :link="backpack_url('log')" />
<li class="nav-item">
    <a class="nav-link" href="/admin/telescope" target="_blank">
        <i class="nav-icon la la-bug d-block d-lg-none d-xl-block"></i>
        <span>Telescope</span>
    </a>
</li>
<x-backpack::menu-item title='Backups' icon='la la-hdd-o' :link="backpack_url('backup')" />

<span class="nav-separator">Прочее</span>
<li class="nav-item">
    <a class="nav-link" href="/admin/seaweed" target="_blank">
        <i class="nav-icon la la-code d-block d-lg-none d-xl-block"></i>
        <span>Seaweed</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="/api/docs" target="_blank">
        <i class="nav-icon la la-code d-block d-lg-none d-xl-block"></i>
        <span>Swagger</span>
    </a>
</li>

