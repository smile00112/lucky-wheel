<link rel="stylesheet" href="{{ url('css/widget/wheel.css') }}">

<div class="lucky-wheel-content">
<div class="lucky-wheel-container">
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
                <div class="won-prize-label">Выиграно сегодня:</div>
                <div class="won-prize-name" id="wonPrizeCode"></div>
            </div>
        </div>

        <button id="spinButton" class="spin-button">Крутить колесо!</button>
        <div id="spinsInfo" class="spins-info"></div>
    </div>

    <div id="error" class="error"></div>
</div>

<div id="winNotification" class="win-notification" style="display: none;">
    <button class="win-notification-close">&times;</button>
    <h3>🎉 Поздравляем с выигрышем!</h3>
    <div class="win-notification-message" id="winNotificationMessage"></div>
    <div class="win-notification-code" id="winNotificationCodeContainer">
        <input type="text" id="winNotificationCode" readonly value="">
        <button title="Копировать код">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
        </button>
    </div>

    <a href="#" id="winNotificationPdfLink" class="win-notification-pdf-link" style="display: none;" target="_blank">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
        </svg>
        <span>Скачать сертификат PDF</span>
    </a>

    <div class="win-notification-form" id="winNotificationFormContainer" style="display: none;">
        <p class="win-notification-form-text">Для получения приза на почту заполните данные:</p>
        <form id="winNotificationForm">
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

    <div class="win-notification-send-container" id="winNotificationSendContainer" style="display: none;">
        <button type="button" class="win-notification-submit-btn" id="winNotificationSubmitBtn2">
            Отправить приз
        </button>
    </div>

    <div class="win-notification-image-container" id="winNotificationImageContainer" style="display: none;">
        <img id="winNotificationImage" src="" alt="Приз">
    </div>
</div>
</div>

<script>
    window.API_URL = '{{ url("/api/widget") }}';
    window.APP_URL = '{{ url('/') }}';
    window.WHEEL_SLUG = '{{ $wheel->slug }}';
</script>

<script type="module" src="{{ url('js/widget/app.js') }}"></script>

