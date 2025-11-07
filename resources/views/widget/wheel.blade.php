<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $wheel->name ?? 'Колесо Фортуны' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .description {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .wheel-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 20px;
            aspect-ratio: 1;
        }

        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            border: 10px solid #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .pointer {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 30px solid #ff4444;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .spin-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            width: 100%;
            max-width: 300px;
        }

        .spin-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .spin-button:active:not(:disabled) {
            transform: translateY(0);
        }

        .spin-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .result {
            margin-top: 20px;
            padding: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            display: none;
        }

        .result.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .result h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .result p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            margin-top: 20px;
            padding: 15px;
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 10px;
            color: #c33;
            display: none;
        }

        .error.show {
            display: block;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .spins-info {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
            }

            h1 {
                font-size: 1.5em;
            }

            .wheel-container {
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎡 {{ $wheel->name ?? 'Колесо Фортуны' }}</h1>
        @if($wheel->description)
        <div class="description">{{ $wheel->description }}</div>
        @endif
        
        <div id="loading" class="loading">Загрузка...</div>
        
        <div id="wheelContent" style="display: none;">
            <div class="wheel-container">
                <div class="pointer"></div>
                <canvas id="wheelCanvas" class="wheel"></canvas>
            </div>
            
            <button id="spinButton" class="spin-button">Крутить колесо!</button>
            <div id="spinsInfo" class="spins-info"></div>
            
            <div id="result" class="result">
                <h2>🎉 Поздравляем!</h2>
                <p id="resultText"></p>
            </div>
        </div>
        
        <div id="error" class="error"></div>
    </div>

    <script>
        // Конфигурация
        const API_URL = '{{ url("/api/widget") }}';
        const WHEEL_SLUG = '{{ $wheel->slug }}';
        const GUEST_ID = new URLSearchParams(window.location.search).get('guest_id');

        // Состояние
        let wheelData = null;
        let prizes = [];
        let isSpinning = false;
        let currentRotation = 0;
        let canvas = null;
        let ctx = null;
        let centerX = 0;
        let centerY = 0;
        let radius = 0;

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            if (!GUEST_ID) {
                showError('Гость не определен');
                return;
            }

            loadWheelData();
        });

        // Загрузка данных колеса
        async function loadWheelData() {
            try {
                const response = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}`);
                if (!response.ok) {
                    throw new Error('Не удалось загрузить данные колеса');
                }
                
                wheelData = await response.json();
                prizes = wheelData.prizes || [];
                
                if (prizes.length === 0) {
                    showError('Нет доступных призов');
                    return;
                }

                initWheel();
                updateSpinsInfo();
            } catch (error) {
                console.error('Error loading wheel:', error);
                showError('Ошибка загрузки данных: ' + error.message);
            }
        }

        // Инициализация колеса
        function initWheel() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('wheelContent').style.display = 'block';

            canvas = document.getElementById('wheelCanvas');
            ctx = canvas.getContext('2d');
            
            const container = canvas.parentElement;
            const size = Math.min(container.clientWidth - 20, 400);
            canvas.width = size;
            canvas.height = size;
            
            centerX = canvas.width / 2;
            centerY = canvas.height / 2;
            radius = Math.min(centerX, centerY) - 10;

            drawWheel();
            
            // Уведомление родительского окна о готовности
            notifyParent('ready', {});
        }

        // Нормализация вероятностей
        function normalizeProbabilities(prizes) {
            const total = prizes.reduce((sum, p) => sum + (parseFloat(p.probability) || 0), 0);
            if (total === 0) {
                const equalProb = 100 / prizes.length;
                return prizes.map(p => ({ ...p, probability: equalProb }));
            }
            return prizes.map(p => ({
                ...p,
                probability: (parseFloat(p.probability) || 0) * 100 / total
            }));
        }

        // Рисование колеса
        function drawWheel(rotation = 0) {
            if (!ctx) return;
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const normalizedPrizes = normalizeProbabilities(prizes);
            const totalAngle = 2 * Math.PI;
            let currentAngle = -Math.PI / 2 + rotation;
            
            normalizedPrizes.forEach((prize, index) => {
                const angle = (prize.probability / 100) * totalAngle;
                
                // Рисуем сектор
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle);
                ctx.closePath();
                
                // Цвет сектора
                const color = prize.color || getColorByIndex(index);
                ctx.fillStyle = color;
                ctx.fill();
                
                // Обводка
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // Текст
                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(currentAngle + angle / 2);
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#fff';
                ctx.font = `bold ${Math.max(10, radius / 20)}px Arial`;
                
                // Обрезка длинного текста
                const maxWidth = radius * 0.7;
                let text = prize.name;
                let metrics = ctx.measureText(text);
                if (metrics.width > maxWidth) {
                    while (metrics.width > maxWidth && text.length > 0) {
                        text = text.substring(0, text.length - 1);
                        metrics = ctx.measureText(text + '...');
                    }
                    text = text + '...';
                }
                
                ctx.fillText(text, radius * 0.6, 0);
                ctx.restore();
                
                currentAngle += angle;
            });
        }

        // Получить цвет по индексу
        function getColorByIndex(index) {
            const colors = [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A',
                '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2',
                '#F8B739', '#E74C3C', '#3498DB', '#2ECC71'
            ];
            return colors[index % colors.length];
        }

        // Найти индекс приза по ID
        function findPrizeIndex(prizeId) {
            return prizes.findIndex(p => p.id === prizeId);
        }

        // Вычислить угол для выбранного приза
        function getPrizeAngle(prizeIndex) {
            const normalizedPrizes = normalizeProbabilities(prizes);
            let cumulativeAngle = -Math.PI / 2;
            
            for (let i = 0; i < normalizedPrizes.length; i++) {
                if (i === prizeIndex) {
                    return cumulativeAngle + (normalizedPrizes[i].probability / 100) * Math.PI;
                }
                cumulativeAngle += (normalizedPrizes[i].probability / 100) * 2 * Math.PI;
            }
            
            return cumulativeAngle;
        }

        // Выполнить вращение
        async function spin() {
            if (isSpinning) return;
            
            isSpinning = true;
            const spinButton = document.getElementById('spinButton');
            const result = document.getElementById('result');
            spinButton.disabled = true;
            result.classList.remove('show');
            hideError();

            try {
                // Отправка запроса на сервер
                const response = await fetch(`${API_URL}/spin`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        wheel_slug: WHEEL_SLUG,
                        guest_id: parseInt(GUEST_ID),
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Ошибка вращения');
                }

                // Уведомление родительского окна о вращении
                notifyParent('spin', data);

                // Найти индекс выигранного приза
                let prizeIndex = -1;
                if (data.prize) {
                    prizeIndex = findPrizeIndex(data.prize.id);
                }

                // Анимация вращения
                await animateSpin(prizeIndex, data);

                // Обновление информации о вращениях
                updateSpinsInfo(data.spins_count, data.spins_limit);

                // Показ результата
                if (data.prize) {
                    showResult(data.prize);
                    notifyParent('win', data.prize);
                } else {
                    showResult(null);
                }

            } catch (error) {
                console.error('Spin error:', error);
                showError('Ошибка: ' + error.message);
                notifyParent('error', { message: error.message });
            } finally {
                isSpinning = false;
                
                // Проверка лимита вращений
                if (wheelData.spins_limit) {
                    // Обновим информацию после завершения
                    setTimeout(() => {
                        loadWheelData();
                    }, 500);
                } else {
                    spinButton.disabled = false;
                }
            }
        }

        // Анимация вращения
        function animateSpin(prizeIndex, spinData) {
            return new Promise((resolve) => {
                const normalizedPrizes = normalizeProbabilities(prizes);
                
                // Если приз не выигран, останавливаемся в случайном месте
                let finalAngle = 0;
                if (prizeIndex >= 0 && prizeIndex < normalizedPrizes.length) {
                    finalAngle = getPrizeAngle(prizeIndex);
                } else {
                    // Случайный угол
                    finalAngle = -Math.PI / 2 + Math.random() * 2 * Math.PI;
                }

                const spins = 5; // Количество полных оборотов
                const finalRotation = currentRotation + (spins * 2 * Math.PI) + (2 * Math.PI - finalAngle);
                
                const startRotation = currentRotation;
                const rotationDiff = finalRotation - startRotation;
                const duration = 4000;
                const startTime = Date.now();
                
                function animate() {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    currentRotation = startRotation + rotationDiff * easeOut;
                    
                    drawWheel(currentRotation);
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        currentRotation = finalRotation;
                        drawWheel(currentRotation);
                        resolve();
                    }
                }
                
                animate();
            });
        }

        // Показать результат
        function showResult(prize) {
            const result = document.getElementById('result');
            const resultText = document.getElementById('resultText');
            const spinButton = document.getElementById('spinButton');
            
            if (prize) {
                resultText.innerHTML = `
                    <strong>Вы выиграли: ${prize.name}</strong><br>
                    ${prize.text_for_winner ? prize.text_for_winner : ''}
                `;
            } else {
                resultText.textContent = 'К сожалению, вы ничего не выиграли. Попробуйте еще раз!';
            }
            
            result.classList.add('show');
            
            // Проверка лимита вращений
            if (wheelData.spins_limit) {
                // Лимит проверяется после загрузки данных
            } else {
                spinButton.disabled = false;
            }
        }

        // Обновить информацию о вращениях
        function updateSpinsInfo(spinsCount = null, spinsLimit = null) {
            const infoEl = document.getElementById('spinsInfo');
            if (!wheelData.spins_limit) {
                infoEl.textContent = '';
                return;
            }
            
            if (spinsCount !== null && spinsLimit !== null) {
                infoEl.textContent = `Вращений: ${spinsCount} / ${spinsLimit}`;
            } else {
                infoEl.textContent = `Лимит вращений: ${wheelData.spins_limit}`;
            }
        }

        // Показать ошибку
        function showError(message) {
            document.getElementById('loading').style.display = 'none';
            const errorEl = document.getElementById('error');
            errorEl.textContent = message;
            errorEl.classList.add('show');
        }

        // Скрыть ошибку
        function hideError() {
            document.getElementById('error').classList.remove('show');
        }

        // Уведомить родительское окно
        function notifyParent(action, data) {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'lucky-wheel',
                    action: action,
                    data: data,
                }, '*');
            }
        }

        // Обработчик кнопки
        document.getElementById('spinButton').addEventListener('click', spin);

        // Обработка сообщений от родительского окна
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'lucky-wheel') {
                if (event.data.action === 'spin') {
                    spin();
                }
            }
        });
    </script>
</body>
</html>

