@if($crud->hasAccess('delete'))
    <a href="{{ url($crud->route . '/' . $entry->getKey() . '/unban') }}" class="btn btn-sm btn-link">
        <i class="la la-user-check"></i> Снять бан
    </a>
@endif
