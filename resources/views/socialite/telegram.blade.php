<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            background-color: #f0f0f0;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            display: flex;
            padding: 0;
            margin: 0;
        }

        .centered-iframe {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 2px solid #333;
            border-radius: 8px;
            height: 80vh;
            width: 80%;
        }
    </style>
</head>
<body>
{!! Socialite::driver('telegram')->getButton() !!}
</body>
</html>