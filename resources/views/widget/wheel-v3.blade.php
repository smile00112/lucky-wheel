@php
    $defaultTexts = [
        'loading_text' => 'Загрузка...',
        'spin_button_text' => 'Крутить колесо!',
        'spin_button_blocked_text' => 'Вы уже выиграли сегодня. Попробуйте завтра!',
        'won_prize_label' => 'Выиграно сегодня:',
        'win_notification_title' => 'Ваш подарок',
        'win_notification_win_text' => 'Скопируйте промокод или покажите QR-код на ресепшене',
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
        'win_notification_message_dop' => 'Скопируйте промокод или покажите QR-код на ресепшене',
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



<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
{{--{!! $wheel->generateStyleCss() !!}--}}
    body{
        margin: 0;
    }
</style>

<div class="lucky-wheel-content">
    <style>
        {!! file_get_contents(resource_path('css/widget/wheel-v3.css')) !!}
    </style>
<div class="lucky-wheel-container 222">
    <div id="loading" class="loading">{{ $texts['loading_text'] }}</div>

    <div id="wheelContent" class="wheel-content-contener"  style="display: none;">
        <div class="wheel-content-wrapper">
            <div class="wheel-container">
                <canvas id="wheelCanvas" class="wheel"></canvas>
                <div id="wonPrizeBlock" class="won-prize-block" style="display: none;">
                    <div class="won-prize-label">{{ $texts['won_prize_label'] }}</div>
                    <div class="won-prize-name" id="wonPrizeCode"></div>
                </div>
            </div>
            <div id="spinsInfo" class="spins-info"></div>
        </div>

<div id="winNotification" class="win-notification" style="display: none;">

    <div class="winning-form" id="winningFormContainer" style="display: none;">
        <h3>{{ $texts['win_notification_title'] }}</h3>
        <div class="win-notification-message" id="winNotificationMessage"></div>
        <div class="win-notification-message_dop" id="winNotificationMessageDop">{{ $texts['win_notification_message_dop'] }}</div>
        <div class="win-notification-code-input-wrapper">
            <div class="win-notification-code" id="winNotificationCodeContainer">
                <input type="text" id="winNotificationCode" readonly value="">
                <button title="{{ $texts['copy_code_button_title'] }}">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                    </svg>
                </button>

            </div>
            <div class="win-notification-image-container" id="winNotificationImageContainer" style="display: none;">
                <img id="winNotificationImage" src="" alt="{{ $texts['prize_image_alt'] }}">
            </div>
        </div>
        <div class="win-notification-code" id="winNotificationPromoCodeContainer" style="display: none;">

                <input type="text" id="winNotificationPromoCode" readonly value="">
                <button title="{{ $texts['copy_code_button_title'] }}">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                    </svg>
                </button>

        </div>

        <a href="#" id="winNotificationPdfLink" class="win-notification-pdf-link" target="_blank">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
            </svg>
            <span>{{ $texts['download_pdf_text'] }}</span>
        </a>
    </div>

    <div class="wheel-info-block" id="wheelInfoBlock" style="display: none;">
        <h1 id="wheelInfoName">{{ $wheel->name ?? $texts['wheel_default_name'] }}</h1>
        @if($wheel->description)
            <div class="description" id="wheelInfoDescription">{{ $wheel->description }}</div>
        @endif

        <button type="button" id="wheelInfoSpinButton" class="spin-button">{{ $texts['spin_button_text'] }}</button>

        @if($wheel->image)
            <div class="wheel-info-image-container">
                <img id="wheelInfoImage" src="{{ Storage::disk('public')->url($wheel->image) }}" alt="{{ $wheel->name ?? $texts['wheel_default_name'] }}">
            </div>
        @endif
    </div>

    <div class="win-notification-form" id="winNotificationFormContainer">
        <div class="win-notification-form-header" id="winNotificationFormHeader" style="display: none;">
            <h3>{{ $texts['win_notification_title'] }}</h3>
            <div class="win-notification-message" id="winNotificationFormMessage"></div>
            <div class="win-notification-message_dop" id="winNotificationFormMessageDop">{{ $texts['win_notification_message_dop'] }}</div>
        </div>
        <div class="win-notification-form-initial" id="winNotificationFormInitial">
            <h1>Крути колесо!</h1>
            <div class="description">Заполни поля ниже, чтобы крутить колесо и выиграть призы! Распродажа только сегодня!</div>
        </div>
{{--        <h1>{{ $wheel->name ?? $texts['wheel_default_name'] }}</h1>--}}
{{--        @if($wheel->description)--}}
{{--            <div class="description">{{ $wheel->description }}</div>--}}
{{--        @endif--}}
{{--        <p class="win-notification-form-text">{{ $texts['form_description'] }}</p>--}}
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
            <div class="win-notification-form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="winNotificationAgreement" name="agreement" required>
                    <span class="checkbox-text">Я даю согласие на обработку персональных данных и принимаю условия пользовательского соглашения</span>
                </label>
            </div>
            <button type="button" id="spinButton" class="spin-button">{{ $texts['spin_button_text'] }}</button>
            <button type="submit" class="win-notification-submit-btn- spin-button" id="winNotificationSubmitBtn" style="display: none;">
                {{ $texts['form_submit_text'] }}
            </button>
        </form>
    </div>

    <div class="win-notification-send-container" id="winNotificationSendContainer" style="display: none;">
        <button type="button" class="win-notification-submit-btn- spin-button" id="winNotificationSubmitBtn2">
            {{ $texts['form_submit_text'] }}
        </button>
    </div>

        </div>
    </div>

    <div id="error" class="error"></div>
</div>

<script>
    window.API_URL = '{{ url("/api/widget") }}';
    window.APP_URL = '{{ url('/') }}';
    window.WHEEL_SLUG = '{{ $wheel->slug }}';
    window.WHEEL_TEXTS = @json($texts);
    window.GUEST_DATA = @json($guestData);
</script>

<script type="module" src="{{ route('widget.assets', ['path' => 'widget-v3/app.js']) }}"></script>
