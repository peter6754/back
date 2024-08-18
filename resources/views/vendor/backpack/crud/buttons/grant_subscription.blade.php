@if ($entry->id)
    <a href="{{ url(config('backpack.base.route_prefix').'/bought-subscriptions/create?user_id='.$entry->id) }}"
       class="btn btn-lg btn-link">
        <i class="la la-gift"></i>
    </a>
@endif
