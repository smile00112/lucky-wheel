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
            font-family: 'Arial', 'Helvetica', sans-serif;
            line-height: 1.7;
            color: #1a1a1a;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .email-container {
            max-width: 620px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        }
        .email-header {
            background-image: url('{{ asset('images/baden-fon3.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 70px 35px;
            text-align: center;
            position: relative;
        }
        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%);
        }
        .email-header-content {
            position: relative;
            z-index: 1;
        }
        .email-logo {
            max-width: 180px;
            margin-bottom: 25px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
        }
        .email-header h1 {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 15px;
            color: #ffffff;
            text-shadow: 0 4px 12px rgba(0,0,0,0.4);
            letter-spacing: -1px;
            text-transform: uppercase;
        }
        .guest-name {
            font-size: 26px;
            color: #ffffff;
            margin: 18px 0 0 0;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
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
            font-size: 20px;
            color: #2d3748;
            line-height: 1.8;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .greeting-text p {
            margin-bottom: 15px;
        }
        .prize-section {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            border: 4px solid #fc8181;
            border-radius: 20px;
            padding: 40px;
            margin: 40px 0;
            text-align: center;
            box-shadow: 0 8px 24px rgba(252, 129, 129, 0.25);
        }
        .prize-title {
            font-size: 32px;
            font-weight: 900;
            color: #c53030;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }
        .qr-code-wrapper {
            margin: 30px 0;
            text-align: center;
        }
        .qr-code-image {
            max-width: 240px;
            height: auto;
            border: 4px solid #fc8181;
            border-radius: 20px;
            padding: 15px;
            background: #ffffff;
            box-shadow: 0 6px 20px rgba(252, 129, 129, 0.3);
        }
        .qr-instruction {
            font-size: 16px;
            color: #c53030;
            margin-top: 20px;
            font-weight: 600;
            line-height: 1.6;
        }
        .prize-description {
            font-size: 18px;
            color: #4a5568;
            margin-top: 25px;
            line-height: 1.8;
            text-align: left;
            font-weight: 500;
        }
        .promo-code-section {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
            border: 5px solid #f59e0b;
            border-radius: 24px;
            padding: 45px;
            text-align: center;
            margin: 40px 0;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
            position: relative;
            overflow: hidden;
        }
        .promo-code-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.1) 10px,
                rgba(255,255,255,0.1) 20px
            );
            animation: shine 3s infinite;
        }
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        .promo-code-content {
            position: relative;
            z-index: 1;
        }
        .promo-code-label {
            font-size: 16px;
            color: #78350f;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 20px;
            font-weight: 800;
        }
        .promo-code-value {
            font-size: 52px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            background-color: rgba(0,0,0,0.2);
            padding: 28px 50px;
            border-radius: 16px;
            display: inline-block;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border: 3px solid #ffffff;
            margin: 15px 0;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .promo-id-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 3px solid rgba(120, 53, 15, 0.3);
        }
        .promo-id-label {
            font-size: 14px;
            color: #78350f;
            margin-bottom: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .promo-id-value {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            font-family: 'Courier New', monospace;
            letter-spacing: 4px;
            text-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .additional-text {
            font-size: 18px;
            color: #2d3748;
            line-height: 1.8;
            margin: 40px 0;
            font-weight: 600;
            text-align: center;
        }
        .additional-text p {
            margin-bottom: 16px;
        }
        .email-footer {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: #e2e8f0;
            padding: 40px 35px;
            text-align: center;
            font-size: 15px;
        }
        .email-footer p {
            margin-bottom: 14px;
            line-height: 1.7;
        }
        .email-footer strong {
            font-size: 20px;
            display: block;
            margin-bottom: 22px;
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .email-footer .contacts {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid rgba(226, 232, 240, 0.2);
        }
        .email-footer .contacts p {
            margin-bottom: 10px;
            color: #cbd5e0;
            font-weight: 500;
        }
        .no-reply-note {
            font-size: 13px;
            color: #a0aec0;
            margin-top: 25px;
            font-style: italic;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 35px 28px;
            }
            .email-header {
                padding: 50px 28px;
            }
            .email-header h1 {
                font-size: 36px;
            }
            .promo-code-value {
                font-size: 38px;
                letter-spacing: 5px;
                padding: 24px 35px;
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
                <h1>🎉 ПОЗДРАВЛЯЕМ! 🎉</h1>
                @if($spin->guest && $spin->guest->name)
                <div class="guest-name">{{ $spin->guest->name }}!</div>
                @else
                <div class="guest-name">Дружок!</div>
                @endif
            </div>
        </div>

        <div class="email-body">
            <div class="greeting-section">
                <div class="greeting-text">
                    <p>🎊 УРА! ТЫ ВЫИГРАЛ! 🎊</p>
                    <p>Невероятная новость — ты стал победителем нашей лотереи!</p>
                </div>
            </div>

            <div class="prize-section">
                <div class="prize-title">🏆 {{ $spin->prize->getNameWithoutSeparator() }} 🏆</div>
                
                @if(isset($qrCodeDataUri) && $qrCodeDataUri)
                <div class="qr-code-wrapper">
                    <img src="{{ $qrCodeDataUri }}" alt="QR код" class="qr-code-image">
                    <div class="qr-instruction">
                        ⚡ Отсканируй QR код в спа-комплексе и получи приз! ⚡
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
                        <img src="{{ $emailImageUrl }}" alt="{{ $spin->prize->getNameWithoutSeparator() }}" style="max-width: 100%; height: auto; border-radius: 20px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); border: 4px solid #fc8181;">
                    </div>
                @endif

                @if($spin->prize->description)
                    <div class="prize-description">{{ $spin->prize->description }}</div>
                @endif

                @if($spin->prize->text_for_winner)
                    <div class="prize-description" style="margin-top: 22px;">
                        <strong>🔥 Важно:</strong> {{ $spin->prize->text_for_winner }}
                    </div>
                @endif
            </div>

            @if($spin->prize->value)
            <div class="promo-code-section">
                <div class="promo-code-content">
                    <div class="promo-code-label">Промокод</div>
                    <div class="promo-code-value">{{ $spin->prize->value }}</div>
                    
                    @if($spin->code)
                    <div class="promo-id-section">
                        <div class="promo-id-label">Идентификационный номер</div>
                        <div class="promo-id-value">{{ $spin->code }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @elseif($spin->code)
            <div class="promo-code-section">
                <div class="promo-code-content">
                    <div class="promo-code-label">Идентификационный номер</div>
                    <div class="promo-code-value" style="font-size: 42px; letter-spacing: 6px;">{{ $spin->code }}</div>
                </div>
            </div>
            @endif

            <div class="additional-text">
                <p>🚀 Приходи в наш спа-комплекс и забирай свой приз!</p>
                <p>💪 Мы ждём именно тебя! Будет круто!</p>
            </div>
        </div>

        <div class="email-footer">
            <strong>{{ $settings->company_name ?: 'Спа-комплекс' }}</strong>
            
            <div class="contacts">
                @if($settings->settings && isset($settings->settings['contact_phone']))
                <p>📞 Телефон: {{ $settings->settings['contact_phone'] }}</p>
                @endif
                @if($settings->settings && isset($settings->settings['contact_email']))
                <p>✉️ Email: {{ $settings->settings['contact_email'] }}</p>
                @endif
                @if($settings->settings && isset($settings->settings['contact_address']))
                <p>📍 Адрес: {{ $settings->settings['contact_address'] }}</p>
                @endif
            </div>

            <div class="no-reply-note">
                Это письмо отправлено автоматически. Пожалуйста, не отвечай на него.
            </div>
        </div>
    </div>
</body>
</html>

