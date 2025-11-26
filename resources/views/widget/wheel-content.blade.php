<!--OLD-->
<div class="lucky-wheel-content">
    <style>
        .lucky-wheel-content {
            font-family: 'Arial', sans-serif;
            width: 100%;
            height: 100%;
            overflow: hidden;

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            /* justify-content: center; */
            /* justify-content: flex-start; */
            align-items: center;
            padding: 10px;
            justify-content: flex-start;
        }

        .lucky-wheel-content * {
            box-sizing: border-box;
        }

        .lucky-wheel-content .lucky-wheel-container {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 100%;
            max-height: 100%;
            margin: 0 auto;
        }

        .lucky-wheel-content .lucky-wheel-h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .lucky-wheel-content .lucky-wheel-description {
            color: #666;
            margin-bottom: 35px;
            font-size: 14px;
        }

        .lucky-wheel-content .lucky-wheel-wheel-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 20px;
            aspect-ratio: 1;
        }

        .lucky-wheel-content .lucky-wheel-wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            border: 10px solid #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .lucky-wheel-content .lucky-wheel-pointer {
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

        .lucky-wheel-content .lucky-wheel-won-prize-block {
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
            animation: lucky-wheel-slideDownPrize 0.5s ease;
        }

        .lucky-wheel-content .lucky-wheel-won-prize-label {
            font-size: 11px;
            opacity: 0.9;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .lucky-wheel-content .lucky-wheel-won-prize-name {
            font-size: 14px;
            font-weight: bold;
            white-space: nowrap;
        }

        @keyframes lucky-wheel-slideDownPrize {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .lucky-wheel-content .lucky-wheel-spin-button {
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

        .lucky-wheel-content .lucky-wheel-spin-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .lucky-wheel-content .lucky-wheel-spin-button:active:not(:disabled) {
            transform: translateY(0);
        }

        .lucky-wheel-content .lucky-wheel-spin-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .lucky-wheel-content .lucky-wheel-error {
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

        .lucky-wheel-content .lucky-wheel-error.show {
            display: block;
        }

        .lucky-wheel-content .lucky-wheel-error.show.lucky-wheel-error-overlay {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10001;
            max-width: 500px;
            width: calc(100% - 40px);
            margin-top: 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: lucky-wheel-slideDownError 0.3s ease forwards;
        }

        @keyframes lucky-wheel-slideDownError {
            from {
                top: -100px;
                opacity: 0;
            }
            to {
                top: 20px;
                opacity: 1;
            }
        }

        .lucky-wheel-content .lucky-wheel-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .lucky-wheel-content .lucky-wheel-spins-info {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }

        .lucky-wheel-content .lucky-wheel-win-notification {
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
            animation: lucky-wheel-slideUpNotification 0.5s ease forwards;
        }

        .lucky-wheel-content .lucky-wheel-win-notification.show {
            bottom: 20px;
        }

        .lucky-wheel-content .lucky-wheel-win-notification h3 {
            margin: 0 0 15px 0;
            font-size: 1.3em;
            text-align: center;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-message {
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-code {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 12px;
            flex-direction: row;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-code input {
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

        .lucky-wheel-content .lucky-wheel-win-notification-code input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-code input::placeholder {
            color: #999;
            font-weight: normal;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-code button {
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

        .lucky-wheel-content .lucky-wheel-win-notification-code button:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-code button:active {
            transform: scale(0.95);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-code button svg {
            width: 20px;
            height: 20px;
            fill: #667eea;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
            transition: all 0.3s ease;
            padding: 0px;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-form-text {
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
            opacity: 0.9;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-form-group {
            margin-bottom: 12px;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: white;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-form-group input::placeholder {
            color: #999;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-submit-btn {
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

        .lucky-wheel-content .lucky-wheel-win-notification-submit-btn:hover:not(:disabled) {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-send-container {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-image-container {
            display: none;
            text-align: center;
            margin: 20px 0;
            width: 100%;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-image-container img {
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-height: 300px;
            max-width: 290px;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-pdf-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .lucky-wheel-content .lucky-wheel-win-notification-pdf-link:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .lucky-wheel-content .lucky-wheel-win-notification-pdf-link svg {
            width: 18px;
            height: 18px;
            fill: white;
        }

        @keyframes lucky-wheel-slideUpNotification {
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
            .lucky-wheel-content .lucky-wheel-container {
                padding: 20px 15px;
            }

            .lucky-wheel-content .lucky-wheel-h1 {
                font-size: 1.5em;
            }

            .lucky-wheel-content .lucky-wheel-wheel-container {
                max-width: 300px;
            }

            .lucky-wheel-content .lucky-wheel-win-notification {
                width: calc(100% - 20px);
                padding: 15px 20px;
                bottom: 10px;
            }

            .lucky-wheel-content .lucky-wheel-win-notification h3 {
                font-size: 1.1em;
            }
        }
    </style>

    <div class="lucky-wheel-container">
        <h1 class="lucky-wheel-h1">🎡 {{ $wheel->name ?? 'Колесо Фортуны' }}</h1>
        @if($wheel->description)
        <div class="lucky-wheel-description">{{ $wheel->description }}</div>
        @endif

        <div id="lucky-wheel-loading" class="lucky-wheel-loading">Загрузка...</div>

        <div id="lucky-wheel-wheelContent" style="display: none;">
            <div class="lucky-wheel-wheel-container">
                <div class="lucky-wheel-pointer"></div>
                <canvas id="lucky-wheel-wheelCanvas" class="lucky-wheel-wheel"></canvas>
                <div id="lucky-wheel-wonPrizeBlock" class="lucky-wheel-won-prize-block" style="display: none;">
                    <div class="lucky-wheel-won-prize-label">Выиграно сегодня:</div>
                    <div class="lucky-wheel-won-prize-name" id="lucky-wheel-wonPrizeCode"></div>
                </div>
            </div>

            <button id="lucky-wheel-spinButton" class="lucky-wheel-spin-button">Крутить колесо!</button>
            <div id="lucky-wheel-spinsInfo" class="lucky-wheel-spins-info"></div>
        </div>

        <div id="lucky-wheel-error" class="lucky-wheel-error"></div>
    </div>

    <div id="lucky-wheel-winNotification" class="lucky-wheel-win-notification" style="display: none;">
        <button class="lucky-wheel-win-notification-close" onclick="luckyWheelHideWinNotification()">&times;</button>
        <h3>🎉 Поздравляем с выигрышем!</h3>
        <div class="lucky-wheel-win-notification-message" id="lucky-wheel-winNotificationMessage"></div>
        <div class="lucky-wheel-win-notification-code" id="lucky-wheel-winNotificationCodeContainer">
            <input type="text" id="lucky-wheel-winNotificationCode" readonly value="">
            <button onclick="luckyWheelCopyPrizeCode(event)" title="Копировать код">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                </svg>
            </button>
        </div>

        <a href="#" id="lucky-wheel-winNotificationPdfLink" class="lucky-wheel-win-notification-pdf-link" style="display: none;" target="_blank">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
            </svg>
            <span>Скачать сертификат PDF</span>
        </a>

        <div class="lucky-wheel-win-notification-form" id="lucky-wheel-winNotificationFormContainer" style="display: none;">
            <p class="lucky-wheel-win-notification-form-text">Для получения приза на почту заполните данные:</p>
            <form id="lucky-wheel-winNotificationForm" onsubmit="luckyWheelSubmitPrizeForm(event)">
                <div class="lucky-wheel-win-notification-form-group">
                    <input type="text" id="lucky-wheel-winNotificationName" name="name" placeholder="Ваше имя" required>
                </div>
                <div class="lucky-wheel-win-notification-form-group">
                    <input type="email" id="lucky-wheel-winNotificationEmail" name="email" placeholder="Email" required>
                </div>
                <div class="lucky-wheel-win-notification-form-group">
                    <input type="tel" id="lucky-wheel-winNotificationPhone" name="phone" placeholder="+7 (XXX) XXX-XX-XX" required maxlength="18">
                </div>
                <button type="submit" class="lucky-wheel-win-notification-submit-btn" id="lucky-wheel-winNotificationSubmitBtn">
                    Отправить приз
                </button>
            </form>
        </div>

        <div class="lucky-wheel-win-notification-send-container" id="lucky-wheel-winNotificationSendContainer" style="display: none;">
            <button type="button" class="lucky-wheel-win-notification-submit-btn" id="lucky-wheel-winNotificationSubmitBtn2" onclick="luckyWheelSubmitPrizeForm(event)">
                Отправить приз
            </button>
        </div>

        <div class="lucky-wheel-win-notification-image-container" id="lucky-wheel-winNotificationImageContainer" style="display: none;">
            <img id="lucky-wheel-winNotificationImage" src="" alt="Приз">
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            // Конфигурация
            const API_URL = '{{ url("/api/widget") }}';
            const APP_URL = '{{ url('/') }}';
            const WHEEL_SLUG = '{{ $wheel->slug }}';
            let GUEST_ID = new URLSearchParams(window.location.search).get('guest_id') || window.luckyWheelGuestId;

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
            let prizeImages = {};

            // Callbacks для связи с родительским виджетом
            const callbacks = window.LuckyWheelCallbacks || {};

            function notifyCallbacks(action, data) {
                if (callbacks[action]) {
                    callbacks[action](data);
                }
            }

            // Создать или получить гостя
            async function createOrGetGuest() {
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
                        let errorMessage = 'Не удалось создать гостя';
                        try {
                            const errorData = await response.json();
                            if (errorData.error) {
                                errorMessage = errorData.error;
                            } else if (errorData.message) {
                                errorMessage = errorData.message;
                            }
                        } catch (e) {}
                        throw new Error(errorMessage);
                    }

                    const data = await response.json();
                    const guestId = data.id || data.guest_id;

                    if (!data || !guestId) {
                        console.error('API response:', data);
                        throw new Error('Не получен ID гостя от сервера. Ответ: ' + JSON.stringify(data));
                    }

                    GUEST_ID = String(guestId);
                    if (!GUEST_ID) {
                        throw new Error('Не удалось получить ID гостя');
                    }

                    localStorage.setItem(guestKey, GUEST_ID);
                    return GUEST_ID;
                } catch (error) {
                    console.error('Error creating guest:', error);
                    showError('Ошибка инициализации: ' + error.message);
                    return null;
                }
            }

            // Инициализация
            async function init() {
                if (!GUEST_ID) {
                    GUEST_ID = await createOrGetGuest();
                    if (!GUEST_ID) {
                        return;
                    }
                }

                const phoneInput = document.getElementById('lucky-wheel-winNotificationPhone');
                if (phoneInput) {
                    applyPhoneMask(phoneInput);
                }

                checkTodayWin();
                loadWheelData();

                setInterval(function() {
                    checkTodayWin();
                }, 60000);
            }

            // Применить маску для российского номера телефона
            function applyPhoneMask(input) {
                if (!input) return;

                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
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
                    e.target.value = formattedValue;
                });

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

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && input.value.length <= 4) {
                        e.preventDefault();
                        input.value = '';
                    }
                });
            }

            // Показать блок выигранного приза
            function showWonPrizeBlock(prize, prizeCode) {
                const block = document.getElementById('lucky-wheel-wonPrizeBlock');
                const nameElement = document.getElementById('lucky-wheel-wonPrizeCode');
                if (block && nameElement && prize) {
                    nameElement.textContent = prizeCode;
                    block.style.display = 'block';
                }
            }

            function hideWonPrizeBlock() {
                const block = document.getElementById('lucky-wheel-wonPrizeBlock');
                if (block) {
                    block.style.display = 'none';
                }
            }

            function applyWonPrizeRotation(prize) {
                if (!prize || !prizes || prizes.length === 0) {
                    return;
                }
                const rotation = calculateRotationForPrize(prize.id);
                currentRotation = rotation;
                if (canvas && ctx) {
                    drawWheel(currentRotation);
                }
            }

            async function applyWonPrizeRotationIfNeeded() {
                if (!prizes || prizes.length === 0) {
                    return;
                }
                const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
                const winData = localStorage.getItem(todayWinKey);
                if (winData) {
                    try {
                        const win = JSON.parse(winData);
                        const winDate = new Date(win.date);
                        const today = new Date();
                        if (winDate.toDateString() === today.toDateString() && win.prize) {
                            applyWonPrizeRotation(win.prize);
                            return;
                        }
                    } catch (e) {
                        console.error('Error parsing win data:', e);
                    }
                }
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

            async function checkTodayWin() {
                const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
                const winData = localStorage.getItem(todayWinKey);

                if (winData) {
                    try {
                        const win = JSON.parse(winData);
                        const winDate = new Date(win.date);
                        const today = new Date();
                        if (winDate.toDateString() === today.toDateString()) {
                            const prizeCode = win.code || null;
                            const prizeEmailImage = win?.prize?.email_image || null;
                            if(prizeEmailImage) {
                                set_prize_image(prizeEmailImage);
                            }
                            const guestHasData = win.guest_has_data !== undefined ? win.guest_has_data : null;
                            showWinNotification(win.prize, prizeCode, guestHasData);
                            showWonPrizeBlock(win.prize, prizeCode);
                            applyWonPrizeRotation(win.prize);
                            blockSpinning();
                            return;
                        } else {
                            localStorage.removeItem(todayWinKey);
                            unblockSpinning();
                            hideWinNotification();
                            hideWonPrizeBlock();
                            currentRotation = 0;
                            if (canvas && ctx) {
                                drawWheel(currentRotation);
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing win data:', e);
                    }
                }

                try {
                    const response = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}/today-win?guest_id=${GUEST_ID}`);
                    if (response.ok) {
                        const data = await response.json();
                        if (data.has_win && data.prize) {
                            const prizeCode = data.code || null;
                            const spinId = data.spin_id || null;
                            const prizeEmailImage = data?.prize?.email_image || null;

                            saveWin(data.prize, prizeCode, data.guest_has_data || false, spinId);
                            showWinNotification(data.prize, prizeCode, data.guest_has_data);

                            if(prizeEmailImage) {
                                set_prize_image(prizeEmailImage);
                            }
                            show_prize_image();

                            showWonPrizeBlock(data.prize, prizeCode);
                            applyWonPrizeRotation(data.prize);
                            blockSpinning();
                        } else {
                            const spinButton = document.getElementById('lucky-wheel-spinButton');
                            if (spinButton && spinButton.disabled && spinButton.textContent.includes('уже выиграли')) {
                                unblockSpinning();
                            }
                            hideWonPrizeBlock();
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

            function blockSpinning() {
                const spinButton = document.getElementById('lucky-wheel-spinButton');
                if (spinButton) {
                    spinButton.disabled = true;
                    spinButton.textContent = 'Вы уже выиграли сегодня. Попробуйте завтра!';
                    spinButton.style.cursor = 'not-allowed';
                }
            }

            function unblockSpinning() {
                const spinButton = document.getElementById('lucky-wheel-spinButton');
                if (spinButton) {
                    spinButton.disabled = false;
                    spinButton.textContent = 'Крутить колесо!';
                    spinButton.style.cursor = 'pointer';
                }
            }

            async function showWinNotification(prize, code, guestHasDataParam = null) {
                const notification = document.getElementById('lucky-wheel-winNotification');
                const message = document.getElementById('lucky-wheel-winNotificationMessage');
                const codeInput = document.getElementById('lucky-wheel-winNotificationCode');
                const codeContainer = document.getElementById('lucky-wheel-winNotificationCodeContainer');
                const formContainer = document.getElementById('lucky-wheel-winNotificationFormContainer');
                const sendContainer = document.getElementById('lucky-wheel-winNotificationSendContainer');
                const pdfLink = document.getElementById('lucky-wheel-winNotificationPdfLink');

                if (!prize) {
                    return;
                }

                let messageText = `<strong>Вы выиграли: ${prize.name}</strong>`;
                if (prize.text_for_winner) {
                    messageText += `<br>${prize.text_for_winner}`;
                }
                message.innerHTML = messageText;

                let prizeCode = code;
                codeContainer.style.display = 'flex';

                if (!prizeCode || !prizeCode.toString().trim()) {
                    codeInput.placeholder = 'Код не указан';
                    codeInput.value = '';
                } else {
                    codeInput.placeholder = '';
                    codeInput.value = prizeCode.toString().trim();
                }

                const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
                const winDataStr = localStorage.getItem(todayWinKey);
                let spinId = null;

                if (winDataStr) {
                    try {
                        const winData = JSON.parse(winDataStr);
                        spinId = winData.spin_id;
                    } catch (e) {
                        console.warn('Could not parse win data for PDF:', e);
                    }
                }

                if (!spinId) {
                    try {
                        const todayWinResponse = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}/today-win?guest_id=${GUEST_ID}`);
                        if (todayWinResponse.ok) {
                            const todayWinData = await todayWinResponse.json();
                            if (todayWinData.has_win && todayWinData.spin_id) {
                                spinId = todayWinData.spin_id;
                            }
                        }
                    } catch (e) {
                        console.warn('Could not get spin_id from API for PDF:', e);
                    }
                }

                if (spinId && pdfLink) {
                    pdfLink.href = `${API_URL}/spin/${spinId}/download-pdf`;
                    pdfLink.style.display = 'flex';
                } else if (pdfLink) {
                    pdfLink.style.display = 'none';
                }

                let guestHasData = guestHasDataParam;

                if (guestHasData === null || guestHasData === undefined) {
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

                    if (guestHasData === null || guestHasData === undefined) {
                        try {
                            const response = await fetch(`${API_URL}/guest/${GUEST_ID}/info`);
                            if (response.ok) {
                                const guestData = await response.json();
                                guestHasData = guestData.has_data || false;
                            }
                        } catch (error) {
                            console.error('Error checking guest data:', error);
                            guestHasData = false;
                        }
                    }
                }

                if (guestHasData === true) {
                    formContainer.style.display = 'none';
                    sendContainer.style.display = 'block';
                } else {
                    formContainer.style.display = 'block';
                    sendContainer.style.display = 'none';
                    const phoneInput = document.getElementById('lucky-wheel-winNotificationPhone');
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

            function hideWinNotification() {
                const notification = document.getElementById('lucky-wheel-winNotification');
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }

            window.luckyWheelHideWinNotification = hideWinNotification;

            async function submitPrizeForm(event) {
                if (event) {
                    event.preventDefault();
                }

                const formContainer = document.getElementById('lucky-wheel-winNotificationFormContainer');
                const sendContainer = document.getElementById('lucky-wheel-winNotificationSendContainer');
                let submitBtn = document.getElementById('lucky-wheel-winNotificationSubmitBtn');

                if (sendContainer?.style.display === 'block') {
                    submitBtn = sendContainer.querySelector('#lucky-wheel-winNotificationSubmitBtn2');
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Отправка...';
                    submitBtn.style.cursor = 'not-allowed';
                }

                let formData = {};

                if (formContainer && formContainer.style.display !== 'none') {
                    const nameInput = document.getElementById('lucky-wheel-winNotificationName');
                    const emailInput = document.getElementById('lucky-wheel-winNotificationEmail');
                    const phoneInput = document.getElementById('lucky-wheel-winNotificationPhone');

                    let phoneValue = phoneInput?.value || '';
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
                    const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
                    const winData = localStorage.getItem(todayWinKey);
                    let spinId = null;

                    if (winData) {
                        try {
                            const parsed = JSON.parse(winData);
                            spinId = parsed.spin_id;
                        } catch (e) {
                            console.error('Error parsing win data:', e);
                        }
                    }

                    if (!spinId) {
                        showError('Не найден ID спина. Пожалуйста, обновите страницу.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Отправить приз';
                            submitBtn.style.cursor = 'pointer';
                        }
                        return;
                    }

                    try {
                        const response = await fetch(`${API_URL}/spin/${spinId}/send-email`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (response.ok) {
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.textContent = '✓ Приз отправлен!';
                                submitBtn.style.background = '#4caf50';
                                submitBtn.style.color = 'white';
                                submitBtn.style.cursor = 'not-allowed';
                            }
                        } else {
                            if (response.status === 403 && data.error === 'Prize already claimed today') {
                                const errorMsg = data.message || 'Приз уже был получен сегодня. Попробуйте завтра!';
                                const message = document.getElementById('lucky-wheel-winNotificationMessage');
                                if (message) {
                                    const originalMessage = message.innerHTML;
                                    message.innerHTML = originalMessage + '<br><br><strong style="color: #ff6b6b;">⚠️ ' + errorMsg + '</strong>';
                                }
                                showError(errorMsg);
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
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.textContent = '✓ Приз отправлен!';
                            submitBtn.style.background = '#4caf50';
                            submitBtn.style.color = 'white';
                            submitBtn.style.cursor = 'not-allowed';
                        }

                        if (formContainer) {
                            formContainer.style.display = 'none';
                        }
                        if (sendContainer) {
                            sendContainer.style.display = 'none';
                        }

                        const todayWinKey = `lucky_wheel_win_${WHEEL_SLUG}_${GUEST_ID}`;
                        const winDataStr = localStorage.getItem(todayWinKey);
                        let prizeEmailImage = null;

                        if (winDataStr) {
                            try {
                                const winData = JSON.parse(winDataStr);
                                if (winData.prize && winData.prize.email_image) {
                                    prizeEmailImage = winData.prize.email_image;
                                }
                            } catch (e) {
                                console.warn('Could not parse win data:', e);
                            }
                        }

                        if(prizeEmailImage) {
                            set_prize_image(prizeEmailImage);
                        }

                        const message = document.getElementById('lucky-wheel-winNotificationMessage');
                        if (message) {
                            message.innerHTML += '<br><br><strong style="color: #4caf50;">✓ Данные сохранены! Приз будет отправлен на указанную почту.</strong>';
                        }

                        if (data.guest_id && typeof data.guest_id === 'number') {
                            notifyCallbacks('claim-prize', { guest_id: data.guest_id });
                        }

                        //показываем фото qr кода
                        show_prize_image();
                    } else {
                        if (response.status === 403 && data.error === 'Prize already claimed today') {
                            const errorMsg = data.message || 'Приз уже был получен сегодня. Попробуйте завтра!';
                            const message = document.getElementById('lucky-wheel-winNotificationMessage');
                            if (message) {
                                const originalMessage = message.innerHTML;
                                message.innerHTML = originalMessage + '<br><br><strong style="color: #ff6b6b;">⚠️ ' + errorMsg + '</strong>';
                            }
                            showError(errorMsg);
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.textContent = 'Приз уже получен';
                                submitBtn.style.background = '#ff6b6b';
                                submitBtn.style.color = 'white';
                                submitBtn.style.cursor = 'not-allowed';
                            }
                        } else {
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
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Отправить приз';
                        submitBtn.style.cursor = 'pointer';
                    }
                }
            }

            window.luckyWheelSubmitPrizeForm = submitPrizeForm;

            function copyPrizeCode(event) {
                const codeInput = document.getElementById('lucky-wheel-winNotificationCode');
                const code = codeInput.value;

                if (!code) {
                    return;
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(() => {
                        showCopyFeedback(event);
                    }).catch(() => {
                        copyToClipboardFallback(codeInput, event);
                    });
                } else {
                    copyToClipboardFallback(codeInput, event);
                }
            }

            window.luckyWheelCopyPrizeCode = copyPrizeCode;

            function copyToClipboardFallback(input, event) {
                input.select();
                input.setSelectionRange(0, 99999);
                try {
                    document.execCommand('copy');
                    showCopyFeedback(event);
                } catch (err) {
                    console.error('Failed to copy:', err);
                    alert('Не удалось скопировать код. Пожалуйста, скопируйте вручную: ' + input.value);
                }
            }

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

                    await loadPrizeImages();
                    initWheel();
                    updateSpinsInfo();
                    await applyWonPrizeRotationIfNeeded();
                } catch (error) {
                    console.error('Error loading wheel:', error);
                    showError('Ошибка загрузки данных: ' + error.message);
                }
            }

            async function loadPrizeImages() {
                const imagePromises = prizes.map(async (prize) => {
                    if (prize.image) {
                        return new Promise((resolve) => {
                            const img = new Image();
                            if (prize.image.startsWith('http://') || prize.image.startsWith('https://')) {
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

                if (canvas && ctx) {
                    drawWheel(currentRotation);
                }
            }

            function initWheel() {
                document.getElementById('lucky-wheel-loading').style.display = 'none';
                document.getElementById('lucky-wheel-wheelContent').style.display = 'block';

                canvas = document.getElementById('lucky-wheel-wheelCanvas');
                ctx = canvas.getContext('2d');

                const container = canvas.parentElement;
                const size = Math.min(container.clientWidth - 20, 400);
                canvas.width = size;
                canvas.height = size;

                centerX = canvas.width / 2;
                centerY = canvas.height / 2;
                radius = Math.min(centerX, centerY) - 10;

                drawWheel();
                notifyCallbacks('ready', {});
            }

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

            function calculateOptimalTextSize(text, sectorWidth, sectorHeight, minFontSize = 10, maxFontSize = 95) {
                const tempCanvas = document.createElement('canvas');
                const tempCtx = tempCanvas.getContext('2d');

                let bestFontSize = minFontSize;
                let bestLines = [];

                while (maxFontSize - minFontSize > 1) {
                    const fontSize = Math.floor((minFontSize + maxFontSize) / 2);
                    tempCtx.font = `bold ${fontSize}px Arial`;

                    const singleLineMetrics = tempCtx.measureText(text);
                    let lines = [];

                    if (singleLineMetrics.width <= sectorWidth * 0.9) {
                        lines = [text];
                    } else {
                        const words = text.split(' ');
                        let firstLine = '';
                        let secondLine = '';

                        for (let i = 0; i < words.length; i++) {
                            const testFirstLine = firstLine ? firstLine + ' ' + words[i] : words[i];
                            const testFirstMetrics = tempCtx.measureText(testFirstLine);

                            if (testFirstMetrics.width > sectorWidth * 0.9 && firstLine) {
                                secondLine = words.slice(i).join(' ');
                                firstLine = firstLine;
                                break;
                            } else {
                                firstLine = testFirstLine;
                            }
                        }

                        if (!secondLine && words.length > 1) {
                            const midPoint = Math.floor(words.length / 2);
                            firstLine = words.slice(0, midPoint).join(' ');
                            secondLine = words.slice(midPoint).join(' ');
                        }

                        const firstMetrics = tempCtx.measureText(firstLine);
                        const secondMetrics = tempCtx.measureText(secondLine);

                        if (firstMetrics.width <= sectorWidth * 0.9 && secondMetrics.width <= sectorWidth * 0.9) {
                            lines = [firstLine, secondLine];
                        } else {
                            lines = [text];
                        }
                    }

                    const lineHeight = fontSize * 1.2;
                    const totalHeight = lines.length * lineHeight;

                    if (totalHeight <= sectorHeight * 0.8 && lines.every(line => tempCtx.measureText(line).width <= sectorWidth * 0.9)) {
                        bestFontSize = fontSize;
                        bestLines = lines;
                        minFontSize = fontSize;
                    } else {
                        maxFontSize = fontSize;
                    }
                }

                if (bestLines.length === 0) {
                    bestFontSize = minFontSize;
                    tempCtx.font = `bold ${bestFontSize}px Arial`;
                    const words = text.split(' ');
                    let firstLine = '';
                    let secondLine = '';

                    for (let i = 0; i < words.length; i++) {
                        const testFirstLine = firstLine ? firstLine + ' ' + words[i] : words[i];
                        const testFirstMetrics = tempCtx.measureText(testFirstLine);

                        if (testFirstMetrics.width > sectorWidth * 0.9 && firstLine) {
                            secondLine = words.slice(i).join(' ');
                            firstLine = firstLine;
                            break;
                        } else {
                            firstLine = testFirstLine;
                        }
                    }

                    if (!secondLine && words.length > 1) {
                        const midPoint = Math.floor(words.length / 2);
                        firstLine = words.slice(0, midPoint).join(' ');
                        secondLine = words.slice(midPoint).join(' ');
                    }

                    bestLines = secondLine ? [firstLine, secondLine] : [firstLine];
                }

                return { fontSize: bestFontSize, lines: bestLines };
            }

            function drawWheel(rotation = 0) {
                if (!ctx) return;

                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const normalizedPrizes = normalizeProbabilities(prizes);
                const totalAngle = 2 * Math.PI;
                let currentAngle = -Math.PI / 2 + rotation;

                normalizedPrizes.forEach((prize, index) => {
                    const angle = (prize.probability / 100) * totalAngle;

                    ctx.beginPath();
                    ctx.moveTo(centerX, centerY);
                    ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle);
                    ctx.closePath();

                    const color = prize.color || getColorByIndex(index);
                    ctx.fillStyle = color;
                    ctx.fill();

                    ctx.strokeStyle = '#fff';
                    ctx.lineWidth = 2;
                    ctx.stroke();

                    ctx.save();
                    ctx.translate(centerX, centerY);
                    ctx.rotate(currentAngle + angle / 2);

                    const prizeImage = prizeImages[prize.id];

                    if (prizeImage && prize.image) {
                        const midRadius = radius * 0.65;
                        const sectorWidth = 2 * Math.sin(angle / 2) * midRadius;
                        const sectorHeight = radius * 0.8;

                        const imageAspectRatio = prizeImage.width / prizeImage.height;
                        const sectorAspectRatio = sectorWidth / sectorHeight;

                        let imageWidth, imageHeight;
                        if (imageAspectRatio > sectorAspectRatio) {
                            imageWidth = sectorWidth * 0.95;
                            imageHeight = imageWidth / imageAspectRatio;
                        } else {
                            imageHeight = sectorHeight * 0.95;
                            imageWidth = imageHeight * imageAspectRatio;
                        }

                        const imageDistance = midRadius;
                        const imageX = imageDistance;
                        const imageY = 0;

                        ctx.save();

                        ctx.beginPath();
                        ctx.moveTo(0, 0);
                        ctx.arc(0, 0, radius * 0.98, -angle / 2 - 0.05, angle / 2 + 0.05);
                        ctx.closePath();
                        ctx.clip();

                        ctx.save();
                        ctx.translate(imageX, imageY);
                        ctx.rotate(1.5);
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
                            ctx.restore();
                            ctx.restore();
                            ctx.restore();

                            const midRadius = radius * 0.65;
                            const sectorWidth = 2 * Math.sin(angle / 2) * midRadius;
                            const sectorHeight = radius * 0.8;

                            const textConfig = calculateOptimalTextSize(prize.name, sectorWidth, sectorHeight);

                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillStyle = '#fff';
                            ctx.font = `bold ${textConfig.fontSize}px Arial`;

                            const lineHeight = textConfig.fontSize * 1.2;
                            const startY = -(textConfig.lines.length - 1) * lineHeight / 2;
                            textConfig.lines.forEach((line, index) => {
                                ctx.fillText(line, radius * 0.6, startY + index * lineHeight);
                            });
                        }

                        ctx.restore();
                        ctx.restore();
                    } else {
                        const midRadius = radius * 0.65;
                        const sectorWidth = 2 * Math.sin(angle / 2) * midRadius;
                        const sectorHeight = radius * 0.8;

                        const textConfig = calculateOptimalTextSize(prize.name, sectorWidth, sectorHeight);

                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = '#fff';
                        ctx.font = `bold ${textConfig.fontSize}px Arial`;

                        const lineHeight = textConfig.fontSize * 1.2;
                        const startY = -(textConfig.lines.length - 1) * lineHeight / 2;
                        textConfig.lines.forEach((line, index) => {
                            ctx.fillText(line, radius * 0.6, startY + index * lineHeight);
                        });
                    }

                    ctx.restore();

                    currentAngle += angle;
                });
            }

            function getColorByIndex(index) {
                const colors = [
                    '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A',
                    '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2',
                    '#F8B739', '#E74C3C', '#3498DB', '#2ECC71'
                ];
                return colors[index % colors.length];
            }

            function findPrizeIndex(prizeId) {
                return prizes.findIndex(p => p.id === prizeId);
            }

            function getPrizeCenterAngle(prizeIndex) {
                const normalizedPrizes = normalizeProbabilities(prizes);
                let cumulativeAngle = -Math.PI / 2;

                for (let i = 0; i < normalizedPrizes.length; i++) {
                    const prizeAngle = (normalizedPrizes[i].probability / 100) * 2 * Math.PI;
                    if (i === prizeIndex) {
                        return cumulativeAngle + prizeAngle / 2;
                    }
                    cumulativeAngle += prizeAngle;
                }

                return cumulativeAngle;
            }

            function calculateRotationForPrize(prizeId) {
                const prizeIndex = findPrizeIndex(prizeId);
                if (prizeIndex === -1) {
                    console.warn('Prize not found:', prizeId);
                    return 0;
                }

                const prizeCenterAngle = getPrizeCenterAngle(prizeIndex);
                const rotation = -Math.PI / 2 - prizeCenterAngle;
                const normalizedRotation = ((rotation % (2 * Math.PI)) + (2 * Math.PI)) % (2 * Math.PI);

                return normalizedRotation;
            }

            async function spin() {
                if (isSpinning) return;

                isSpinning = true;
                const spinButton = document.getElementById('lucky-wheel-spinButton');
                spinButton.disabled = true;
                hideError();

                try {
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
                        if (data.error === 'Already won today' && data.today_win) {
                            const prize = data.today_win.prize;
                            const prizeCode = data.today_win.code || null;
                            const spinId = data.today_win.spin_id || null;
                            saveWin(prize, prizeCode, null, spinId);
                            setTimeout(() => {
                                showWinNotification(prize, prizeCode);
                                showWonPrizeBlock(prize, prizeCode);
                            }, 100);
                            blockSpinning();
                            isSpinning = false;
                            return;
                        }
                        throw new Error(data.error || data.message || 'Ошибка вращения');
                    }

                    notifyCallbacks('spin', data);

                    let prizeIndex = -1;
                    if (data.prize) {
                        prizeIndex = findPrizeIndex(data.prize.id);
                    }

                    await animateSpin(prizeIndex, data);
                    updateSpinsInfo(data.spins_count, data.spins_limit);

                    if (data.prize) {
                        showResult(data.prize, data.code);
                        notifyCallbacks('win', data.prize);

                        const prizeCode = data.code || null;
                        const spinId = data.spin_id || null;
                        saveWin(data.prize, prizeCode, null, spinId);

                        setTimeout(() => {
                            showWinNotification(data.prize, prizeCode);
                            showWonPrizeBlock(data.prize, prizeCode);
                        }, 500);

                        blockSpinning();
                    } else {
                        showResult(null);
                    }

                } catch (error) {
                    console.error('Spin error:', error);
                    showError('Ошибка: ' + error.message);
                    notifyCallbacks('error', { message: error.message });
                } finally {
                    isSpinning = false;

                    if (wheelData.spins_limit) {
                        setTimeout(() => {
                            loadWheelData();
                        }, 500);
                    } else {
                        spinButton.disabled = false;
                    }
                }
            }

            function animateSpin(prizeIndex, spinData) {
                return new Promise((resolve) => {
                    const normalizedPrizes = normalizeProbabilities(prizes);

                    let finalAngle = 0;
                    if (prizeIndex >= 0 && prizeIndex < normalizedPrizes.length) {
                        finalAngle = getPrizeCenterAngle(prizeIndex);
                    } else {
                        finalAngle = -Math.PI / 2 + Math.random() * 2 * Math.PI;
                    }

                    const spins = 5;
                    const targetRotation = -Math.PI / 2 - finalAngle;
                    const finalRotation = currentRotation + (spins * 2 * Math.PI) + targetRotation;

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

            function showResult(prize, code = '') {
                const spinButton = document.getElementById('lucky-wheel-spinButton');
                if (wheelData.spins_limit) {
                } else {
                    spinButton.disabled = false;
                }
            }

            function updateSpinsInfo(spinsCount = null, spinsLimit = null) {
                const infoEl = document.getElementById('lucky-wheel-spinsInfo');
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

            function showError(message) {
                document.getElementById('lucky-wheel-loading').style.display = 'none';
                const errorEl = document.getElementById('lucky-wheel-error');
                const winNotification = document.getElementById('lucky-wheel-winNotification');

                errorEl.textContent = message;
                errorEl.classList.add('show');

                const isWinNotificationVisible = winNotification &&
                    winNotification.style.display !== 'none' &&
                    winNotification.style.display !== '' &&
                    (winNotification.classList.contains('show') || winNotification.offsetHeight > 0);

                if (isWinNotificationVisible) {
                    errorEl.classList.add('lucky-wheel-error-overlay');
                    setTimeout(() => {
                        errorEl.classList.remove('show', 'lucky-wheel-error-overlay');
                    }, 5000);
                } else {
                    errorEl.classList.remove('lucky-wheel-error-overlay');
                }
            }

            function hideError() {
                const errorEl = document.getElementById('lucky-wheel-error');
                errorEl.classList.remove('show', 'lucky-wheel-error-overlay');
            }

            function set_prize_image(prizeEmailImage){
                const imageContainer = document.getElementById('lucky-wheel-winNotificationImageContainer');
                const imageElement = document.getElementById('lucky-wheel-winNotificationImage');

                if (prizeEmailImage && imageContainer && imageElement) {
                    let imageUrl = prizeEmailImage;
                    if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                        if (imageUrl.startsWith('/')) {
                            imageUrl = imageUrl;
                        } else {
                            imageUrl = `${APP_URL}/storage/${prizeEmailImage}`;
                        }
                    }

                    imageElement.src = imageUrl;
                    imageElement.alt = 'Приз';
                    //imageContainer.style.display = 'block';
                }
            }

            function show_prize_image(){
                const imageContainer = document.getElementById('lucky-wheel-winNotificationImageContainer');
                imageContainer.style.display = 'block';
            }

            document.getElementById('lucky-wheel-spinButton').addEventListener('click', spin);

            // Инициализация при загрузке DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
</div>







