@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">Управление фотографиями пользователя</span>
            <small>{{ $user->name ?? 'ID: ' . $user->id }}</small>
        </h2>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-3">
                <a href="{{ url('admin/secondaryuser') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Назад к списку пользователей
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Добавить новые фотографии</h3>
                </div>
                <div class="card-body">
                    <form action="{{ url('admin/users/'.$user->id.'/photos') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Выберите фотографии:</label>
                            <input type="file" name="photo[]" class="form-control" multiple accept="image/*" required>
                            <small class="form-text text-muted">
                                Можно выбрать несколько файлов. Поддерживаемые форматы: JPEG, PNG, JPG, GIF. Максимальный размер файла: 2MB.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-upload"></i> Загрузить фотографии
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Текущие фотографии ({{ count($photos) }})</h3>
                </div>
                <div class="card-body">
                    @if(count($photos) > 0)
                        <div class="row">
                            @foreach($photos as $photo)
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card photo-card">
                                        <div class="position-relative">
                                            <img src="{{ $photo['url'] }}" class="card-img-top photo-preview"
                                                 style="height: 600px; object-fit: cover; cursor: pointer;">

                                            @if($photo['is_main'])
                                                <span class="badge badge-warning position-absolute" style="top: 10px; right: 10px;">
                                                <i class="fa fa-star"></i> Главное
                                            </span>
                                            @endif
                                        </div>

                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column gap-2">
                                                @if(!$photo['is_main'])
                                                    <form action="{{ url('admin/users/'.$user->id.'/photos/set-main') }}"
                                                          method="POST" class="mb-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="fid" value="{{ $photo['fid'] }}">
                                                        <button type="submit" class="btn btn-warning btn-sm btn-block">
                                                            <i class="fa fa-star"></i> Сделать главным
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="text-center py-1">
                                                        <small class="text-muted">Главное фото</small>
                                                    </div>
                                                @endif

{{--                                                <button class="btn btn-info btn-sm btn-block"--}}
{{--                                                        onclick="viewFullImage('{{ $photo['url'] }}')">--}}
{{--                                                    <i class="fa fa-eye"></i> Просмотр--}}
{{--                                                </button>--}}

                                                <form action="{{ url('admin/users/'.$user->id.'/photos/delete') }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Вы уверены, что хотите удалить это фото?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="fid" value="{{ $photo['fid'] }}">
                                                    <button type="submit" class="btn btn-danger btn-sm btn-block">
                                                        <i class="fa fa-trash"></i> Удалить
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-images fa-3x text-muted mb-3"></i>
                            <p class="text-muted">У пользователя пока нет фотографий</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Модалка --}}
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Просмотр фотографии</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="fullImage" src="" class="img-fluid" style="max-height: 70vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
    <script>
        function viewFullImage(imageUrl) {
            document.getElementById('fullImage').src = imageUrl;
            $('#imageModal').modal('show');
        }
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                let fileNames = Array.from(files).map(file => file.name).join(', ');
                if (fileNames.length > 50) {
                    fileNames = fileNames.substring(0, 50) + '...';
                }

                let preview = document.getElementById('file-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.id = 'file-preview';
                    preview.className = 'mt-2';
                    e.target.parentNode.appendChild(preview);
                }

                preview.innerHTML = `
            <small class="text-info">
                <i class="fa fa-check"></i> Выбрано файлов: ${files.length}
                <br>Файлы: ${fileNames}
            </small>
        `;
            }
        });
    </script>
@endsection

@section('after_styles')
    <style>
        .photo-card {
            transition: transform 0.2s;
            height: 100%;
        }

        .photo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .photo-preview {
            transition: opacity 0.2s;
        }

        .photo-preview:hover {
            opacity: 0.8;
        }

        .gap-2 > * {
            margin-bottom: 0.5rem;
        }

        .gap-2 > *:last-child {
            margin-bottom: 0;
        }
    </style>
@endsection
