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
            flex-direction: column;
            /*justify-content: center;*/
            justify-content: flex-start;
            align-items: center;
            padding: 10px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 100%;
            max-height: 100%;
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

        .won-prize-block {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 11;
            text-align: center;
            min-width: 150px;
            animation: slideDownPrize 0.5s ease;
        }

        .won-prize-label {
            font-size: 11px;
            opacity: 0.9;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .won-prize-name {
            font-size: 14px;
            font-weight: bold;
            white-space: nowrap;
        }

        @keyframes slideDownPrize {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        @media (max-width: 768px) {
            .won-prize-block {
                top: 15px;
                padding: 8px 15px;
                min-width: 120px;
            }

            .won-prize-label {
                font-size: 10px;
            }

            .won-prize-name {
                font-size: 12px;
            }
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
            position: relative;
            z-index: 1;
        }

        .error.show {
            display: block;
        }

        /* Ошибка поверх секции выигрыша */
        .error.show.error-overlay {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10001; /* Выше секции выигрыша (z-index: 10000) */
            max-width: 500px;
            width: calc(100% - 40px);
            margin-top: 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDownError 0.3s ease forwards;
        }

        @keyframes slideDownError {
            from {
                top: -100px;
                opacity: 0;
            }
            to {
                top: 20px;
                opacity: 1;
            }
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
            border-radius: 15px 15px 0 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            max-width: 451px;
            width: calc(100% - 40px);
            min-height: 44%;
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
            flex-direction: row;
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
            transition: all 0.3s ease;
        }

        .win-notification-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .win-notification-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .win-notification-form-text {
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
            opacity: 0.9;
        }

        .win-notification-form-group {
            margin-bottom: 12px;
        }

        .win-notification-form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .win-notification-form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: white;
        }

        .win-notification-form-group input::placeholder {
            color: #999;
        }

        .win-notification-submit-btn {
            width: 100%;
            padding: 14px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .win-notification-submit-btn:hover:not(:disabled) {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .win-notification-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .win-notification-send-container {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
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
                bottom: 0px;
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
                flex-direction: row;
            }

            .win-notification-code button {
                /*width: 100%;*/
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
                <!-- Блок выигранного приза под стрелкой -->
                <div id="wonPrizeBlock" class="won-prize-block" style="display: none;">
                    <div class="won-prize-label">Выиграно сегодня:</div>
{{--                    <div class="won-prize-name" id="wonPrizeName"></div>--}}
                    <div class="won-prize-name" id="wonPrizeCode"></div>
                </div>
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

        <!-- Форма для получения приза -->
        <div class="win-notification-form" id="winNotificationFormContainer" style="display: none;">
            <p class="win-notification-form-text">Для получения приза на почту заполните данные:</p>
            <form id="winNotificationForm" onsubmit="submitPrizeForm(event)">
                <div class="win-notification-form-group">
                    <input type="text" id="winNotificationName" name="name" placeholder="Ваше имя" required>
                </div>
                <div class="win-notification-form-group">
                    <input type="email" id="winNotificationEmail" name="email" placeholder="Email" required>
                </div>
                <div class="win-notification-form-group">
                    <input type="tel" id="winNotificationPhone" name="phone" placeholder="+7 (XXX) XXX-XX-XX" required maxlength="18">
                </div>
                <button type="submit" class="win-notification-submit-btn" id="winNotificationSubmitBtn">
                    Отправить приз
                </button>
            </form>
        </div>

        <!-- Кнопка отправки приза (если данные уже заполнены) -->
        <div class="win-notification-send-container" id="winNotificationSendContainer" style="display: none;">
            <button type="button" class="win-notification-submit-btn"  id="winNotificationSubmitBtn2" onclick="submitPrizeForm(event)">
                Отправить приз
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

            // Применяем маску для поля телефона
            const phoneInput = document.getElementById('winNotificationPhone');
            if (phoneInput) {
                applyPhoneMask(phoneInput);
            }

            // Проверяем, был ли выигрыш сегодня
            checkTodayWin();

            loadWheelData();

            // Проверяем каждую минуту, не наступила ли полуночь
            setInterval(function() {
                checkTodayWin();
            }, 60000); // Каждую минуту
        });

        // Применить маску для российского номера телефона
        function applyPhoneMask(input) {
            if (!input) return;

            // Обработчик ввода
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Удаляем все нецифровые символы

                // Если начинается с 8, заменяем на 7
                if (value.startsWith('8')) {
                    value = '7' + value.substring(1);
                }

                // Ограничиваем до 11 цифр
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }

                // Форматируем номер
                let formattedValue = '';
                if (value.length > 0) {
                    formattedValue = '+7';
                    if (value.length > 1) {
                        formattedValue += ' (' + value.substring(1, 4);
                        if (value.length >= 4) {
                            formattedValue += ') ' + value.substring(4, 7);
                            if (value.length >= 7) {
                                formattedValue += '-' + value.substring(7, 9);
                                if (value.length >= 9) {
                                    formattedValue += '-' + value.substring(9, 11);
                                }
                            }
                        }
                    }
                }

                e.target.value = formattedValue;
            });

            // Обработчик вставки (paste)
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                let pastedText = (e.clipboardData || window.clipboardData).getData('text');
                let value = pastedText.replace(/\D/g, '');

                if (value.startsWith('8')) {
                    value = '7' + value.substring(1);
                }

                if (value.length > 11) {
                    value = value.substring(0, 11);
                }

                let formattedValue = '';
                if (value.length > 0) {
                    formattedValue = '+7';
                    if (value.length > 1) {
                        formattedValue += ' (' + value.substring(1, 4);
                        if (value.length >= 4) {
                            formattedValue += ') ' + value.substring(4, 7);
                            if (value.length >= 7) {
                                formattedValue += '-' + value.substring(7, 9);
                                if (value.length >= 9) {
                                    formattedValue += '-' + value.substring(9, 11);
                                }
                            }
                        }
                    }
                }

                input.value = formattedValue;
            });

            // Обработчик удаления (backspace)
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && input.value.length <= 4) {
                    e.preventDefault();
                    input.value = '';
                }
            });
        }

        // Показать блок выигранного приза под стрелкой
        function showWonPrizeBlock(prize, prizeCode) {
            const block = document.getElementById('wonPrizeBlock');
            // const nameElement = document.getElementById('wonPrizeName');
            const nameElement = document.getElementById('wonPrizeCode');

            if (block && nameElement && prize) {
                //nameElement.textContent = prize.name;
                nameElement.textContent = prizeCode;
                block.style.display = 'block';
            }

        }

        // Скрыть блок выигранного приза
        function hideWonPrizeBlock() {
            const block = document.getElementById('wonPrizeBlock');
            if (block) {
                block.style.display = 'none';
            }
        }

        // Применить поворот колеса для выигранного приза
        function applyWonPrizeRotation(prize) {
            if (!prize || !prizes || prizes.length === 0) {
                console.warn('Cannot apply rotation: prize or prizes array is empty', { prize, prizesLength: prizes?.length });
                return;
            }

            // Вычисляем поворот для размещения приза под стрелкой
            const rotation = calculateRotationForPrize(prize.id);
            console.log('Applying rotation for prize:', {
                prizeId: prize.id,
                prizeName: prize.name,
                rotation: rotation,
                rotationDegrees: (rotation * 180 / Math.PI).toFixed(2)
            });

            currentRotation = rotation;

            // Перерисовываем колесо с новым поворотом
            if (canvas && ctx) {
                drawWheel(currentRotation);
            }
        }

        // Применить поворот колеса для выигранного приза, если он есть
        async function applyWonPrizeRotationIfNeeded() {
            if (!prizes || prizes.length === 0) {
                return;
            }

            // Проверяем localStorage
            const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
            const winData = localStorage.getItem(todayWinKey);

            if (winData) {
                try {
                    const win = JSON.parse(winData);
                    const winDate = new Date(win.date);
                    const today = new Date();

                    // Проверяем, что это сегодня (не вчера)
                    if (winDate.toDateString() === today.toDateString() && win.prize) {
                        applyWonPrizeRotation(win.prize);
                        return;
                    }
                } catch (e) {
                    console.error('Error parsing win data:', e);
                }
            }

            // Также проверяем на сервере
            try {
                const response = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}/today-win?guest_id=${GUEST_ID}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.has_win && data.prize) {
                        applyWonPrizeRotation(data.prize);
                    }
                }
            } catch (error) {
                console.error('Error checking today win for rotation:', error);
            }
        }

        // Проверить выигрыш сегодня
        async function checkTodayWin() {
            console.log('____checkTodayWin')
            // Сначала проверяем localStorage
            const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
            const winData = localStorage.getItem(todayWinKey);
            console.log(winData)

            if (winData) {
                try {
                    const win = JSON.parse(winData);
                    const winDate = new Date(win.date);
                    const today = new Date();

                    // Проверяем, что это сегодня (не вчера)
                    if (winDate.toDateString() === today.toDateString()) {
                    // Получаем код из сохраненных данных
                    const prizeCode = win.code || null;
                    // Получаем информацию о заполненных данных из сохраненных данных
                    const guestHasData = win.guest_has_data !== undefined ? win.guest_has_data : null;
                        // Показываем уведомление о выигрыше
                        showWinNotification(win.prize, prizeCode, guestHasData);
                        // Показываем блок выигранного приза под стрелкой
                        showWonPrizeBlock(win.prize, prizeCode);
                        // Применяем поворот колеса для выигранного приза
                        applyWonPrizeRotation(win.prize);
                        // Блокируем вращение
                        blockSpinning();
                        return;
                    } else {
                        // Прошла полночь - удаляем старые данные и разблокируем
                        localStorage.removeItem(todayWinKey);
                        unblockSpinning();
                        hideWinNotification();
                        hideWonPrizeBlock();
                        // Сбрасываем поворот
                        currentRotation = 0;
                        if (canvas && ctx) {
                            drawWheel(currentRotation);
                        }
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
                        // Сохраняем в localStorage (включая информацию о заполненных данных и spin_id)
                        const prizeCode = data.code || null;
                        const spinId = data.spin_id || null;
                        saveWin(data.prize, prizeCode, data.guest_has_data || false, spinId);

                        // Показываем уведомление (передаем информацию о заполненных данных)
                        showWinNotification(data.prize, prizeCode, data.guest_has_data);
                        // Показываем блок выигранного приза под стрелкой
                        showWonPrizeBlock(data.prize, prizeCode);
                        // Применяем поворот колеса для выигранного приза
                        applyWonPrizeRotation(data.prize);
                        // Блокируем вращение
                        blockSpinning();
                    } else {
                        // Нет выигрыша сегодня - проверяем, не заблокировано ли вращение
                        // (на случай если localStorage очищен, но кнопка заблокирована)
                        const spinButton = document.getElementById('spinButton');
                        if (spinButton && spinButton.disabled && spinButton.textContent.includes('уже выиграли')) {
                            unblockSpinning();
                        }
                        // Скрываем блок выигранного приза
                        hideWonPrizeBlock();
                        // Сбрасываем поворот
                        currentRotation = 0;
                        if (canvas && ctx) {
                            drawWheel(currentRotation);
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking today win:', error);
            }
        }

        // Сохранить выигрыш
        function saveWin(prize, code, guestHasData = null, spinId = null) {
            const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
            const winData = {
                date: new Date().toISOString(),
                prize: prize,
                code: code,
                guest_has_data: guestHasData,
                spin_id: spinId
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
        async function showWinNotification(prize, code, guestHasDataParam = null) {
            const notification = document.getElementById('winNotification');
            const message = document.getElementById('winNotificationMessage');
            const codeInput = document.getElementById('winNotificationCode');
            const codeContainer = document.getElementById('winNotificationCodeContainer');
            const formContainer = document.getElementById('winNotificationFormContainer');
            const sendContainer = document.getElementById('winNotificationSendContainer');

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
            // Код передается явно в параметре code
            let prizeCode = code;

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

            // Проверяем, заполнены ли данные гостя
            let guestHasData = guestHasDataParam; // Используем переданный параметр, если есть

            // Если параметр не передан, проверяем через API
            if (guestHasData === null || guestHasData === undefined) {
                // Сначала проверяем информацию из ответа getTodayWin (для случая, когда выигрыш найден по IP)
                try {
                    const todayWinResponse = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}/today-win?guest_id=${GUEST_ID}`);
                    if (todayWinResponse.ok) {
                        const todayWinData = await todayWinResponse.json();
                        if (todayWinData.has_win && todayWinData.guest_has_data !== undefined) {
                            guestHasData = todayWinData.guest_has_data;
                        }
                    }
                } catch (e) {
                    console.warn('Could not check guest data from today-win:', e);
                }

                // Если не получили информацию из today-win, проверяем через guest info
                if (guestHasData === null || guestHasData === undefined) {
                    try {
                        const response = await fetch(`${API_URL}/guest/${GUEST_ID}/info`);
                        if (response.ok) {
                            const guestData = await response.json();
                            guestHasData = guestData.has_data || false;
                        }
                    } catch (error) {
                        console.error('Error checking guest data:', error);
                        guestHasData = false; // По умолчанию показываем форму
                    }
                }
            }

            // Показываем форму или кнопку в зависимости от наличия данных
            if (guestHasData === true) {
                // Данные уже заполнены - показываем только кнопку отправки
                formContainer.style.display = 'none';
                sendContainer.style.display = 'block';
            } else {
                // Данные не заполнены - показываем форму
                formContainer.style.display = 'block';
                sendContainer.style.display = 'none';
                // Применяем маску для поля телефона, если форма показывается
                const phoneInput = document.getElementById('winNotificationPhone');
                if (phoneInput && !phoneInput.hasAttribute('data-mask-applied')) {
                    applyPhoneMask(phoneInput);
                    phoneInput.setAttribute('data-mask-applied', 'true');
                }
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

        // Отправить форму для получения приза
        async function submitPrizeForm(event) {
            console.log('submitPrizeForm called', event);

            // Убеждаемся, что функция доступна глобально
            if (typeof window !== 'undefined') {
                window.submitPrizeForm = submitPrizeForm;
            }

            if (event) {
                event.preventDefault();
            }

            const formContainer = document.getElementById('winNotificationFormContainer');
            const sendContainer = document.getElementById('winNotificationSendContainer');

            console.log('formContainer:', formContainer, 'display:', formContainer?.style.display);
            console.log('sendContainer:', sendContainer, 'display:', sendContainer?.style.display);

            // Ищем кнопку: сначала в форме, потом в контейнере отправки
            let submitBtn = document.getElementById('winNotificationSubmitBtn');

            if (sendContainer?.style.display === 'block') {
                submitBtn = sendContainer.querySelector('#winNotificationSubmitBtn2');
            }

            console.log('submitBtn found:', submitBtn);

            // Блокируем кнопку сразу после нажатия
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';
                submitBtn.style.cursor = 'not-allowed';
            }

            let formData = {};

            // Если форма видна, берем данные из формы
            if (formContainer && formContainer.style.display !== 'none') {
                console.log('Form is visible, using form data');
                const nameInput = document.getElementById('winNotificationName');
                const emailInput = document.getElementById('winNotificationEmail');
                const phoneInput = document.getElementById('winNotificationPhone');

                // Очищаем номер телефона от форматирования перед отправкой
                let phoneValue = phoneInput?.value || '';
                // Удаляем все символы кроме цифр, но сохраняем +7 в начале
                phoneValue = phoneValue.replace(/\D/g, '');
                if (phoneValue.startsWith('7')) {
                    phoneValue = '+' + phoneValue;
                } else if (phoneValue && !phoneValue.startsWith('+')) {
                    phoneValue = '+7' + phoneValue;
                }

                formData = {
                    name: nameInput?.value || '',
                    email: emailInput?.value || '',
                    phone: phoneValue || ''
                };
            } else {
                // Если форма не видна, значит данные уже заполнены
                console.log('Form is not visible, using send-email route');
                // Используем новый роут для отправки приза на почту по spin_id
                const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
                const winData = localStorage.getItem(todayWinKey);
                let spinId = null;

                console.log('todayWinKey:', todayWinKey);
                console.log('winData from localStorage:', winData);

                if (winData) {
                    try {
                        const parsed = JSON.parse(winData);
                        spinId = parsed.spin_id;
                        console.log('Parsed spin_id:', spinId);
                    } catch (e) {
                        console.error('Error parsing win data:', e);
                    }
                }

                if (!spinId) {
                    console.error('spin_id not found in localStorage');
                    showError('Не найден ID спина. Пожалуйста, обновите страницу.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Отправить приз';
                        submitBtn.style.cursor = 'pointer';
                    }
                    return;
                }

                console.log('Sending request to:', `${API_URL}/spin/${spinId}/send-email`);

                // Используем новый роут для отправки приза на почту
                try {
                    const response = await fetch(`${API_URL}/spin/${spinId}/send-email`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    console.log('Response status:', response.status);
                    console.log('Response data:', data);

                    if (response.ok) {
                        console.log('Email sent successfully');
                        // Успешно отправлено - кнопка остается заблокированной
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.textContent = '✓ Приз отправлен!';
                            submitBtn.style.background = '#4caf50';
                            submitBtn.style.color = 'white';
                            submitBtn.style.cursor = 'not-allowed';
                        }

                        // Показываем сообщение об успехе
                        // const message = document.getElementById('winNotificationMessage');
                        // if (message) {
                        //     message.innerHTML += '<br><br><strong style="color: #4caf50;">✓ Приз отправлен на почту!</strong>';
                        // }
                    } else {
                        // Обработка ошибок
                        if (response.status === 403 && data.error === 'Prize already claimed today') {
                            const errorMsg = data.message || 'Приз уже был получен сегодня. Попробуйте завтра!';

                            const message = document.getElementById('winNotificationMessage');
                            if (message) {
                                const originalMessage = message.innerHTML;
                                message.innerHTML = originalMessage + '<br><br><strong style="color: #ff6b6b;">⚠️ ' + errorMsg + '</strong>';
                            }

                            showError(errorMsg);

                            // Кнопка остается заблокированной при ошибке "уже получен"
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.textContent = 'Приз уже получен';
                                submitBtn.style.background = '#ff6b6b';
                                submitBtn.style.color = 'white';
                                submitBtn.style.cursor = 'not-allowed';
                            }
                        } else {
                            const errorMsg = data.error || data.message || 'Ошибка при отправке приза';
                            showError(errorMsg);
                            // Разблокируем кнопку только при других ошибках
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Отправить приз';
                                submitBtn.style.cursor = 'pointer';
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error sending prize email:', error);
                    showError('Ошибка при отправке приза: ' + error.message);
                    // Разблокируем кнопку при ошибке сети
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Отправить приз';
                        submitBtn.style.cursor = 'pointer';
                    }
                }
                return;
            }

            try {
                const response = await fetch(`${API_URL}/guest/${GUEST_ID}/claim-prize`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...formData,
                        wheel_slug: WHEEL_SLUG,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    // Успешно отправлено - кнопка остается заблокированной
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = '✓ Приз отправлен!';
                        submitBtn.style.background = '#4caf50';
                        submitBtn.style.color = 'white';
                        submitBtn.style.cursor = 'not-allowed';
                    }

                    // Скрываем форму и показываем только кнопку
                    if (formContainer) {
                        formContainer.style.display = 'none';
                    }
                    if (sendContainer) {
                        sendContainer.style.display = 'block';
                        // Обновляем кнопку в контейнере отправки
                        const sendBtn = sendContainer.querySelector('.win-notification-submit-btn');
                        if (sendBtn) {
                            sendBtn.disabled = true;
                            sendBtn.textContent = '✓ Приз отправлен!';
                            sendBtn.style.background = '#4caf50';
                            sendBtn.style.color = 'white';
                            sendBtn.style.cursor = 'not-allowed';
                        }
                    }

                    // Показываем сообщение об успехе
                    const message = document.getElementById('winNotificationMessage');
                    if (message) {
                        message.innerHTML += '<br><br><strong style="color: #4caf50;">✓ Данные сохранены! Приз будет отправлен на указанную почту.</strong>';
                    }

                    // Отправляем guest_id в родительское окно, если он есть в ответе
                    if (data.guest_id && typeof data.guest_id === 'number') {
                        notifyParent('claim-prize', { guest_id: data.guest_id });
                    }
                } else {
                    // Обработка ошибок
                    if (response.status === 403 && data.error === 'Prize already claimed today') {
                        // Приз уже был получен сегодня
                        const errorMsg = data.message || 'Приз уже был получен сегодня. Попробуйте завтра!';

                        // Показываем сообщение в области уведомления о выигрыше
                        const message = document.getElementById('winNotificationMessage');
                        if (message) {
                            const originalMessage = message.innerHTML;
                            message.innerHTML = originalMessage + '<br><br><strong style="color: #ff6b6b;">⚠️ ' + errorMsg + '</strong>';
                        }

                        // Также показываем общую ошибку
                        showError(errorMsg);

                        // Блокируем кнопку отправки
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.textContent = 'Приз уже получен';
                            submitBtn.style.background = '#ff6b6b';
                            submitBtn.style.color = 'white';
                            submitBtn.style.cursor = 'not-allowed';
                        }
                    } else {
                        // Другие ошибки - разблокируем кнопку
                        const errorMsg = data.error || data.message || 'Ошибка при отправке данных';
                        showError(errorMsg);
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Отправить приз';
                            submitBtn.style.cursor = 'pointer';
                        }
                    }
                }
            } catch (error) {
                console.error('Error submitting prize form:', error);
                showError('Ошибка при отправке данных: ' + error.message);
                // Разблокируем кнопку при ошибке сети
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Отправить приз';
                    submitBtn.style.cursor = 'pointer';
                }
            }
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

                // Проверяем выигрыш и применяем поворот после загрузки призов
                await applyWonPrizeRotationIfNeeded();
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

                //вывод картинки
                if (prizeImage && prize.image) {
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

        // Вычислить угол центра приза (середина сектора)
        function getPrizeCenterAngle(prizeIndex) {
            const normalizedPrizes = normalizeProbabilities(prizes);
            let cumulativeAngle = -Math.PI / 2;

            for (let i = 0; i < normalizedPrizes.length; i++) {
                const prizeAngle = (normalizedPrizes[i].probability / 100) * 2 * Math.PI;
                if (i === prizeIndex) {
                    // Возвращаем центр сектора (начало + половина угла)
                    return cumulativeAngle + prizeAngle / 2;
                }
                cumulativeAngle += prizeAngle;
            }

            return cumulativeAngle;
        }

        // Вычислить поворот колеса для размещения приза под стрелкой
        function calculateRotationForPrize(prizeId) {
            const prizeIndex = findPrizeIndex(prizeId);
            if (prizeIndex === -1) {
                console.warn('Prize not found:', prizeId, 'Available prizes:', prizes.map(p => ({ id: p.id, name: p.name })));
                return 0;
            }

            // Угол центра выигранного приза (относительно начального положения -Math.PI/2)
            const prizeCenterAngle = getPrizeCenterAngle(prizeIndex);

            console.log('Calculating rotation:', {
                prizeId: prizeId,
                prizeIndex: prizeIndex,
                prizeCenterAngle: prizeCenterAngle,
                prizeCenterAngleDegrees: (prizeCenterAngle * 180 / Math.PI).toFixed(2)
            });

            // Стрелка указывает на -Math.PI/2 (вверх)
            // В drawWheel начальный угол: currentAngle = -Math.PI / 2 + rotation
            // prizeCenterAngle = -Math.PI/2 + offset (где offset - сумма углов до центра приза)
            //
            // При повороте колеса на rotation, центр приза будет находиться на:
            // prizeCenterAngle + rotation = (-Math.PI/2 + offset) + rotation
            //
            // Чтобы центр приза был под стрелкой (на -Math.PI/2), нужно:
            // (-Math.PI/2 + offset) + rotation = -Math.PI/2
            // rotation = -Math.PI/2 - (-Math.PI/2 + offset)
            // rotation = -Math.PI/2 + Math.PI/2 - offset
            // rotation = -offset
            //
            // Но offset = prizeCenterAngle + Math.PI/2
            // Поэтому: rotation = -(prizeCenterAngle + Math.PI/2)
            // rotation = -prizeCenterAngle - Math.PI/2

            // Правильная формула: нужно повернуть так, чтобы центр приза был на -Math.PI/2
            // Если prizeCenterAngle - это угол центра приза в неповернутом колесе,
            // то после поворота на rotation он будет на prizeCenterAngle + rotation
            // Нам нужно: prizeCenterAngle + rotation = -Math.PI/2
            // rotation = -Math.PI/2 - prizeCenterAngle

            const rotation = -Math.PI / 2 - prizeCenterAngle;

            // Нормализуем угол в диапазон [0, 2π]
            const normalizedRotation = ((rotation % (2 * Math.PI)) + (2 * Math.PI)) % (2 * Math.PI);

            console.log('Rotation result:', {
                rotation: rotation,
                rotationDegrees: (rotation * 180 / Math.PI).toFixed(2),
                normalizedRotation: normalizedRotation,
                normalizedRotationDegrees: (normalizedRotation * 180 / Math.PI).toFixed(2)
            });

            return normalizedRotation;
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
                        const prizeCode = data.today_win.code || null;
                        const spinId = data.today_win.spin_id || null;
                        saveWin(prize, prizeCode, null, spinId);
                        // Показываем уведомление вместо ошибки
                        setTimeout(() => {
                            showWinNotification(prize, prizeCode);
                            // Показываем блок выигранного приза под стрелкой
                            showWonPrizeBlock(prize, prizeCode);
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
                    console.log('Spin result:', {
                        prizeId: data.prize.id,
                        prizeName: data.prize.name,
                        prizeIndex: prizeIndex,
                        allPrizes: prizes.map((p, idx) => ({ index: idx, id: p.id, name: p.name }))
                    });

                    if (prizeIndex === -1) {
                        console.error('Prize not found in prizes array!', {
                            prizeId: data.prize.id,
                            prizeName: data.prize.name,
                            availablePrizes: prizes.map(p => ({ id: p.id, name: p.name }))
                        });
                    }
                }

                // Анимация вращения
                await animateSpin(prizeIndex, data);

                // Обновление информации о вращениях
                updateSpinsInfo(data.spins_count, data.spins_limit);

                // Показ результата
                if (data.prize) {
                    showResult(data.prize, data.code);
                    notifyParent('win', data.prize);

                    // Сохраняем выигрыш и показываем уведомление
                    // Код приза берем из поля code ответа API
                    const prizeCode = data.code || null;
                    const spinId = data.spin_id || null;
                    saveWin(data.prize, prizeCode, null, spinId);

                    // Показываем уведомление с задержкой после анимации
                    // Передаем код явно, если он есть
                    setTimeout(() => {
                        showWinNotification(data.prize, prizeCode);
                        // Показываем блок выигранного приза под стрелкой
                        showWonPrizeBlock(data.prize, prizeCode);
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
                    // Используем центр сектора приза, чтобы стрелка указывала точно на приз
                    finalAngle = getPrizeCenterAngle(prizeIndex);
                    console.log('Animation: prizeIndex=', prizeIndex, 'finalAngle (center)=', finalAngle, 'degrees=', (finalAngle * 180 / Math.PI).toFixed(2));
                } else {
                    // Случайный угол
                    finalAngle = -Math.PI / 2 + Math.random() * 2 * Math.PI;
                }

                const spins = 5; // Количество полных оборотов

                // Логика вычисления финального поворота:
                // 1. finalAngle - это угол центра приза в неповернутом колесе (rotation = 0)
                //    Он измеряется от начала координат и включает -Math.PI/2
                // 2. В drawWheel сектор рисуется от currentAngle = -Math.PI/2 + rotation
                // 3. После поворота на rotation, центр приза будет на finalAngle + rotation
                // 4. Стрелка указывает на -Math.PI/2 (вверх)
                // 5. Чтобы стрелка указывала на центр приза: finalAngle + rotation = -Math.PI/2
                // 6. Отсюда: rotation = -Math.PI/2 - finalAngle
                // 7. Добавляем полные обороты для эффекта вращения
                const targetRotation = -Math.PI / 2 - finalAngle;
                const finalRotation = currentRotation + (spins * 2 * Math.PI) + targetRotation;

                console.log('Animation calculation:', {
                    prizeIndex: prizeIndex,
                    finalAngle: finalAngle,
                    finalAngleDegrees: (finalAngle * 180 / Math.PI).toFixed(2),
                    targetRotation: targetRotation,
                    targetRotationDegrees: (targetRotation * 180 / Math.PI).toFixed(2),
                    currentRotation: currentRotation,
                    finalRotation: finalRotation,
                    finalRotationDegrees: (finalRotation * 180 / Math.PI).toFixed(2)
                });

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
        function showResult(prize, code = '') {
            const result = document.getElementById('result');
            const resultText = document.getElementById('resultText');
            const spinButton = document.getElementById('spinButton');

            if (prize) {
                resultText.innerHTML = `
                    <strong>Вы выиграли: ${prize.name}</strong><br>
                    ${prize.text_for_winner ? prize.text_for_winner : ''}
                `;

                if(code){
                    resultText.innerHTML = `<strong>Код: ${code}</strong><br>`;
                }
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
            const winNotification = document.getElementById('winNotification');

            errorEl.textContent = message;
            errorEl.classList.add('show');

            // Если секция выигрыша видна, показываем ошибку поверх неё
            const isWinNotificationVisible = winNotification &&
                winNotification.style.display !== 'none' &&
                winNotification.style.display !== '' &&
                (winNotification.classList.contains('show') || winNotification.offsetHeight > 0);

            if (isWinNotificationVisible) {
                errorEl.classList.add('error-overlay');

                // Автоматически скрываем ошибку через 5 секунд
                setTimeout(() => {
                    errorEl.classList.remove('show', 'error-overlay');
                }, 5000);
            } else {
                errorEl.classList.remove('error-overlay');
            }
        }

        // Скрыть ошибку
        function hideError() {
            const errorEl = document.getElementById('error');
            errorEl.classList.remove('show', 'error-overlay');
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

