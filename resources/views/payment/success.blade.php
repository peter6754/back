<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата прошла успешно</title>
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
        .success-card {
            width: 100%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 2rem;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        .btn-back {
            background-color: #28a745;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
<div class="card success-card text-center">
    <div class="success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
        </svg>
    </div>
    <h2 class="card-title mb-3">Оплата прошла успешно!</h2>
    <p class="card-text mb-4">
        Заказ <strong>#{{ $orderId }}</strong> успешно обработан.
    </p>
    <p class="text-muted mb-4">
        Чек отправлен на вашу почту.
    </p>
    <a href="https://tinderone.ru" class="btn btn-success">
        Вернуться на главную
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
