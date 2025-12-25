<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $wheel->name ?? 'Колесо Фортуны' }}</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            background-color: #154a88;
            display: flex;
            flex-direction: column;
            align-items: center;
            /* padding: 20px 10px; */
            min-height: 100vh;

        }
        .lucky-wheel-content::-webkit-scrollbar {
            width: 0;
        }
        .lucky-wheel-content{
            padding: 0 !important;
        }
        .lucky-wheel-container{
            padding: 0px 20px !important;
        }
        .wheel-container {
            max-width: 500px !important;
        }
        .wheel {
            max-width: 500px !important;
            max-height: 500px !important;
        }
        .win-notification {
            width: 600px !important;
        }
        .lucky-wheel-content {
            max-height: 800px;
        }
        @media (min-width: 480px) {
            .wheel-content-contener{
                padding: 10px 0 !important;
                max-height: 800px !important;

            }
        }
        .win-notification {
            @media (max-width: 480px) {
                top: 0px !important;
                margin-top: -200px !important;
            }
        }
        .wheel-content-contener {
            @media (max-width: 480px) {
                padding: 10px 0 !important;
                height: 100vh !important;
                align-content: center;
            }
        }
    </style>
</head>
<body>
<div class="lucky-wheel-content">
    @include('widget.wheel-v3')
</div>

</body>
</html>

