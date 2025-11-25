<?php

namespace App\Mail;

use App\Models\Spin;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PrizeWinMail extends Mailable
{
    use Queueable, SerializesModels;

    public Spin $spin;
    public Setting $settings;
    public $html;

    /**
     * Create a new message instance.
     */
    public function __construct(Spin $spin)
    {
        // Загружаем связи для использования в шаблоне
        $this->spin = $spin->load(['prize', 'guest']);
        $this->settings = Setting::getInstance();
        $this->html = $this->buildEmailHtml();
    }

    /**
     * Построить HTML письма из шаблона настроек
     */
    protected function buildEmailHtml(): string
    {
        $template = $this->settings->email_template;
        
        // Если шаблона нет, используем шаблон по умолчанию
        if (empty($template)) {
            $template = $this->getDefaultTemplate();
        }

        // Подготовка данных для замены
        $replacements = $this->prepareReplacements();

        // Замена переменных в шаблоне
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Подготовить массив замен для переменных
     */
    protected function prepareReplacements(): array
    {
        $settings = $this->settings;
        $spin = $this->spin;
        $prize = $spin->prize;
        $guest = $spin->guest;

        // Логотип
        $logoHtml = '';
        if ($settings->logo) {
            $logoUrl = $this->getFileUrl($settings->logo);
            $logoAlt = $settings->company_name ?: 'Логотип';
            $logoHtml = "<img src=\"{$logoUrl}\" alt=\"{$logoAlt}\" class=\"email-logo\">";
        }

        // Имя гостя
        $guestNameHtml = '';
        $guestName = '';
        if ($guest && $guest->name) {
            $guestNameHtml = "<div class=\"guest-name\">{$guest->name}</div>";
            $guestName = ' ' . $guest->name;
        }

        // Изображение приза
        $prizeImageHtml = '';
        if ($prize && $prize->email_image) {
            $prizeImageUrl = $this->getFileUrl($prize->email_image);
            $prizeImageAlt = $prize->name ?? '';
            $prizeImageHtml = "<img src=\"{$prizeImageUrl}\" alt=\"{$prizeImageAlt}\" class=\"prize-image\">";
        }

        // Описание приза
        $prizeDescriptionHtml = '';
        if ($prize && $prize->description) {
            $prizeDescriptionHtml = "<div class=\"prize-description\">{$prize->description}</div>";
        }

        // Текст для победителя
        $prizeTextForWinnerHtml = '';
        if ($prize && $prize->text_for_winner) {
            $prizeTextForWinnerHtml = "<div class=\"prize-description\"><strong>Сообщение:</strong> {$prize->text_for_winner}</div>";
        }

        // Значение приза (основное отображение)
        $codeHtml = '';
        if ($prize && $prize->value) {
            $codeHtml = "<div class=\"code-section\">
                <div class=\"code-label\">Идентификационный номер</div>
                <div class=\"code-value\">{$prize->value}</div>
            </div>";
        }

        // Примечание с кодом выигрыша
        $codeNoteHtml = '';
        if ($spin->code) {
            $codeNoteHtml = "<div class=\"code-note\">Примечание: Код выигрыша {$spin->code}</div>";
        }

        return [
            '{logo_html}' => $logoHtml,
            '{logo_url}' => $settings->logo ? $this->getFileUrl($settings->logo) : '',
            '{company_name}' => $settings->company_name ?: 'Колесо фортуны',
            '{guest_name_html}' => $guestNameHtml,
            '{guest_name}' => $guestName,
            '{guest_email}' => ($guest && $guest->email) ? $guest->email : '',
            '{guest_phone}' => ($guest && $guest->phone) ? $guest->phone : '',
            '{prize_name}' => ($prize && $prize->name) ? $prize->name : '',
            '{prize_description_html}' => $prizeDescriptionHtml,
            '{prize_description}' => ($prize && $prize->description) ? $prize->description : '',
            '{prize_text_for_winner_html}' => $prizeTextForWinnerHtml,
            '{prize_text_for_winner}' => ($prize && $prize->text_for_winner) ? $prize->text_for_winner : '',
            '{prize_type}' => ($prize && $prize->type) ? $prize->type : '',
            '{prize_value}' => ($prize && $prize->value) ? $prize->value : '',
            '{prize_email_image_html}' => $prizeImageHtml,
            '{prize_email_image_url}' => ($prize && $prize->email_image) ? $this->getFileUrl($prize->email_image) : '',
            '{code_html}' => $codeHtml,
            '{code_note_html}' => $codeNoteHtml,
            '{code}' => $spin->code ?: 'не указан',
        ];
    }

    /**
     * Получить URL файла из storage
     */
    protected function getFileUrl(string $path): string
    {
        // Если это полный URL, возвращаем как есть
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Если путь начинается с /, это абсолютный путь
        if (str_starts_with($path, '/')) {
            return url($path);
        }

        // Проверяем, существует ли файл в public storage
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // По умолчанию используем asset для storage
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Получить шаблон по умолчанию
     */
    protected function getDefaultTemplate(): string
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
                <div class="prize-title">🏆 Ваш приз: {prize_name}</div>
                {prize_email_image_html}
                {prize_description_html}
                {prize_text_for_winner_html}
            </div>

            {code_html}

            <div class="divider"></div>

            <div class="content-text">
                <p>Уважаемый{guest_name}!</p>
                <p>Поздравляем вас с выигрышем приза <strong>{prize_name}</strong>!</p>
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

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Перезагружаем настройки, чтобы получить актуальные данные
        $settings = Setting::getInstance();
        $companyName = $settings->company_name ?: 'Колесо фортуны';
        $fromAddress = config('mail.from.address', 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, $companyName),
            subject: "Поздравляем! Вы выиграли приз - {$companyName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.prize-win-simple',
            with: [
                'html' => $this->html,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
