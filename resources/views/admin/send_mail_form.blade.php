// resources/views/admin/send_mail_form.blade.php
@extends(backpack_view('blank'))

@section('content')
    <div class="container-fluid">
        <h1>Отправить письмо</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('send-mail.send') }}">
                    @csrf

                    <div class="form-group">
                        <label>Шаблон письма</label>
                        <select name="template_id" class="form-control" id="template-select" required>
                            <option value="">Выберите шаблон</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }} - {{ $template->subject }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Email получателя</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Имя получателя (опционально)</label>
                        <input type="text" name="name" class="form-control">
                    </div>

                    <div id="variables-block" class="form-group" style="display: none;">
                        <label>Переменные для шаблона (JSON)</label>
                        <textarea name="variables" class="form-control" rows="5" id="variables-textarea" placeholder='{"name": "Иван", "date": "2024-01-01"}'></textarea>
                        <small class="form-text text-muted">
                            Доступные переменные: <span id="available-variables"></span>
                        </small>
                    </div>

                    <div id="preview-block" style="display: none;">
                        <h5>Предварительный просмотр</h5>
                        <div class="border p-3" id="preview-content"></div>
                    </div>

                    <button type="submit" class="btn btn-primary">Отправить письмо</button>
                    <a href="{{ route('mail-queue.index') }}" class="btn btn-secondary">Посмотреть очередь</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template-select');
            const variablesBlock = document.getElementById('variables-block');
            const previewBlock = document.getElementById('preview-block');
            const variablesTextarea = document.getElementById('variables-textarea');
            const availableVariables = document.getElementById('available-variables');
            const previewContent = document.getElementById('preview-content');

            // Загрузка данных шаблона
            templateSelect.addEventListener('change', function() {
                if (this.value) {
                    fetch(`{{ route('send-mail.template', '') }}/${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.variables && data.variables.length > 0) {
                                variablesBlock.style.display = 'block';
                                availableVariables.textContent = data.variables.join(', ');

                                // Генерируем пример JSON
                                const exampleVars = {};
                                data.variables.forEach(variable => {
                                    exampleVars[variable] = `пример_${variable}`;
                                });
                                variablesTextarea.placeholder = JSON.stringify(exampleVars, null, 2);
                            } else {
                                variablesBlock.style.display = 'none';
                            }

                            previewBlock.style.display = 'block';
                            previewContent.innerHTML = data.html_preview;
                        });
                } else {
                    variablesBlock.style.display = 'none';
                    previewBlock.style.display = 'none';
                }
            });
        });
    </script>
@endsection
