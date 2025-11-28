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
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.8;
            color: #2c2c2c;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .email-header {
            background-image: url('{{ asset('images/baden-fon1.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 80px 40px;
            text-align: center;
            position: relative;
            color: #ffffff;
        }
        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(44, 44, 44, 0.7) 0%, rgba(100, 100, 100, 0.5) 100%);
        }
        .email-header-content {
            position: relative;
            z-index: 1;
        }
        .email-logo {
            max-width: 200px;
            margin-bottom: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }
        .email-header h1 {
            font-size: 42px;
            font-weight: 400;
            margin-bottom: 15px;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
            letter-spacing: 2px;
            font-style: italic;
        }
        .guest-name {
            font-size: 24px;
            color: #ffffff;
            margin: 20px 0 0 0;
            font-weight: 300;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
        }
        .email-body {
            padding: 50px 40px;
            background-color: #ffffff;
        }
        .greeting-section {
            margin-bottom: 40px;
            text-align: center;
        }
        .greeting-text {
            font-size: 19px;
            color: #4a4a4a;
            line-height: 2;
            margin-bottom: 20px;
            font-style: italic;
        }
        .prize-section {
            background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%);
            border: 1px solid #e0ddd8;
            border-radius: 0;
            padding: 40px;
            margin: 40px 0;
            text-align: center;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.05);
        }
        .prize-title {
            font-size: 28px;
            font-weight: 400;
            color: #5a4a3a;
            margin-bottom: 30px;
            font-style: italic;
            letter-spacing: 1px;
        }
        .qr-code-wrapper {
            margin: 30px 0;
            text-align: center;
        }
        .qr-code-image {
            max-width: 220px;
            height: auto;
            border: 2px solid #8b7355;
            border-radius: 0;
            padding: 15px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .qr-instruction {
            font-size: 15px;
            color: #6a6a6a;
            margin-top: 20px;
            font-style: italic;
            line-height: 1.8;
        }
        .prize-description {
            font-size: 17px;
            color: #4a4a4a;
            margin-top: 25px;
            line-height: 2;
            text-align: left;
        }
        .promo-code-section {
            background: #f8f6f3;
            border: 3px solid #8b7355;
            border-radius: 0;
            padding: 45px;
            text-align: center;
            margin: 40px 0;
            box-shadow: 0 6px 20px rgba(139, 115, 85, 0.2);
        }
        .promo-code-label {
            font-size: 14px;
            color: #5a4a3a;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 20px;
            font-weight: 400;
            font-style: italic;
        }
        .promo-code-value {
            font-size: 44px;
            font-weight: 300;
            color: #3a2a1a;
            letter-spacing: 10px;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 30px 50px;
            border-radius: 0;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #8b7355;
            margin: 15px 0;
        }
        .promo-id-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #8b7355;
        }
        .promo-id-label {
            font-size: 13px;
            color: #5a4a3a;
            margin-bottom: 12px;
            font-weight: 400;
            font-style: italic;
        }
        .promo-id-value {
            font-size: 22px;
            font-weight: 300;
            color: #3a2a1a;
            font-family: 'Courier New', monospace;
            letter-spacing: 4px;
        }
        .additional-text {
            font-size: 17px;
            color: #4a4a4a;
            line-height: 2;
            margin: 40px 0;
            text-align: center;
            font-style: italic;
        }
        .email-footer {
            background: linear-gradient(135deg, #3a2a1a 0%, #2a1a0a 100%);
            color: #e8e5e0;
            padding: 40px 30px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer p {
            margin-bottom: 15px;
            line-height: 1.8;
        }
        .email-footer strong {
            font-size: 18px;
            display: block;
            margin-bottom: 20px;
            color: #f5f3f0;
            font-weight: 400;
            letter-spacing: 1px;
        }
        .email-footer .contacts {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(232, 229, 224, 0.2);
        }
        .email-footer .contacts p {
            margin-bottom: 10px;
        }
        .no-reply-note {
            font-size: 12px;
            color: rgba(232, 229, 224, 0.7);
            margin-top: 25px;
            font-style: italic;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 25px;
            }
            .email-header {
                padding: 50px 25px;
            }
            .email-header h1 {
                font-size: 32px;
            }
            .promo-code-value {
                font-size: 32px;
                letter-spacing: 6px;
                padding: 25px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="email-header-content">
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
                <h1>Поздравляем!</h1>
                @if($spin->guest && $spin->guest->name)
                <div class="guest-name">Дорогой {{ $spin->guest->name }}</div>
                @else
                <div class="guest-name">Дорогой гость</div>
                @endif
            </div>
        </div>

        <div class="email-body">
            <div class="greeting-section">
                <div class="greeting-text">
                    <p>С радостью сообщаем тебе прекрасную новость!</p>
                    <p>Ты стал победителем нашей лотереи и выиграл замечательный приз!</p>
                </div>
            </div>

            <div class="prize-section">
                <div class="prize-title">{{ $spin->prize->getNameWithoutSeparator() }}</div>
                
                @if(isset($qrCodeDataUri) && $qrCodeDataUri)
                <div class="qr-code-wrapper">
                    <img src="{{ $qrCodeDataUri }}" alt="QR код" class="qr-code-image">
                    <div class="qr-instruction">
                        Пожалуйста, отсканируй этот QR код при визите в наш спа-комплекс для получения приза
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
                    <div style="margin: 30px 0;">
                        <img src="{{ $emailImageUrl }}" alt="{{ $spin->prize->getNameWithoutSeparator() }}" style="max-width: 100%; height: auto; border-radius: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                    </div>
                @endif

                @if($spin->prize->description)
                    <div class="prize-description">{{ $spin->prize->description }}</div>
                @endif

                @if($spin->prize->text_for_winner)
                    <div class="prize-description" style="margin-top: 20px;">
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
                <div class="promo-code-value" style="font-size: 36px; letter-spacing: 6px;">{{ $spin->code }}</div>
            </div>
            @endif

            <div class="additional-text">
                <p>Мы с нетерпением ждём твоего визита в наш спа-комплекс!</p>
                <p>Приходи и получи свой заслуженный приз. Будем рады видеть тебя!</p>
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

