<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добро пожаловать в LuckyWheel!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #ffffff;
        }
        .email-body {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .content-text {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .content-text p {
            margin-bottom: 15px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .email-footer {
            background-color: #2d3748;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer p {
            margin-bottom: 10px;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 25px 20px;
            }
            .email-header {
                padding: 30px 15px;
            }
            .email-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🎡 Добро пожаловать в LuckyWheel!</h1>
        </div>

        <div class="email-body">
            <div class="content-text">
                <p>Здравствуйте, <strong>{{ $user->name }}</strong>!</p>
                
                <p>Поздравляем с успешной регистрацией в системе LuckyWheel!</p>
                
                <p>Теперь вы можете создавать и настраивать колеса фортуны для вашего бизнеса, привлекать новых клиентов и увеличивать продажи.</p>
                
                <p>Для начала работы перейдите в админ-панель, используя ваши учетные данные:</p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li><strong>Email:</strong> {{ $user->email }}</li>
                </ul>
            </div>

            <div class="button-container">
                <a href="{{ $adminUrl }}" class="button">Перейти в админ-панель</a>
            </div>

            <div class="content-text">
                <p>Если у вас возникнут вопросы, мы всегда готовы помочь!</p>
                
                <p>С уважением,<br>Команда LuckyWheel</p>
            </div>
        </div>

        <div class="email-footer">
            <p><strong>LuckyWheel</strong></p>
            <p>Платформа для создания колеса фортуны</p>
            <p>© {{ date('Y') }} LuckyWheel. Все права защищены.</p>
        </div>
    </div>
</body>
</html>

