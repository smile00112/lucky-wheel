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
            color: #1a1a1a;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .email-header {
            background-image: url('{{ asset('images/baden-fon2.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 60px 30px;
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
            background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
        }
        .email-header-content {
            position: relative;
            z-index: 1;
        }
        .email-logo {
            max-width: 160px;
            margin-bottom: 25px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .email-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }
        .guest-name {
            font-size: 20px;
            color: #4a5568;
            margin: 15px 0 0 0;
            font-weight: 500;
        }
        .email-body {
            padding: 45px 35px;
            background-color: #ffffff;
        }
        .greeting-section {
            margin-bottom: 35px;
        }
        .greeting-text {
            font-size: 18px;
            color: #2d3748;
            line-height: 1.75;
            margin-bottom: 18px;
        }
        .greeting-text p {
            margin-bottom: 12px;
        }
        .prize-section {
            background: #f7fafc;
            border-left: 4px solid #4299e1;
            border-radius: 12px;
            padding: 35px;
            margin: 35px 0;
        }
        .prize-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 25px;
            letter-spacing: -0.3px;
        }
        .qr-code-wrapper {
            margin: 25px 0;
            text-align: center;
        }
        .qr-code-image {
            max-width: 200px;
            height: auto;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .qr-instruction {
            font-size: 14px;
            color: #718096;
            margin-top: 16px;
            line-height: 1.6;
        }
        .prize-description {
            font-size: 16px;
            color: #4a5568;
            margin-top: 20px;
            line-height: 1.75;
        }
        .promo-code-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            margin: 35px 0;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }
        .promo-code-label {
            font-size: 13px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 18px;
            font-weight: 600;
            opacity: 0.9;
        }
        .promo-code-value {
            font-size: 42px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 6px;
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            background-color: rgba(255,255,255,0.15);
            padding: 24px 40px;
            border-radius: 12px;
            display: inline-block;
            backdrop-filter: blur(10px);
            margin: 12px 0;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .promo-id-section {
            margin-top: 28px;
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,0.3);
        }
        .promo-id-label {
            font-size: 12px;
            color: #ffffff;
            margin-bottom: 10px;
            font-weight: 500;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .promo-id-value {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            letter-spacing: 3px;
        }
        .additional-text {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.75;
            margin: 35px 0;
        }
        .additional-text p {
            margin-bottom: 14px;
        }
        .email-footer {
            background: #1a202c;
            color: #e2e8f0;
            padding: 35px 30px;
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
            margin-bottom: 18px;
            color: #ffffff;
            font-weight: 600;
        }
        .email-footer .contacts {
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px solid rgba(226, 232, 240, 0.15);
        }
        .email-footer .contacts p {
            margin-bottom: 8px;
            color: #cbd5e0;
        }
        .no-reply-note {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 22px;
            font-style: italic;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 25px;
            }
            .email-header {
                padding: 40px 25px;
            }
            .email-header h1 {
                font-size: 28px;
            }
            .promo-code-value {
                font-size: 32px;
                letter-spacing: 4px;
                padding: 20px 30px;
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
                <h1>🎉 Поздравляем!</h1>
                @if($spin->guest && $spin->guest->name)
                <div class="guest-name">Привет, {{ $spin->guest->name }}!</div>
                @else
                <div class="guest-name">Привет!</div>
                @endif
            </div>
        </div>

        <div class="email-body">
            <div class="greeting-section">
                <div class="greeting-text">
                    <p>Отличные новости! 🎊</p>
                    <p>Ты выиграл приз в нашей лотерее онлайн колеса призов!</p>
                </div>
            </div>

            <div class="prize-section">
                <div class="prize-title">🏆 Твой приз: {{ $spin->prize->getNameWithoutSeparator() }}</div>
                
                @if(isset($qrCodeDataUri) && $qrCodeDataUri)
                <div class="qr-code-wrapper">
                    <img src="{{ $qrCodeDataUri }}" alt="QR код" class="qr-code-image">
                    <div class="qr-instruction">
                        Отсканируй QR код при посещении спа-комплекса для получения приза
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
                    <div style="margin: 25px 0;">
                        <img src="{{ $emailImageUrl }}" alt="{{ $spin->prize->getNameWithoutSeparator() }}" style="max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    </div>
                @endif

                @if($spin->prize->description)
                    <div class="prize-description">{{ $spin->prize->description }}</div>
                @endif

                @if($spin->prize->text_for_winner)
                    <div class="prize-description" style="margin-top: 18px;">
                        <strong>Важно:</strong> {{ $spin->prize->text_for_winner }}
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
                <p>Ждём тебя в нашем спа-комплексе! Приходи и забирай свой заслуженный приз.</p>
                <p>Если возникнут вопросы — обращайся, мы всегда готовы помочь.</p>
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

