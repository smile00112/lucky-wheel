<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $wheel->name ?? 'Колесо Фортуны' }}</title>
    <script src="https://unpkg.com/@vkid/sdk@2/dist/umd/index.js"></script>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
            min-height: 100vh;

        }
        .lucky-wheel-content::-webkit-scrollbar {
            width: 0;
        }
    </style>
</head>
<body>
<div class="lucky-wheel-content">
    @include('widget.wheel-v3')
</div>

</body>
</html>

