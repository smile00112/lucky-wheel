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

        /* Всплывающее уведомление о выигрыше */
        .win-notification {
            position: fixed;
            bottom: -200px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            max-width: 500px;
            width: calc(100% - 40px);
            transition: bottom 0.5s ease;
            animation: slideUpNotification 0.5s ease forwards;
        }

        .win-notification.show {
            bottom: 20px;
        }

        .win-notification h3 {
            margin: 0 0 15px 0;
            font-size: 1.3em;
            text-align: center;
        }

        .win-notification-message {
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
        }

        .win-notification-code {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 12px;
        }

        .win-notification-code input {
            flex: 1;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            color: #333;
            font-family: 'Courier New', monospace;
        }

        .win-notification-code input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
        }

        .win-notification-code input::placeholder {
            color: #999;
            font-weight: normal;
        }

        .win-notification-code button {
            background: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .win-notification-code button:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        .win-notification-code button:active {
            transform: scale(0.95);
        }

        .win-notification-code button svg {
            width: 20px;
            height: 20px;
            fill: #667eea;
        }

        .win-notification-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: none; /* Скрываем, но не удаляем */
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
            transition: all 0.3s ease;
        }

        .win-notification-close:hover {
            background: rgba(255, 255, 255, 0.3);
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

        @keyframes slideUpNotification {
            from {
                bottom: -200px;
                opacity: 0;
            }
            to {
                bottom: 20px;
                opacity: 1;
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

            .win-notification {
                width: calc(100% - 20px);
                padding: 15px 20px;
                bottom: 10px;
            }

            .win-notification h3 {
                font-size: 1.1em;
            }

            .win-notification-code {
                flex-direction: column;
            }

            .win-notification-code button {
                width: 100%;
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

    <!-- Всплывающее уведомление о выигрыше -->
    <div id="winNotification" class="win-notification" style="display: none;">
        <button class="win-notification-close" onclick="hideWinNotification()">&times;</button>
        <h3>🎉 Поздравляем с выигрышем!</h3>
        <div class="win-notification-message" id="winNotificationMessage"></div>
        <div class="win-notification-code" id="winNotificationCodeContainer">
            <input type="text" id="winNotificationCode" readonly value="">
            <button onclick="copyPrizeCode(event)" title="Копировать код">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
        // Конфигурация
        const API_URL = '{{ url("/api/widget") }}';
        const WHEEL_SLUG = '{{ $wheel->slug }}';
        let GUEST_ID = new URLSearchParams(window.location.search).get('guest_id');

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
        let prizeImages = {}; // Кэш загруженных изображений

        // Создать или получить гостя
        async function createOrGetGuest() {
            // Проверяем localStorage
            const guestKey = `lucky_wheel_guest_${WHEEL_SLUG}`;
            const savedGuestId = localStorage.getItem(guestKey);

            if (savedGuestId) {
                GUEST_ID = savedGuestId;
                return GUEST_ID;
            }

            try {
                const response = await fetch(`${API_URL}/guest`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        wheel_slug: WHEEL_SLUG,
                    }),
                });

                if (!response.ok) {
                    // Пытаемся получить детали ошибки
                    let errorMessage = 'Не удалось создать гостя';
                    try {
                        const errorData = await response.json();
                        if (errorData.error) {
                            errorMessage = errorData.error;
                        } else if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        // Если не удалось распарсить JSON, используем стандартное сообщение
                    }
                    throw new Error(errorMessage);
                }

                const data = await response.json();

                // API возвращает 'id', а не 'guest_id'
                const guestId = data.id || data.guest_id;

                // Проверяем, что ID гостя есть в ответе
                if (!data || !guestId) {
                    console.error('API response:', data);
                    throw new Error('Не получен ID гостя от сервера. Ответ: ' + JSON.stringify(data));
                }

                GUEST_ID = String(guestId);

                if (!GUEST_ID) {
                    throw new Error('Не удалось получить ID гостя');
                }

                // Сохраняем в localStorage
                localStorage.setItem(guestKey, GUEST_ID);

                return GUEST_ID;
            } catch (error) {
                console.error('Error creating guest:', error);
                console.error('API URL:', `${API_URL}/guest`);
                showError('Ошибка инициализации: ' + error.message);
                return null;
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', async function() {
            // Если нет guest_id, создаем или получаем гостя
            if (!GUEST_ID) {
                GUEST_ID = await createOrGetGuest();
                if (!GUEST_ID) {
                    return;
                }
            }

            // Проверяем, был ли выигрыш сегодня
            checkTodayWin();

            loadWheelData();

            // Проверяем каждую минуту, не наступила ли полуночь
            setInterval(function() {
                checkTodayWin();
            }, 60000); // Каждую минуту
        });

        // Проверить выигрыш сегодня
        async function checkTodayWin() {
            // Сначала проверяем localStorage
            const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
            const winData = localStorage.getItem(todayWinKey);

            if (winData) {
                try {
                    const win = JSON.parse(winData);
                    const winDate = new Date(win.date);
                    const today = new Date();

                    // Проверяем, что это сегодня (не вчера)
                    if (winDate.toDateString() === today.toDateString()) {
                        // Получаем код из сохраненных данных или из объекта prize
                        const prizeCode = win.code || (win.prize && win.prize.value) || null;
                        // Показываем уведомление о выигрыше
                        showWinNotification(win.prize, prizeCode);
                        // Блокируем вращение
                        blockSpinning();
                        return;
                    } else {
                        // Прошла полночь - удаляем старые данные и разблокируем
                        localStorage.removeItem(todayWinKey);
                        unblockSpinning();
                        hideWinNotification();
                    }
                } catch (e) {
                    console.error('Error parsing win data:', e);
                }
            }

            // Также проверяем на сервере (на случай очистки localStorage)
            try {
                const response = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}/today-win?guest_id=${GUEST_ID}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.has_win && data.prize) {
                        // Сохраняем в localStorage
                        const prizeCode = data.prize.value || null;
                        saveWin(data.prize, prizeCode);
                        // Показываем уведомление
                        showWinNotification(data.prize, prizeCode);
                        // Блокируем вращение
                        blockSpinning();
                    } else {
                        // Нет выигрыша сегодня - проверяем, не заблокировано ли вращение
                        // (на случай если localStorage очищен, но кнопка заблокирована)
                        const spinButton = document.getElementById('spinButton');
                        if (spinButton && spinButton.disabled && spinButton.textContent.includes('уже выиграли')) {
                            unblockSpinning();
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking today win:', error);
            }
        }

        // Сохранить выигрыш
        function saveWin(prize, code) {
            const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
            const winData = {
                date: new Date().toISOString(),
                prize: prize,
                code: code
            };
            localStorage.setItem(todayWinKey, JSON.stringify(winData));
        }

        // Заблокировать вращение
        function blockSpinning() {
            const spinButton = document.getElementById('spinButton');
            if (spinButton) {
                spinButton.disabled = true;
                spinButton.textContent = 'Вы уже выиграли сегодня. Попробуйте завтра!';
                spinButton.style.cursor = 'not-allowed';
            }
        }

        // Разблокировать вращение
        function unblockSpinning() {
            const spinButton = document.getElementById('spinButton');
            if (spinButton) {
                spinButton.disabled = false;
                spinButton.textContent = 'Крутить колесо!';
                spinButton.style.cursor = 'pointer';
            }
        }

        // Показать уведомление о выигрыше
        function showWinNotification(prize, code) {
            const notification = document.getElementById('winNotification');
            const message = document.getElementById('winNotificationMessage');
            const codeInput = document.getElementById('winNotificationCode');
            const codeContainer = document.getElementById('winNotificationCodeContainer');

            if (!prize) {
                return;
            }

            // Формируем сообщение
            let messageText = `<strong>Вы выиграли: ${prize.name}</strong>`;
            if (prize.text_for_winner) {
                messageText += `<br>${prize.text_for_winner}`;
            }
            message.innerHTML = messageText;

            // Определяем код приза
            // Если код не передан явно, пытаемся взять из объекта prize
            let prizeCode = code;
            if (!prizeCode && prize && prize.value) {
                prizeCode = prize.value;
            }

            // Всегда показываем поле с кодом
            codeContainer.style.display = 'flex';

            // Если кода нет, показываем placeholder и очищаем поле
            if (!prizeCode || !prizeCode.toString().trim()) {
                codeInput.placeholder = 'Код не указан';
                codeInput.value = '';
            } else {
                // Если код есть, устанавливаем его и убираем placeholder
                codeInput.placeholder = '';
                codeInput.value = prizeCode.toString().trim();
            }

            notification.style.display = 'block';
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
        }

        // Скрыть уведомление о выигрыше
        function hideWinNotification() {
            const notification = document.getElementById('winNotification');
            notification.classList.remove('show');
            setTimeout(() => {
                notification.style.display = 'none';
            }, 500);
        }

        // Копировать код приза
        function copyPrizeCode(event) {
            const codeInput = document.getElementById('winNotificationCode');
            const code = codeInput.value;

            if (!code) {
                return;
            }

            // Используем современный API, если доступен
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(() => {
                    showCopyFeedback(event);
                }).catch(() => {
                    // Fallback на старый метод
                    copyToClipboardFallback(codeInput, event);
                });
            } else {
                // Fallback на старый метод
                copyToClipboardFallback(codeInput, event);
            }
        }

        // Старый метод копирования (fallback)
        function copyToClipboardFallback(input, event) {
            input.select();
            input.setSelectionRange(0, 99999); // Для мобильных устройств

            try {
                document.execCommand('copy');
                showCopyFeedback(event);
            } catch (err) {
                console.error('Failed to copy:', err);
                alert('Не удалось скопировать код. Пожалуйста, скопируйте вручную: ' + input.value);
            }
        }

        // Показать обратную связь при копировании
        function showCopyFeedback(event) {
            const button = event.target.closest('button');
            if (!button) return;

            const originalHTML = button.innerHTML;
            button.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#28a745" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
            button.style.background = '#d4edda';

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.background = 'white';
            }, 2000);
        }

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

                // Загружаем изображения призов
                await loadPrizeImages();

                initWheel();
                updateSpinsInfo();
            } catch (error) {
                console.error('Error loading wheel:', error);
                showError('Ошибка загрузки данных: ' + error.message);
            }
        }

        // Загрузить изображения призов
        async function loadPrizeImages() {
            const imagePromises = prizes.map(async (prize) => {
                if (prize.image) {
                    return new Promise((resolve) => {
                        const img = new Image();
                        // Используем crossOrigin только для внешних URL
                        if (prize.image.startsWith('http://') || prize.image.startsWith('https://')) {
                            // Для внешних URL проверяем, тот ли это домен
                            const currentOrigin = window.location.origin;
                            const imageUrl = new URL(prize.image);
                            if (imageUrl.origin !== currentOrigin) {
                                img.crossOrigin = 'anonymous';
                            }
                        }

                        img.onload = () => {
                            prizeImages[prize.id] = img;
                            resolve();
                        };
                        img.onerror = () => {
                            console.warn('Failed to load image for prize:', prize.id, prize.image);
                            prizeImages[prize.id] = null;
                            resolve();
                        };
                        img.src = prize.image;
                    });
                } else {
                    prizeImages[prize.id] = null;
                    return Promise.resolve();
                }
            });

            await Promise.all(imagePromises);

            // Перерисовываем колесо после загрузки всех изображений
            if (canvas && ctx) {
                drawWheel(currentRotation);
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

            // Перерисовываем колесо после загрузки изображений (если они есть)
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

                // Рисуем изображение или текст
                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(currentAngle + angle / 2);

                const prizeImage = prizeImages[prize.id];

                if (0 && prizeImage && prize.image) {
                    // Вычисляем размер изображения по размеру секции
                    // Ширина сектора на средней линии (на половине радиуса)
                    const midRadius = radius * 0.65; // Средняя точка сектора
                    const sectorWidth = 2 * Math.sin(angle / 2) * midRadius;
                    const sectorHeight = radius * 0.8; // Высота секции (80% радиуса)

                    // Пропорции исходного изображения
                    const imageAspectRatio = prizeImage.width / prizeImage.height;
                    const sectorAspectRatio = sectorWidth / sectorHeight;

                    // Вычисляем размер изображения с сохранением пропорций
                    let imageWidth, imageHeight;
                    if (imageAspectRatio > sectorAspectRatio) {
                        // Изображение шире - масштабируем по ширине
                        imageWidth = sectorWidth * 0.95;
                        imageHeight = imageWidth / imageAspectRatio;
                    } else {
                        // Изображение выше - масштабируем по высоте
                        imageHeight = sectorHeight * 0.95;
                        imageWidth = imageHeight * imageAspectRatio;
                    }

                    // Позиция изображения - в центре сектора
                    const imageDistance = midRadius;
                    const imageX = imageDistance;
                    const imageY = 0;

                    // Сохраняем состояние для клиппинга
                    ctx.save();

                    // Создаем клиппинг путь для сектора (чтобы изображение не выходило за границы)
                    ctx.beginPath();
                    ctx.moveTo(0, 0);
                    ctx.arc(0, 0, radius * 0.98, -angle / 2 - 0.05, angle / 2 + 0.05);
                    ctx.closePath();
                    ctx.clip();

                    // Поворачиваем изображение на 180 градусов, чтобы нижняя часть была к центру
                    ctx.save();
                    ctx.translate(imageX, imageY);
                   // ctx.rotate(Math.PI); // Поворот на 180 градусов
                    ctx.rotate(1.5);
                    // Рисуем изображение с центрированием
                    try {
                        ctx.drawImage(
                            prizeImage,
                            -imageWidth / 2,
                            -imageHeight / 2,
                            imageWidth,
                            imageHeight
                        );
                    } catch (e) {
                        console.warn('Error drawing image:', e);
                        // Если ошибка при отрисовке изображения, показываем текст
                        ctx.restore();
                        ctx.restore();
                        ctx.restore();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = '#fff';
                        ctx.font = `bold ${Math.max(30, (radius / 20) * 3)}px Arial`;
                        ctx.fillText(prize.name, radius * 0.6, 0);
                    }

                    ctx.restore(); // Восстанавливаем поворот
                    ctx.restore(); // Восстанавливаем клиппинг
                } else {
                    // Рисуем текст (если нет изображения)
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = '#fff';
                    ctx.font = `bold ${Math.max(30, (radius / 20) * 3)}px Arial`;

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
                }

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
                    // Обработка случая, когда уже выиграли сегодня
                    if (data.error === 'Already won today' && data.today_win) {
                        const prize = data.today_win.prize;
                        const prizeCode = prize.value || null;
                        saveWin(prize, prizeCode);
                        // Показываем уведомление вместо ошибки
                        setTimeout(() => {
                            showWinNotification(prize, prizeCode);
                        }, 100);
                        blockSpinning();
                        // Не показываем ошибку, только блокируем вращение
                        isSpinning = false;
                        return;
                    }
                    throw new Error(data.error || data.message || 'Ошибка вращения');
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

                    // Сохраняем выигрыш и показываем уведомление
                    // Код приза берем из поля value
                    const prizeCode = data.prize.value || null;
                    saveWin(data.prize, prizeCode);

                    // Показываем уведомление с задержкой после анимации
                    // Передаем код явно, если он есть
                    setTimeout(() => {
                        showWinNotification(data.prize, prizeCode);
                    }, 500);

                    // Блокируем дальнейшие вращения сегодня
                    blockSpinning();
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

