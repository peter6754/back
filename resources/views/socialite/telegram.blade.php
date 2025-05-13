<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(60% 55% at 85% 0%, #a84affa6, #a84aff00 60%), radial-gradient(70% 60% at 15% 35%, #ff52948c, #ff529400 62%), linear-gradient(180deg, #ff3b86, #ff4fa1 28%, #c133ff);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .header {
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .auth-card {
            border-radius: 16px;
            width: 100%;
        }

        .telegram-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0088cc;
            color: white;
            text-decoration: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            border: none;
            cursor: pointer;
        }

        .telegram-button:hover {
            background: #0077b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 136, 204, 0.3);
        }

        .telegram-button:active {
            transform: translateY(0);
        }

        .button-icon {
            margin-right: 10px;
            font-size: 20px;
        }

        .privacy-notice {
            margin-top: 20px;
            font-size: 12px;
            color: #fff;
            line-height: 1.4;
        }

        .privacy-notice a {
            text-underline-offset: 3px;
            text-decoration: underline;
            color: #fff;
        }

        /* Адаптивность для очень маленьких экранов */
        @media (max-width: 375px) {
            body {
                padding: 15px;
            }

            .header h1 {
                font-size: 20px;
            }

            .header p {
                font-size: 14px;
            }

            .auth-card {
                padding: 25px 15px;
            }

            .telegram-button {
                padding: 14px 20px;
                font-size: 15px;
            }
        }

        /* Поддержка landscape ориентации */
        @media (max-height: 500px) and (orientation: landscape) {
            body {
                padding: 10px;
            }

            .container {
                max-width: 350px;
            }

            .header {
                margin-bottom: 20px;
            }
        }

        /* Анимация появления */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Добро пожаловать</h1>
        <p>Войдите с помощью Telegram для продолжения</p>
    </div>

    <div class="auth-card">
        {!! Socialite::driver('telegram')->getButton() !!}

        <div class="privacy-notice">
            Нажимая кнопку, вы соглашаетесь с
            <a href="https://tinderone.ru/uslovija-ispolzovanija/" target="_blank">
                условиями использования
            </a>
            и
            <a href="https://tinderone.ru/privacy/" target="_blank">
                политикой конфиденциальности
            </a>
        </div>
    </div>
</div>

<script>
    // Добавляем базовую обработку для кнопки Telegram
    document.addEventListener('DOMContentLoaded', function () {
        const telegramButton = document.querySelector('.telegram-button');
        if (telegramButton) {
            telegramButton.addEventListener('click', function (e) {
                // Можно добавить индикатор загрузки
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';
            });
        }
    });
</script>
</body>
</html>