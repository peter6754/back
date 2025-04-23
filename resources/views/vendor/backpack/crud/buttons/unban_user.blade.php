@if($entry->isBanned())
    <form action="{{ backpack_url('secondaryuser/unban/' . $entry->getKey()) }}" method="POST" style="display: inline;">
        @csrf
        @method('POST')
        <button type="submit" class="btn btn-sm btn-success">
            <i class="la la-user-check"></i> Разбанить
        </button>
    </form>
@endif
