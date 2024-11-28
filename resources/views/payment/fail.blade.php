<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка оплаты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-card {
            width: 100%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 2rem;
        }

        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }

        .btn-back {
            background-color: #dc3545;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
        }

        .btn-try-again {
            margin-left: 10px;
        }
    </style>
</head>
<body>
<div class="card error-card text-center">
    <div class="error-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-x-circle-fill"
             viewBox="0 0 16 16">
            <path
                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z" />
        </svg>
    </div>
    <h2 class="card-title mb-3">Оплата не прошла</h2>

    <div class="card-text mb-4">
        {{ $errorMessage }}
    </div>

    @if(isset($orderId))
        <p class="text-muted mb-4">
            Номер заказа: <strong>#{{ $orderId }}</strong>
        </p>
    @endif

    <a href="tinderone://payment" class="btn btn-danger">
        Вернуться
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
