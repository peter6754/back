@if(!$entry->isBanned())
    <a href="{{ backpack_url('user-ban/create?user_id=' . $entry->getKey()) }}" class="btn btn-sm btn-danger">
        <i class="la la-ban"></i> Забанить
    </a>
@endif
