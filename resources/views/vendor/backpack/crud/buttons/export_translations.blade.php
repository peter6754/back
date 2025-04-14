<div class="btn-group">
    <form action="{{ url($crud->route.'/export') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i class="la la-download"></i> Экспортировать переводы
        </button>
    </form>
</div>