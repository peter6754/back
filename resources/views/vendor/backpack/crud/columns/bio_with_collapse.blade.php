@php
    // Получаем текущую запись через переменную $entry
    $modalId = 'bioModal' . $entry->id; // Уникальный ID модального окна для каждой записи

    $shortText = \Illuminate\Support\Str::limit($entry->userInformation?->bio, 15); // Короткий текст
    $fullText = $entry->userInformation?->bio; // Полный текст
@endphp

<span>
    {{ $shortText }}
    <p class="d-inline-flex gap-1">
        <a data-bs-toggle="collapse" href="#{{ $modalId }}" role="button" aria-expanded="false" aria-controls="{{ $modalId }}">
            подробно
        </a>
    </p>
</span>

<div class="collapse" id="{{ $modalId }}">
    <div class="card card-body" style="white-space: normal">
        <h5 class="card-title">О себе</h5>
        <p>{{ $fullText }}</p>
        <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $modalId }}" aria-expanded="true" aria-controls="{{ $modalId }}">
            Close
        </button>
    </div>
</div>
