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
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c2a 50%, #6ba644 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #ffffff;
        }
        .email-header .subtitle {
            font-size: 16px;
            opacity: 0.95;
        }
        .email-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .prize-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .email-body {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .prize-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #6ba644;
            padding: 25px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .prize-title {
            font-size: 24px;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 15px;
        }
        .prize-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .guest-name {
            font-size: 20px;
            color: #ffffff;
            margin: 15px 0 0 0;
            font-weight: 500;
            opacity: 0.95;
        }
        .code-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 3px solid #6ba644;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(107, 166, 68, 0.3);
        }
        .code-label {
            font-size: 14px;
            color: #2d5016;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .code-value {
            font-size: 40px;
            font-weight: 700;
            color: #2d5016;
            letter-spacing: 6px;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 2px solid #6ba644;
        }
        .content-text {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .content-text h2 {
            color: #2d5016;
            font-size: 20px;
            margin-bottom: 15px;
            margin-top: 25px;
        }
        .content-text p {
            margin-bottom: 15px;
        }
        .content-text ul, .content-text ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        .content-text li {
            margin-bottom: 8px;
        }
        .content-text a {
            color: #6ba644;
            text-decoration: none;
        }
        .content-text a:hover {
            text-decoration: underline;
        }
        .content-text strong {
            color: #2d5016;
            font-weight: 600;
        }
        .email-footer {
            background-color: #2d5016;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer p {
            margin-bottom: 10px;
        }
        .email-footer a {
            color: #6ba644;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #6ba644, transparent);
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 25px 20px;
            }
            .email-header {
                padding: 30px 15px;
            }
            .email-header h1 {
                font-size: 24px;
            }
            .code-value {
                font-size: 32px;
                letter-spacing: 4px;
                padding: 15px 25px;
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
            <div class="guest-name">{{ $spin->guest->name }}</div>
            @endif
            <div class="subtitle">{{ $settings->company_name ?: 'Колесо фортуны' }}</div>
        </div>

        <div class="email-body">
            <div class="prize-section">
                <div class="prize-title">🏆 Ваш приз: {{ $spin->prize->name }}</div>
                @if($spin->prize->email_image)
                    @php
                        $emailImageUrl = filter_var($spin->prize->email_image, FILTER_VALIDATE_URL)
                            ? $spin->prize->email_image
                            : (str_starts_with($spin->prize->email_image, '/')
                                ? url($spin->prize->email_image)
                                : asset('storage/' . $spin->prize->email_image));
                    @endphp
                    <img src="{{ $emailImageUrl }}" alt="{{ $spin->prize->name }}" class="prize-image">
                @endif
                @if($spin->prize->description)
                    <div class="prize-description">{{ $spin->prize->description }}</div>
                @endif
                @if($spin->prize->text_for_winner)
                    <div class="prize-description">
                        <strong>Сообщение:</strong> {{ $spin->prize->text_for_winner }}
                    </div>
                @endif
            </div>

            @if($spin->code)
                <div class="code-section">
                    <div class="code-label">Идентификационный номер</div>
                    <div class="code-value">{{ $spin->code }}</div>
                </div>
            @endif

            <div class="divider"></div>

            @if($settings->email_template)
                <div class="content-text">
                    {!! str_replace(
                        [
                            '{company_name}',
                            '{prize_name}',
                            '{prize_description}',
                            '{prize_text_for_winner}',
                            '{prize_type}',
                            '{prize_value}',
                            '{code}',
                            '{guest_name}',
                            '{guest_email}',
                            '{guest_phone}'
                        ],
                        [
                            $settings->company_name ?: 'Колесо фортуны',
                            $spin->prize->name ?? '',
                            $spin->prize->description ?? '',
                            $spin->prize->text_for_winner ?? '',
                            $spin->prize->type ?? '',
                            $spin->prize->value ?? '',
                            $spin->code ?: 'не указан',
                            $spin->guest->name ?? '',
                            $spin->guest->email ?? '',
                            $spin->guest->phone ?? ''
                        ],
                        $settings->email_template
                    ) !!}
                </div>
            @else
                <div class="content-text">
                    <p>Уважаемый{{ $spin->guest->name ? ' ' . $spin->guest->name : '' }}!</p>
                    <p>Поздравляем вас с выигрышем приза <strong>{{ $spin->prize->name }}</strong>!</p>
                    @if($spin->code)
                        <p>Идентификационный номер: <strong>{{ $spin->code }}</strong></p>
                    @endif
                    <p>Спасибо за участие в нашей акции!</p>
                </div>
            @endif
        </div>

        <div class="email-footer">
            <p><strong>{{ $settings->company_name ?: 'Колесо фортуны' }}</strong></p>
            <p>Это письмо отправлено автоматически, пожалуйста, не отвечайте на него.</p>
            <p>Если у вас возникли вопросы, свяжитесь с нами.</p>
        </div>
    </div>
</body>
</html>

