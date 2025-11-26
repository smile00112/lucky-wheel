@php
    $defaultTexts = [
        'loading_text' => 'Загрузка...',
        'spin_button_text' => 'Крутить колесо!',
        'spin_button_blocked_text' => 'Вы уже выиграли сегодня. Попробуйте завтра!',
        'won_prize_label' => 'Выиграно сегодня:',
        'win_notification_title' => '🎉 Поздравляем с выигрышем!',
        'win_notification_win_text' => 'Вы выиграли:',
        'copy_code_button_title' => 'Копировать код',
        'code_not_specified' => 'Код не указан',
        'download_pdf_text' => 'Скачать сертификат PDF',
        'form_description' => 'Для получения приза на почту заполните данные:',
        'form_name_placeholder' => 'Ваше имя',
        'form_email_placeholder' => 'Email',
        'form_phone_placeholder' => '+7 (XXX) XXX-XX-XX',
        'form_submit_text' => 'Отправить приз',
        'form_submit_loading' => 'Отправка...',
        'form_submit_success' => '✓ Приз отправлен!',
        'form_submit_error' => 'Приз уже получен',
        'form_success_message' => '✓ Данные сохранены! Приз будет отправлен на указанную почту.',
        'prize_image_alt' => 'Приз',
        'spins_info_format' => 'Вращений: {count} / {limit}',
        'spins_limit_format' => 'Лимит вращений: {limit}',
        'error_init_guest' => 'Ошибка инициализации: не удалось создать гостя',
        'error_init' => 'Ошибка инициализации:',
        'error_no_prizes' => 'Нет доступных призов',
        'error_load_data' => 'Ошибка загрузки данных:',
        'error_spin' => 'При розыгрыше произошла ошибка! Обратитесь в поддержку сервиса.',
        'error_general' => 'Ошибка:',
        'error_send' => 'Ошибка при отправке',
        'error_copy_code' => 'Не удалось скопировать код. Пожалуйста, скопируйте вручную:',
        'wheel_default_name' => 'Колесо Фортуны',
    ];
    $settings = $wheel->settings ?? [];
    $texts = array_merge($defaultTexts, $settings);
    $guestData = $guest ? [
        'id' => $guest->id,
        'name' => $guest->name,
        'email' => $guest->email,
        'phone' => $guest->phone,
    ] : null;
@endphp

<link rel="stylesheet" href="{{ url('css/widget/wheel.css') }}">

<style>
{!! $wheel->generateStyleCss() !!}
</style>

<div class="lucky-wheel-content">
<div class="lucky-wheel-container">
    <h1>🎡 {{ $wheel->name ?? $texts['wheel_default_name'] }}</h1>
    @if($wheel->description)
        <div class="description">{{ $wheel->description }}</div>
    @endif

    <div id="loading" class="loading">{{ $texts['loading_text'] }}</div>

    <div id="wheelContent" style="display: none;">
        <div class="wheel-container">
            <div class="pointer"></div>
            <canvas id="wheelCanvas" class="wheel"></canvas>
            <div id="wonPrizeBlock" class="won-prize-block" style="display: none;">
                <div class="won-prize-label">{{ $texts['won_prize_label'] }}</div>
                <div class="won-prize-name" id="wonPrizeCode"></div>
            </div>
        </div>

        <button id="spinButton" class="spin-button">{{ $texts['spin_button_text'] }}</button>
        <div id="spinsInfo" class="spins-info"></div>
    </div>

    <div id="error" class="error"></div>
</div>

<div id="winNotification" class="win-notification" style="display: none;">
    <button class="win-notification-close">&times;</button>
    <h3>{{ $texts['win_notification_title'] }}</h3>
    <div class="win-notification-message" id="winNotificationMessage"></div>
    <div class="win-notification-code" id="winNotificationCodeContainer">
        <input type="text" id="winNotificationCode" readonly value="">
        <button title="{{ $texts['copy_code_button_title'] }}">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
        </button>
    </div>

    <a href="#" id="winNotificationPdfLink" class="win-notification-pdf-link" style="display: none;" target="_blank">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
        </svg>
        <span>{{ $texts['download_pdf_text'] }}</span>
    </a>

    <div class="win-notification-form" id="winNotificationFormContainer" style="display: none;">
        <p class="win-notification-form-text">{{ $texts['form_description'] }}</p>
        <form id="winNotificationForm">
            <div class="win-notification-form-group">
                <input type="text" id="winNotificationName" name="name" placeholder="{{ $texts['form_name_placeholder'] }}" required>
            </div>
            <div class="win-notification-form-group">
                <input type="email" id="winNotificationEmail" name="email" placeholder="{{ $texts['form_email_placeholder'] }}" required>
            </div>
            <div class="win-notification-form-group">
                <input type="tel" id="winNotificationPhone" name="phone" placeholder="{{ $texts['form_phone_placeholder'] }}" required maxlength="18">
            </div>
            <button type="submit" class="win-notification-submit-btn" id="winNotificationSubmitBtn">
                {{ $texts['form_submit_text'] }}
            </button>
        </form>
    </div>

    <div class="win-notification-send-container" id="winNotificationSendContainer" style="display: none;">
        <button type="button" class="win-notification-submit-btn" id="winNotificationSubmitBtn2">
            {{ $texts['form_submit_text'] }}
        </button>
    </div>

    <div class="win-notification-image-container" id="winNotificationImageContainer" style="display: none;">
        <img id="winNotificationImage" src="" alt="{{ $texts['prize_image_alt'] }}">
    </div>
</div>
</div>

<script>
    window.API_URL = '{{ url("/api/widget") }}';
    window.APP_URL = '{{ url('/') }}';
    window.WHEEL_SLUG = '{{ $wheel->slug }}';
    window.WHEEL_TEXTS = @json($texts);
    window.GUEST_DATA = @json($guestData);
</script>

<script type="module" src="{{ route('widget.assets', ['path' => 'app.js']) }}"></script>

