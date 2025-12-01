<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Колесо Фортуны - Получите больше продаж и заявок</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    
            <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
        .wheel-animation {
            animation: spin 10s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
            </style>
    </head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold gradient-text">🎡 LuckyWheel</h1>
                </div>
                <div class="flex items-center space-x-4">
            @if (Route::has('login'))
                    @auth
                            <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-gray-900 px-4 py-2 rounded-md text-sm font-medium">
                                Панель управления
                        </a>
                    @else
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 px-4 py-2 rounded-md text-sm font-medium">
                                Войти
                            </a>
                            <button onclick="openRegisterModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Регистрация
                            </button>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    Получите больше продаж и заявок<br>с виджетом колеса фортуны
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-purple-100">
                    Универсальный попап, который привлекает посетителей сайта<br>и улучшает результаты распродажи
                </p>
                <div class="flex justify-center space-x-4">
                    <button onclick="openRegisterModal()" class="bg-white text-purple-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition duration-200">
                        Попробовать бесплатно
                    </button>
                    <a href="#features" class="bg-purple-700 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-purple-800 transition duration-200">
                        Узнать больше
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Колесо фортуны принесет результат любому бизнесу
                </h2>
                <p class="text-xl text-gray-600">
                    Вовлекайте и мотивируйте клиентов оставить свои контакты
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 p-8 rounded-xl">
                    <div class="text-4xl mb-4">🛒</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Интернет-магазин</h3>
                    <p class="text-gray-600">
                        Увеличьте число заказов, разыгрывая скидки и бонусы через игровую механику
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-8 rounded-xl">
                    <div class="text-4xl mb-4">💼</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Продажа услуг</h3>
                    <p class="text-gray-600">
                        Улучшите результаты рекламы и получите больше лидов с вашего лендинга
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-pink-50 to-red-50 p-8 rounded-xl">
                    <div class="text-4xl mb-4">📦</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Оптовые продажи</h3>
                    <p class="text-gray-600">
                        Привлекайте внимание партнеров к спецпредложениям и повысьте конверсию посадочных страниц
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Используйте все возможности колеса фортуны
                </h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 mb-16">
                <div class="bg-white p-8 rounded-xl shadow-sm">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <span class="text-2xl">👥</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Превращает посетителей в лиды</h3>
                    <p class="text-gray-600">
                        Пользователь оставляет контакты ради выигрыша — вы получаете заявку от клиента, который заинтересован в вашей услуге
                    </p>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-sm">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <span class="text-2xl">⏱️</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Удерживает клиентов на сайте</h3>
                    <p class="text-gray-600">
                        В момент, когда клиент хочет уйти с сайта — появляется шанс «проверить удачу». Колесо фортуны удерживает внимание и оставляет клиента на сайте
                    </p>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-sm">
                    <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mb-4">
                        <span class="text-2xl">🎁</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Мотивирует на первое касание</h3>
                    <p class="text-gray-600">
                        Подарок за участие, даже символический, создает позитивный опыт при первом касании и повышает шансы на возврат клиента
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Создайте свое колесо фортуны
                </h2>
                <p class="text-xl text-gray-600">
                    Выберите нужные цвета, чтобы интегрировать виджет в визуал вашего сайта<br>и управляйте логикой выпадения призов
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-purple-600 font-bold">1</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Интуитивный редактор</h3>
                                <p class="text-gray-600">
                                    Легко изменить цвет любого элемента — от фона и кнопки до стрелки и выигрышного сектора
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Адаптивный дизайн</h3>
                                <p class="text-gray-600">
                                    Колесо фортуны автоматически адаптируется под мобильные устройства
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-pink-600 font-bold">3</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Вы контролируете удачу</h3>
                                <p class="text-gray-600">
                                    Задайте призам шанс выпадения, можно настроить так, чтобы каждый пользователь выигрывал подарок, либо минимизировать шансы до нуля
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-green-600 font-bold">4</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Сценарии результата</h3>
                                <p class="text-gray-600">
                                    Выбирайте, что происходит дальше: отображение промокода, автоматическая подгрузка формы или переход на нужную страницу
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-center">
                    <div class="relative">
                        <div class="w-64 h-64 rounded-full border-8 border-purple-200 flex items-center justify-center bg-gradient-to-br from-purple-100 to-pink-100 shadow-xl">
                            <div class="text-6xl">🎡</div>
                        </div>
                        <div class="absolute top-0 right-0 w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center shadow-lg transform rotate-45">
                            <span class="text-white text-2xl">→</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Conditions Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Максимум лидов за счет умных условий показа
                </h2>
                <p class="text-xl text-gray-600">
                    Гибкие настройки помогут персонализировать ваше колесо фортуны,<br>его увидит только нужная аудитория
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">📄</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">На выбранных страницах</h3>
                    <p class="text-gray-600 text-sm">
                        Разместите виджет на всех страницах или только, например, на каталоге, в корзине или лендинге со специальной акцией
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">📱</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Для определенных устройств</h3>
                    <p class="text-gray-600 text-sm">
                        Покажите колесо фортуны только на десктопе, либо на мобильных или на всех устройствах, в зависимости от поведения ЦА
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🔗</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">По UTM-меткам</h3>
                    <p class="text-gray-600 text-sm">
                        Настройте свое колесо для разных источников: уникальные предложения для рекламы, рассылок или соцсетей
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🌍</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">С учетом геолокации</h3>
                    <p class="text-gray-600 text-sm">
                        Настраивайте призы и сценарии для пользователей из разных городов
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🚪</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">При попытке ухода</h3>
                    <p class="text-gray-600 text-sm">
                        Покажите колесо фортуны в тот момент, когда клиент уходит с сайта — чтобы привлечь его внимание в последний момент
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">👤</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">С учетом поведения</h3>
                    <p class="text-gray-600 text-sm">
                        Установите таймер появления колеса фортуны, ограничьте повторный показ, чтобы попап не раздражал клиентов
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Setup Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Как подключить колесо фортуны
                </h2>
                <p class="text-xl text-gray-600">
                    Запустить виджет на сайте можно за 5 минут — без разработчиков и сложных интеграций
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl font-bold text-purple-600">1</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Добавьте скрипт на сайт</h3>
                    <p class="text-gray-600">
                        Это можно сделать самостоятельно или обратиться в нашу поддержку
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl font-bold text-blue-600">2</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Создайте колесо фортуны</h3>
                    <p class="text-gray-600">
                        Воспользуйтесь редактором или генератором. Задайте условия и включите виджет
                    </p>
        </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl font-bold text-pink-600">3</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Собирайте контакты</h3>
                    <p class="text-gray-600">
                        Форма будет собирать контакты для отдела продаж. Все они будут храниться в едином журнале лидов
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">
                Готовы увеличить продажи?
            </h2>
            <p class="text-xl mb-8 text-purple-100">
                Начните использовать колесо фортуны уже сегодня и получите первые лиды уже завтра
            </p>
            <button onclick="openRegisterModal()" class="inline-block bg-white text-purple-600 px-10 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition duration-200 shadow-lg">
                Начать бесплатно
            </button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-white mb-2">🎡 LuckyWheel</h3>
                <p class="mb-4">Платформа для создания колеса фортуны</p>
                <p class="text-sm">© {{ date('Y') }} LuckyWheel. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Registration Modal -->
    <div id="registerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Регистрация</h2>
                    <button onclick="closeRegisterModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="registerForm" onsubmit="handleRegister(event)">
                    <div class="mb-4">
                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Название компании
                        </label>
                        <input type="text" id="company_name" name="company_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        <div id="company_name_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        <div id="email_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Пароль
                        </label>
                        <input type="password" id="password" name="password" required minlength="8"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                        <div id="password_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Подтверждение пароля
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    @if (!app()->environment('local'))
                    <div class="mb-4">
                        <div id="yandex-captcha"></div>
                        <div id="captcha_error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    @endif

                    <div id="form_message" class="mb-4 hidden"></div>

                    <button type="submit" id="submitBtn"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md font-medium transition duration-200">
                        Зарегистрироваться
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Yandex SmartCaptcha Script -->
    @if (!app()->environment('local'))
    <script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>
    @endif

    <script>
        const isLocal = {{ app()->environment('local') ? 'true' : 'false' }};
        const yandexCaptchaClientKey = '{{ config("services.yandex.captcha_client_key") }}';
        let captchaToken = null;
        let captchaWidgetId = null;

        function openRegisterModal() {
            document.getElementById('registerModal').classList.remove('hidden');
            if (!isLocal) {
                initYandexCaptcha();
            }
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
            document.getElementById('registerForm').reset();
            clearErrors();
            if (!isLocal && captchaWidgetId) {
                window.smartCaptcha.reset(captchaWidgetId);
                captchaToken = null;
            }
        }

        function initYandexCaptcha() {
            if (isLocal) {
                return;
            }

            if (!yandexCaptchaClientKey) {
                console.error('Yandex Captcha client key not configured');
                return;
            }

            if (window.smartCaptcha && !captchaWidgetId) {
                captchaWidgetId = window.smartCaptcha.render('yandex-captcha', {
                    sitekey: yandexCaptchaClientKey,
                    callback: function(token) {
                        captchaToken = token;
                        const errorEl = document.getElementById('captcha_error');
                        if (errorEl) {
                            errorEl.classList.add('hidden');
                        }
                    },
                    'error-callback': function() {
                        captchaToken = null;
                    }
                });
            }
        }

        function clearErrors() {
            document.querySelectorAll('[id$="_error"]').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
            const formMessage = document.getElementById('form_message');
            formMessage.classList.add('hidden');
            formMessage.textContent = '';
        }

        function showError(field, message) {
            const errorEl = document.getElementById(field + '_error');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
        }

        function showFormMessage(message, isSuccess = false) {
            const formMessage = document.getElementById('form_message');
            formMessage.textContent = message;
            formMessage.classList.remove('hidden');
            formMessage.className = isSuccess 
                ? 'mb-4 p-3 bg-green-100 text-green-700 rounded-md' 
                : 'mb-4 p-3 bg-red-100 text-red-700 rounded-md';
        }

        async function handleRegister(event) {
            event.preventDefault();
            clearErrors();

            if (!isLocal && !captchaToken) {
                showError('captcha', 'Пожалуйста, пройдите проверку капчи');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Регистрация...';

            const formData = new FormData(event.target);
            if (!isLocal && captchaToken) {
                formData.append('captcha_token', captchaToken);
            }

            try {
                const response = await fetch('{{ route("register.submit") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showFormMessage(data.message, true);
                    setTimeout(() => {
                        closeRegisterModal();
                        window.location.href = '{{ url("/admin") }}';
                    }, 2000);
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errors = data.errors[field];
                            showError(field, Array.isArray(errors) ? errors[0] : errors);
                        });
                    } else {
                        showFormMessage(data.message || 'Произошла ошибка при регистрации');
                    }
                    if (!isLocal && captchaWidgetId) {
                        window.smartCaptcha.reset(captchaWidgetId);
                        captchaToken = null;
                    }
                }
            } catch (error) {
                showFormMessage('Произошла ошибка при отправке формы. Пожалуйста, попробуйте еще раз.');
                if (!isLocal && captchaWidgetId) {
                    window.smartCaptcha.reset(captchaWidgetId);
                    captchaToken = null;
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }

        // Закрытие модального окна при клике вне его
        document.getElementById('registerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRegisterModal();
            }
        });
    </script>
    </body>
</html>
