<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поздравляем с выигрышем!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #e8d5c4 0%, #d4a574 50%, #c8966a 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .email-logo {
            max-width: 180px;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .email-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .email-header .subtitle {
            font-size: 18px;
            color: #ffffff;
            opacity: 0.95;
        }
        .guest-name {
            font-size: 22px;
            color: #ffffff;
            margin: 15px 0 0 0;
            font-weight: 500;
        }
        .email-body {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .greeting-section {
            margin-bottom: 30px;
        }
        .greeting-text {
            font-size: 18px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        .prize-section {
            background: linear-gradient(135deg, #fff8f0 0%, #f5ebe0 100%);
            border: 2px solid #d4a574;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .prize-title {
            font-size: 26px;
            font-weight: 600;
            color: #8b5a3c;
            margin-bottom: 20px;
        }
        .prize-image-wrapper {
            margin: 25px 0;
        }
        .prize-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .qr-code-wrapper {
            margin: 25px 0;
            text-align: center;
        }
        .qr-code-image {
            max-width: 250px;
            height: auto;
            border: 3px solid #d4a574;
            border-radius: 10px;
            padding: 10px;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .qr-instruction {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
            font-style: italic;
            line-height: 1.6;
        }
        .prize-description {
            font-size: 16px;
            color: #555;
            margin-top: 20px;
            line-height: 1.8;
            text-align: left;
        }
        .promo-code-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 4px solid #d4a574;
            border-radius: 12px;
            padding: 35px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 6px 20px rgba(212, 165, 116, 0.3);
        }
        .promo-code-label {
            font-size: 16px;
            color: #8b5a3c;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .promo-code-value {
            font-size: 48px;
            font-weight: 700;
            color: #8b5a3c;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 25px 40px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 3px solid #d4a574;
            margin: 10px 0;
        }
        .promo-id-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px dashed #d4a574;
        }
        .promo-id-label {
            font-size: 14px;
            color: #8b5a3c;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .promo-id-value {
            font-size: 20px;
            font-weight: 600;
            color: #8b5a3c;
            font-family: 'Courier New', monospace;
            letter-spacing: 3px;
        }
        .additional-text {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin: 30px 0;
        }
        .email-footer {
            background: linear-gradient(135deg, #8b5a3c 0%, #6b4423 100%);
            color: #ffffff;
            padding: 35px 20px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer p {
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .email-footer strong {
            font-size: 16px;
            display: block;
            margin-bottom: 15px;
        }
        .email-footer .contacts {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .email-footer .contacts p {
            margin-bottom: 8px;
        }
        .no-reply-note {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            margin-top: 20px;
            font-style: italic;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 25px 20px;
            }
            .email-header {
                padding: 30px 15px;
            }
            .email-header h1 {
                font-size: 26px;
            }
            .promo-code-value {
                font-size: 36px;
                letter-spacing: 4px;
                padding: 20px 30px;
            }
            .prize-title {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            @if($settings->logo)
                @php
                    $logoUrl = filter_var($settings->logo, FILTER_VALIDATE_URL)
                        ? $settings->logo
                        : (str_starts_with($settings->logo, '/')
                            ? url($settings->logo)
                            : asset('storage/' . $settings->logo));
                @endphp
                <img src="{{ $logoUrl }}" alt="{{ $settings->company_name ?: 'Логотип' }}" class="email-logo">
            @endif
            <h1>🎉 Поздравляем с выигрышем!</h1>
            @if($spin->guest && $spin->guest->name)
            <div class="guest-name">Привет, {{ $spin->guest->name }}!</div>
            @else
            <div class="guest-name">Привет!</div>
            @endif
            <div class="subtitle">{{ $settings->company_name ?: 'Спа-комплекс' }}</div>
        </div>

        <div class="email-body">
            <div class="greeting-section">
                <div class="greeting-text">
                    <p>Мы рады сообщить тебе отличную новость! 🎊</p>
                    <p>Ты выиграл приз в нашей лотерее онлайн колеса призов!</p>
                </div>
            </div>

            <div class="prize-section">
                <div class="prize-title">🏆 Твой приз: {{ $spin->prize->name }}</div>
                
                @if(isset($qrCodeDataUri) && $qrCodeDataUri)
                <div class="qr-code-wrapper">
                    <img src="{{ $qrCodeDataUri }}" alt="QR код" class="qr-code-image">
                    <div class="qr-instruction">
                        Отсканируй этот QR код при посещении нашего спа-комплекса, чтобы получить приз
                    </div>
                </div>
                @elseif($spin->prize->email_image)
                    @php
                        $emailImageUrl = filter_var($spin->prize->email_image, FILTER_VALIDATE_URL)
                            ? $spin->prize->email_image
                            : (str_starts_with($spin->prize->email_image, '/')
                                ? url($spin->prize->email_image)
                                : asset('storage/' . $spin->prize->email_image));
                    @endphp
                    <div class="prize-image-wrapper">
                        <img src="{{ $emailImageUrl }}" alt="{{ $spin->prize->name }}" class="prize-image">
                    </div>
                @endif

                @if($spin->prize->description)
                    <div class="prize-description">{{ $spin->prize->description }}</div>
                @endif

                @if($spin->prize->text_for_winner)
                    <div class="prize-description" style="margin-top: 15px;">
                        <strong>Важная информация:</strong> {{ $spin->prize->text_for_winner }}
                    </div>
                @endif
            </div>

            @if($spin->prize->value)
            <div class="promo-code-section">
                <div class="promo-code-label">Промокод</div>
                <div class="promo-code-value">{{ $spin->prize->value }}</div>
                
                @if($spin->code)
                <div class="promo-id-section">
                    <div class="promo-id-label">Идентификационный номер</div>
                    <div class="promo-id-value">{{ $spin->code }}</div>
                </div>
                @endif
            </div>
            @elseif($spin->code)
            <div class="promo-code-section">
                <div class="promo-code-label">Идентификационный номер</div>
                <div class="promo-code-value" style="font-size: 36px; letter-spacing: 4px;">{{ $spin->code }}</div>
            </div>
            @endif

            <div class="additional-text">
                <p>Ждём тебя в нашем спа-комплексе! Приходи и забирай свой заслуженный приз. Мы будем рады видеть тебя!</p>
                <p>Если у тебя возникнут вопросы, не стесняйся обращаться к нам - мы всегда готовы помочь.</p>
            </div>
        </div>

        <div class="email-footer">
            <strong>{{ $settings->company_name ?: 'Спа-комплекс' }}</strong>
            
            <div class="contacts">
                @if($settings->settings && isset($settings->settings['contact_phone']))
                <p>Телефон: {{ $settings->settings['contact_phone'] }}</p>
                @endif
                @if($settings->settings && isset($settings->settings['contact_email']))
                <p>Email: {{ $settings->settings['contact_email'] }}</p>
                @endif
                @if($settings->settings && isset($settings->settings['contact_address']))
                <p>Адрес: {{ $settings->settings['contact_address'] }}</p>
                @endif
            </div>

            <div class="no-reply-note">
                Это письмо отправлено автоматически. Пожалуйста, не отвечай на него.
            </div>
        </div>
    </div>
</body>
</html>

