{{-- resources/views/vendor/backpack/crud/buttons/advertisement_manage_photos.blade.php --}}

@if ($crud->hasAccess('update'))
    <a href="{{ url('admin/advertisement/'.$entry->getKey().'/photos') }}"
       class="btn btn-sm btn-link"
       data-toggle="tooltip"
       title="Управление фотографиями">
        <i class="la la-images"></i> Фото ({{ $entry->images->count() }})
    </a>
@endif
