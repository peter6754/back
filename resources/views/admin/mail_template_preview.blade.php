@extends(backpack_view('blank'))

@section('content')
    <div class="container-fluid">
        <h1>Предварительный просмотр: {{ $template->name }}</h1>

        <div class="card">
            <div class="card-header">
                <h3>{{ $template->subject }}</h3>
            </div>
            <div class="card-body">
                <div class="border p-3">
                    {!! $template->html_body !!}
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('mail-template.index') }}" class="btn btn-secondary">Назад к списку</a>
                <a href="{{ route('mail-template.edit', $template->id) }}" class="btn btn-primary">Редактировать</a>
            </div>
        </div>
    </div>
@endsection
