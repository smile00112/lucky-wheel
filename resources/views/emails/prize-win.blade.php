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
        .code-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px dashed #6ba644;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            border-radius: 8px;
        }
        .code-label {
            font-size: 14px;
            color: #2d5016;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .code-value {
            font-size: 36px;
            font-weight: 700;
            color: #2d5016;
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 15px 25px;
            border-radius: 6px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
                font-size: 28px;
                letter-spacing: 3px;
                padding: 12px 20px;
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
            <div class="subtitle">{{ $settings->company_name ?: 'Колесо фортуны' }}</div>
        </div>

        <div class="email-body">
            <div class="prize-section">
                <div class="prize-title">🏆 Ваш приз: {{ $spin->prize->name }}</div>
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
                    <div class="code-label">Код для получения приза</div>
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
                            '{code}',
                            '{guest_name}'
                        ],
                        [
                            $settings->company_name ?: 'Колесо фортуны',
                            $spin->prize->name,
                            $spin->code ?: 'не указан',
                            $spin->guest->name ?: $spin->guest->email ?: $spin->guest->phone ?: 'Уважаемый гость'
                        ],
                        $settings->email_template
                    ) !!}
                </div>
            @else
                <div class="content-text">
                    <p>Уважаемый{{ $spin->guest->name ? ' ' . $spin->guest->name : '' }}!</p>
                    <p>Поздравляем вас с выигрышем приза <strong>{{ $spin->prize->name }}</strong>!</p>
                    @if($spin->code)
                        <p>Ваш код для получения приза: <strong>{{ $spin->code }}</strong></p>
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

