<!DOCTYPE html>
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
            font-family: 'DejaVu Sans', Arial, sans-serif;
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
        .certificate-header {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }
        .certificate-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 40px;
            font-weight: bold;
        }
        .prize-name {
            font-size: 32px;
            color: #764ba2;
            font-weight: bold;
            margin: 30px 0;
            padding: 20px;
            background: #f5f7fa;
            border-radius: 10px;
        }
        .guest-name {
            font-size: 22px;
            color: #667eea;
            margin: 20px 0;
            font-weight: 600;
        }
        .prize-code {
            font-size: 28px;
            color: #667eea;
            margin: 30px 0;
            padding: 20px 30px;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 3px solid #667eea;
            border-radius: 12px;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-weight: bold;
            letter-spacing: 4px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .prize-code-label {
            font-size: 14px;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .prize-description {
            font-size: 16px;
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        .certificate-footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            font-size: 14px;
            color: #999;
        }
        .date {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        .wheel-name {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .prize-image {
            max-width: 100%;
            max-height: 300px;
            margin: 20px auto;
            display: block;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="certificate-header">ПОЗДРАВЛЯЕМ!</div>
        <div class="certificate-title">Сертификат выигрыша</div>

        @if(isset($guest) && $guest && $guest->name)
        <div class="guest-name">Уважаемый {{ $guest->name }}!</div>
        @endif

        <div class="wheel-name">{{ $wheel->name ?? 'Колесо Фортуны' }}</div>

        <div class="prize-name">Название приза: {{ $prize->name }}</div>

        @if(isset($emailImageUrl) && $emailImageUrl)
        <img src="{{ $emailImageUrl }}" alt="{{ $prize->name }}" class="prize-image">
        @endif

        @if($prize->description)
        <div class="prize-description">{{ $prize->description }}</div>
        @endif

        @if($code)
        <div style="margin: 30px 0;">
            <div class="prize-code-label">Идентификационный номер</div>
            <div class="prize-code">{{ $code }}</div>
        </div>
        @endif

        @if($prize->text_for_winner)
        <div class="prize-description">{{ $prize->text_for_winner }}</div>
        @endif

        <div class="certificate-footer">
            <div class="date">Дата выигрыша: {{ $date }}</div>
        </div>
    </div>
</body>
</html>

