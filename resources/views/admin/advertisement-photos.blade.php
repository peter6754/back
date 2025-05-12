@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2>
            <span class="text-capitalize">Управление изображениями рекламы</span>
            <small>{{ $advertisement->title }}</small>
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
                <a href="{{ url('admin/advertisement') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Назад к списку рекламы
                </a>
                <a href="{{ url('admin/advertisement/'.$advertisement->id.'/show') }}" class="btn btn-info">
                    <i class="fa fa-eye"></i> Просмотр рекламы
                </a>
            </div>

            <!-- Информация о рекламе -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Название:</strong> {{ $advertisement->title }}</p>
                            <p><strong>Ссылка:</strong>
                                @if($advertisement->link)
                                    <a href="{{ $advertisement->link }}" target="_blank">{{ $advertisement->link }}</a>
                                @else
                                    <span class="text-muted">Не указана</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Показы:</strong>
                                {{ $advertisement->impressions_count }}
                                @if($advertisement->impressions_limit > 0)
                                    / {{ $advertisement->impressions_limit }}
                                @else
                                    / ∞
                                @endif
                            </p>
                            <p><strong>Статус:</strong>
                                @if($advertisement->isCurrentlyActive())
                                    <span class="badge badge-success">Активна</span>
                                @else
                                    <span class="badge badge-secondary">Неактивна</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Форма загрузки -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Добавить новые банеры</h3>
                </div>
                <div class="card-body">
                    <form action="{{ url('admin/advertisement/'.$advertisement->id.'/photos') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Выберите фотографии:</label>
                            <input type="file" name="photo[]" class="form-control" multiple accept="image/*" required>
                            <small class="form-text text-muted">
                                Можно выбрать несколько файлов. Поддерживаемые форматы: JPEG, PNG, JPG, GIF, WEBP. Максимальный размер файла: 10MB.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-upload"></i> Загрузить банеры
                        </button>
                    </form>
                </div>
            </div>

            <!-- Текущие банеры -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Текущие банеры ({{ count($photos) }})</h3>
                </div>
                <div class="card-body">
                    @if(count($photos) > 0)
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            Первое (основное) изображение будет отображаться в списке рекламы и показываться пользователям по умолчанию.
                        </div>
                        <div class="row">
                            @foreach($photos as $photo)
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card photo-card">
                                        <div class="position-relative">
                                            <img src="{{ $photo['url'] }}"
                                                 class="card-img-top photo-preview"
                                                 style="height: 400px; object-fit: cover; cursor: pointer;"
                                                 >

                                            @if($photo['is_primary'])
                                                <span class="badge badge-warning position-absolute" style="top: 10px; right: 10px;">
                                                    <i class="fa fa-star"></i> Основное
                                                </span>
                                            @endif

                                            <div class="position-absolute" style="bottom: 10px; left: 10px; right: 10px;">
                                                <span class="badge badge-dark" style="opacity: 0.8;">
                                                    Порядок: {{ $photo['order'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column gap-2">
                                                @if($photo['original_name'])
                                                    <small class="text-muted text-truncate" title="{{ $photo['original_name'] }}">
                                                        {{ $photo['original_name'] }}
                                                    </small>
                                                @endif

                                                @if(!$photo['is_primary'])
                                                    <form action="{{ url('admin/advertisement/'.$advertisement->id.'/photos/set-primary') }}"
                                                          method="POST" class="mb-1">
                                                        @csrf
                                                        <input type="hidden" name="fid" value="{{ $photo['fid'] }}">
                                                        <button type="submit" class="btn btn-warning btn-sm btn-block">
                                                            <i class="fa fa-star"></i> Сделать главным
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="text-center py-1">
                                                        <small class="text-muted">Главный банер</small>
                                                    </div>
                                                @endif

{{--                                                <button class="btn btn-info btn-sm btn-block"--}}
{{--                                                        onclick="viewFullImage('{{ $photo['url'] }}')">--}}
{{--                                                    <i class="fa fa-eye"></i> Просмотр--}}
{{--                                                </button>--}}

                                                <form action="{{ url('admin/advertisement/'.$advertisement->id.'/photos') }}"
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
                            <p class="text-muted">У рекламы пока нет фотографий</p>
                            <p class="text-muted">Добавьте хотя бы одно изображение для показа рекламы</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка просмотра -->
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

        // Предпросмотр выбранных файлов
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

        .position-relative {
            position: relative;
        }

        .position-absolute {
            position: absolute;
        }
    </style>
@endsection
