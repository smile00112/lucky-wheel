<?php

declare(strict_types=1);

namespace App\Support;

class DefaultTemplates
{
    public static function email(): string
    {
        return '<!DOCTYPE html>
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
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;
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
            font-family: \'Courier New\', monospace;
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
        .code-note {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            font-style: italic;
            text-align: center;
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
            {logo_html}
            <h1>🎉 Поздравляем с выигрышем!</h1>
            {guest_name_html}
            <div class="subtitle">{company_name}</div>
        </div>

        <div class="email-body">
            <div class="prize-section">
                <div class="prize-title">🏆 Ваш приз: {prize_email_name}</div>
                {prize_email_image_html}
                {prize_description_html}
                {prize_text_for_winner_html}
            </div>

            {code_html}
            {prize_email_coupon_after_code_text_html}

            <div class="divider"></div>

            <div class="content-text">
                <p>Уважаемый{guest_name}!</p>
                <p>Поздравляем вас с выигрышем приза <strong>{prize_full_name}</strong>!</p>
                {prize_email_text_after_congratulation_html}
                <p>Спасибо за участие в нашей акции!</p>
            </div>

            {code_note_html}
        </div>

        <div class="email-footer">
            <p><strong>{company_name}</strong></p>
            <p>Это письмо отправлено автоматически, пожалуйста, не отвечайте на него.</p>
            <p>Если у вас возникли вопросы, свяжитесь с нами.</p>
        </div>
    </div>
</body>
</html>';
    }

    public static function pdf(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Сертификат выигрыша</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #667eea;
            padding: 40px;
            color: #333;
        }
        .certificate {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
        }
        .title {
            font-size: 32px;
            color: #3f51b5;
            margin-bottom: 20px;
        }
        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
        }
        .prize-name {
            font-size: 28px;
            color: #2d2d2d;
            margin: 20px 0;
        }
        .guest-name {
            font-size: 22px;
            color: #444;
            margin: 15px 0;
        }
        .description {
            font-size: 16px;
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        .code-block {
            background: #f8f9fa;
            padding: 30px;
            margin: 30px 0;
            border-radius: 12px;
            border: 2px dashed #3f51b5;
        }
        .code-label {
            font-size: 14px;
            color: #3f51b5;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .code-value {
            font-size: 28px;
            color: #222;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #777;
        }
        .date {
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }
        .prize-image {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 12px;
        }
        .code-note {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="title">СЕРТИФИКАТ ПОБЕДИТЕЛЯ</div>
        <div class="subtitle">{company_name}</div>

        <div class="guest-name">Поздравляем, {guest_name}!</div>
        <div class="prize-name">{prize_full_name}</div>

        {prize_email_image_html}
        <div class="description">{prize_description_html}</div>
        <div class="description">{prize_text_for_winner_html}</div>

        <div class="code-block">
            <div class="code-label">Промокод 2</div>
            <div class="code-value">{prize_value}</div>
            {code_note_html}
        </div>

        <div class="footer">
            Этот сертификат подтверждает ваш выигрыш в розыгрыше "{wheel_name}".
        </div>
        <div class="date">Дата выигрыша: {date}</div>
    </div>
</body>
</html>';
    }
}

