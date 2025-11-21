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
            overflow-x: hidden;
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

        .container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 100%;
            box-sizing: border-box;
        }

        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .description {
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .wheel-container {
            position: relative;
            width: 100%;
            max-width: 350px;
            margin: 0 auto 20px;
            aspect-ratio: 1;
        }

        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 8px solid #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .pointer {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 24px solid #ff4444;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .won-prize-block {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 11;
            text-align: center;
            min-width: 120px;
            font-size: 12px;
        }

        .spin-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }

        .spin-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .spin-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .error {
            margin-top: 15px;
            padding: 12px;
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 10px;
            color: #c33;
            display: none;
            font-size: 14px;
        }

        .error.show {
            display: block;
        }

        .spins-info {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
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
                <div id="wonPrizeBlock" class="won-prize-block" style="display: none;">
                    <div>Выиграно сегодня:</div>
                    <div id="wonPrizeCode"></div>
                </div>
            </div>

            <button id="spinButton" class="spin-button">Крутить колесо!</button>
            <div id="spinsInfo" class="spins-info"></div>
        </div>

        <div id="error" class="error"></div>
    </div>

    <script>
        const WHEEL_SLUG = '{{ $wheel->slug }}';
        const API_URL = '{{ url('/api/widget') }}';
        const TELEGRAM_AUTH_URL = '{{ url('/api/telegram/auth') }}';
        const TELEGRAM_SPIN_URL = '{{ url('/api/telegram/spin') }}';

        let tg = window.Telegram?.WebApp;
        let guestId = null;
        let telegramId = null;
        let wheelData = null;
        let prizes = [];
        let prizeImages = {};
        let canvas = null;
        let ctx = null;
        let centerX = 0;
        let centerY = 0;
        let radius = 0;
        let currentRotation = 0;
        let isSpinning = false;

        if (tg) {
            tg.ready();
            tg.expand();
        }

        async function init() {
            try {
                if (!tg || !tg.initData) {
                    showError('Telegram WebApp не доступен');
                    return;
                }

                const authResponse = await fetch(TELEGRAM_AUTH_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        initData: tg.initData,
                        wheel_slug: WHEEL_SLUG,
                    }),
                });

                if (!authResponse.ok) {
                    const errorData = await authResponse.json();
                    showError(errorData.error || 'Ошибка авторизации');
                    return;
                }

                const authData = await authResponse.json();
                guestId = authData.guest_id;
                telegramId = authData.telegram_id;

                await loadWheelData();
            } catch (error) {
                console.error('Init error:', error);
                showError('Ошибка инициализации: ' + error.message);
            }
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
            document.getElementById('loading').style.display = 'none';
            document.getElementById('wheelContent').style.display = 'block';

            canvas = document.getElementById('wheelCanvas');
            ctx = canvas.getContext('2d');

            const container = canvas.parentElement;
            const size = Math.min(container.clientWidth - 20, 350);
            canvas.width = size;
            canvas.height = size;

            centerX = canvas.width / 2;
            centerY = canvas.height / 2;
            radius = Math.min(centerX, centerY) - 10;

            drawWheel();
        }

        function drawWheel(rotation = 0) {
            if (!ctx) return;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const anglePerPrize = (2 * Math.PI) / prizes.length;

            prizes.forEach((prize, index) => {
                const startAngle = index * anglePerPrize + rotation;
                const endAngle = (index + 1) * anglePerPrize + rotation;

                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();

                ctx.fillStyle = prize.color || '#667eea';
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();

                const textAngle = startAngle + anglePerPrize / 2;
                const textRadius = radius * 0.7;
                const textX = centerX + Math.cos(textAngle) * textRadius;
                const textY = centerY + Math.sin(textAngle) * textRadius;

                ctx.save();
                ctx.translate(textX, textY);
                ctx.rotate(textAngle + Math.PI / 2);
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 14px Arial';
                ctx.fillText(prize.name, 0, 0);
                ctx.restore();

                if (prizeImages[prize.id]) {
                    const imgRadius = radius * 0.3;
                    const imgX = centerX + Math.cos(textAngle) * imgRadius;
                    const imgY = centerY + Math.sin(textAngle) * imgRadius;
                    const imgSize = radius * 0.15;

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(imgX, imgY, imgSize, 0, 2 * Math.PI);
                    ctx.clip();
                    ctx.drawImage(prizeImages[prize.id], imgX - imgSize, imgY - imgSize, imgSize * 2, imgSize * 2);
                    ctx.restore();
                }
            });
        }

        async function spin() {
            if (isSpinning) return;

            isSpinning = true;
            const spinButton = document.getElementById('spinButton');
            spinButton.disabled = true;
            hideError();

            try {
                const response = await fetch(TELEGRAM_SPIN_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        wheel_slug: WHEEL_SLUG,
                        guest_id: guestId,
                        telegram_id: telegramId,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Ошибка вращения');
                }

                const prizeIndex = prizes.findIndex(p => p.id === data.prize?.id);
                if (prizeIndex === -1 && data.prize) {
                    throw new Error('Приз не найден');
                }

                const targetAngle = prizeIndex !== -1 ? (2 * Math.PI * prizeIndex) / prizes.length : 0;
                const spinRotation = 5 * Math.PI * 2 + (Math.PI * 2 - targetAngle - currentRotation % (Math.PI * 2));

                let currentAngle = currentRotation;
                const targetRotation = currentRotation + spinRotation;
                const duration = 3000;
                const startTime = Date.now();

                function animate() {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOut = 1 - Math.pow(1 - progress, 3);

                    currentAngle = currentRotation + spinRotation * easeOut;
                    drawWheel(currentAngle);

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        currentRotation = targetRotation;
                        drawWheel(currentRotation);
                        isSpinning = false;
                        spinButton.disabled = false;
                        updateSpinsInfo();

                        if (data.prize) {
                            if (tg) {
                                tg.showAlert(`🎉 Вы выиграли: ${data.prize.name}!`);
                            }
                        }
                    }
                }

                animate();
            } catch (error) {
                console.error('Spin error:', error);
                showError(error.message);
                isSpinning = false;
                spinButton.disabled = false;
            }
        }

        async function applyWonPrizeRotationIfNeeded() {
            try {
                const response = await fetch(`${API_URL}/wheel/${WHEEL_SLUG}/today-win?guest_id=${guestId}`);
                const data = await response.json();

                if (data.has_win && data.prize) {
                    const prizeIndex = prizes.findIndex(p => p.id === data.prize.id);
                    if (prizeIndex !== -1) {
                        const targetAngle = (2 * Math.PI * prizeIndex) / prizes.length;
                        currentRotation = -Math.PI / 2 - targetAngle;
                        drawWheel(currentRotation);

                        document.getElementById('wonPrizeBlock').style.display = 'block';
                        document.getElementById('wonPrizeCode').textContent = data.code || data.prize.name;
                    }
                }
            } catch (error) {
                console.error('Error checking today win:', error);
            }
        }

        function updateSpinsInfo() {
            if (!wheelData) return;

            const infoEl = document.getElementById('spinsInfo');
            if (wheelData.spins_limit) {
                infoEl.textContent = `Осталось вращений: ${wheelData.spins_limit}`;
            }
        }

        function showError(message) {
            const errorEl = document.getElementById('error');
            errorEl.textContent = message;
            errorEl.classList.add('show');
        }

        function hideError() {
            const errorEl = document.getElementById('error');
            errorEl.classList.remove('show');
        }

        document.getElementById('spinButton')?.addEventListener('click', spin);

        init();
    </script>
</body>
</html>

