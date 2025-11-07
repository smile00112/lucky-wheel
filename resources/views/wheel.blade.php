<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Колесо Фортуны</title>
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
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .wheel-container {
            position: relative;
            width: 400px;
            height: 400px;
            margin: 0 auto 30px;
        }

        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            border: 10px solid #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
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
            font-size: 14px;
            text-align: center;
            padding: 10px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .wheel-section span {
            transform: rotate(45deg);
            max-width: 80px;
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

        .spin-button {
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
        }

        .spin-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .spin-button:active {
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
        }

        .result p {
            color: #666;
            font-size: 16px;
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
            .wheel-container {
                width: 300px;
                height: 300px;
            }

            h1 {
                font-size: 2em;
            }

            .wheel-section {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎡 Колесо Фортуны</h1>
        
        <div class="wheel-container">
            <div class="pointer"></div>
            <canvas id="wheelCanvas" class="wheel" width="400" height="400"></canvas>
        </div>
        
        <button id="spinButton" class="spin-button">Крутить колесо!</button>
        
        <div id="result" class="result">
            <h2>🎉 Поздравляем!</h2>
            <p id="resultText"></p>
        </div>
    </div>

    <script>
        // Данные призов из PHP
        @php
            $prizesData = [];
            if ($wheel && $wheel->activePrizes) {
                foreach ($wheel->activePrizes as $prize) {
                    $prizesData[] = [
                        'name' => $prize->name,
                        'color' => $prize->color ?: null,
                        'probability' => $prize->probability ?: 0
                    ];
                }
            }
        @endphp
        const prizesData = @json($prizesData);
        
        // Если нет призов, создаем тестовые
        const testPrizes = prizesData.length > 0 ? prizesData : [
            { name: 'Скидка 10%', color: '#FF6B6B', probability: 20 },
            { name: 'Скидка 20%', color: '#4ECDC4', probability: 15 },
            { name: 'Скидка 30%', color: '#45B7D1', probability: 10 },
            { name: 'Бесплатная доставка', color: '#FFA07A', probability: 25 },
            { name: 'Подарок', color: '#98D8C8', probability: 15 },
            { name: 'Попробуйте еще раз', color: '#F7DC6F', probability: 15 }
        ];

        // Настройка колеса
        const canvas = document.getElementById('wheelCanvas');
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 10;

        let isSpinning = false;
        let currentRotation = 0;

        // Нормализуем вероятности (сумма должна быть 100%)
        function normalizeProbabilities(prizes) {
            const total = prizes.reduce((sum, p) => sum + (parseFloat(p.probability) || 0), 0);
            if (total === 0) {
                // Если вероятности не заданы, распределяем равномерно
                const equalProb = 100 / prizes.length;
                return prizes.map(p => ({ ...p, probability: equalProb }));
            }
            // Нормализуем до 100%
            return prizes.map(p => ({
                ...p,
                probability: (parseFloat(p.probability) || 0) * 100 / total
            }));
        }

        const normalizedPrizes = normalizeProbabilities(testPrizes);

        // Рисуем колесо
        function drawWheel(rotation = 0) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const totalAngle = 2 * Math.PI;
            let currentAngle = -Math.PI / 2 + rotation; // Начинаем сверху
            
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
                ctx.font = 'bold 14px Arial';
                ctx.fillText(prize.name, radius * 0.6, 0);
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

        // Выбрать приз на основе вероятностей
        function selectPrize() {
            const random = Math.random() * 100;
            let cumulative = 0;
            
            for (const prize of normalizedPrizes) {
                cumulative += prize.probability;
                if (random <= cumulative) {
                    return prize;
                }
            }
            
            return normalizedPrizes[normalizedPrizes.length - 1];
        }

        // Вычислить угол для выбранного приза
        function getPrizeAngle(prize) {
            let cumulativeAngle = -Math.PI / 2;
            
            for (const p of normalizedPrizes) {
                if (p === prize) {
                    return cumulativeAngle + (p.probability / 100) * Math.PI;
                }
                cumulativeAngle += (p.probability / 100) * 2 * Math.PI;
            }
            
            return cumulativeAngle;
        }

        // Вращение колеса
        function spin() {
            if (isSpinning) return;
            
            isSpinning = true;
            const spinButton = document.getElementById('spinButton');
            const result = document.getElementById('result');
            spinButton.disabled = true;
            result.classList.remove('show');
            
            // Выбираем приз
            const selectedPrize = selectPrize();
            const prizeAngle = getPrizeAngle(selectedPrize);
            
            // Вычисляем финальный угол (несколько полных оборотов + угол до приза)
            const spins = 5; // Количество полных оборотов
            const finalRotation = currentRotation + (spins * 2 * Math.PI) + (2 * Math.PI - prizeAngle);
            
            // Анимация
            const startRotation = currentRotation;
            const rotationDiff = finalRotation - startRotation;
            const duration = 4000; // 4 секунды
            const startTime = Date.now();
            
            function animate() {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing функция для плавного замедления
                const easeOut = 1 - Math.pow(1 - progress, 3);
                currentRotation = startRotation + rotationDiff * easeOut;
                
                drawWheel(currentRotation);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    // Анимация завершена
                    isSpinning = false;
                    spinButton.disabled = false;
                    
                    // Показываем результат
                    const resultText = document.getElementById('resultText');
                    resultText.textContent = `Вы выиграли: ${selectedPrize.name}!`;
                    result.classList.add('show');
                }
            }
            
            animate();
        }

        // Инициализация
        drawWheel();
        
        // Обработчик кнопки
        document.getElementById('spinButton').addEventListener('click', spin);
    </script>
</body>
</html>

