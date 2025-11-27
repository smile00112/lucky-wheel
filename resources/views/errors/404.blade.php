<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Страница не найдена</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
            position: relative;
        }

        .error-code {
            font-size: 120px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            line-height: 1;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2em;
        }

        p {
            color: #666;
            margin-bottom: 40px;
            font-size: 16px;
        }

        .wheel-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto 40px;
        }

        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            border: 10px solid #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .wheel-section {
            position: absolute;
            width: 50%;
            height: 50%;
            transform-origin: right bottom;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 10px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .wheel-section span {
            transform: rotate(45deg);
            max-width: 70px;
            word-wrap: break-word;
        }

        .pointer {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-top: 40px solid #ff4444;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .home-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            text-decoration: none;
            display: inline-block;
        }

        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .home-button:active {
            transform: translateY(0);
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 80px;
            }

            h1 {
                font-size: 1.5em;
            }

            .wheel-container {
                width: 250px;
                height: 250px;
            }

            .container {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <h1>Страница не найдена</h1>
        <p>К сожалению, запрашиваемая страница не существует.<br>Возможно, она была удалена или перемещена.</p>

        <div class="wheel-container">
            <div class="pointer"></div>
            <canvas id="wheelCanvas" class="wheel" width="300" height="300"></canvas>
        </div>

        <a href="{{ url('/') }}" class="home-button">Вернуться на главную</a>
    </div>

    <script>
        const canvas = document.getElementById('wheelCanvas');
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 10;

        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A',
            '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'
        ];

        const messages = [
            '404', 'Ошибка', 'Не найдено', 'Упс!',
            '404', 'Ошибка', 'Не найдено', 'Упс!'
        ];

        function drawWheel() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const sections = 8;
            const anglePerSection = (2 * Math.PI) / sections;

            for (let i = 0; i < sections; i++) {
                const startAngle = -Math.PI / 2 + (i * anglePerSection);
                const endAngle = startAngle + anglePerSection;

                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();

                ctx.fillStyle = colors[i % colors.length];
                ctx.fill();

                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();

                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(startAngle + anglePerSection / 2);
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 14px Arial';
                ctx.fillText(messages[i], radius * 0.6, 0);
                ctx.restore();
            }
        }

        drawWheel();
    </script>
</body>
</html>



