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
    public $emailSettings;
    public $html;

    /**
     * Create a new message instance.
     */
    public function __construct(Spin $spin)
    {
        // Загружаем связи для использования в шаблоне
        $this->spin = $spin->load(['prize', 'guest', 'wheel']);
        $this->emailSettings = $this->resolveEmailSettings();
        $this->html = $this->buildEmailHtml();
    }

    /**
     * Определить источник настроек email: колесо или глобальные настройки
     */
    protected function resolveEmailSettings()
    {
        $wheel = $this->spin->wheel;

        if ($wheel && $wheel->use_wheel_email_settings) {
            return $wheel;
        }

        return Setting::getInstance();
    }

    /**
     * Построить HTML письма из шаблона настроек
     */
    protected function buildEmailHtml(): string
    {
        $template = $this->emailSettings->email_template;

        // Если шаблона нет, используем шаблон по умолчанию
        if (empty($template)) {
            $template = \App\Support\DefaultTemplates::email();
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
        $settings = $this->emailSettings;
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
            $prizeImageAlt = $prize->getNameWithoutSeparator() ?? '';
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

        // Полное наименование приза
        $prizeFullName = ($prize && $prize->full_name) ? $prize->full_name : (($prize && $prize->name) ? $prize->getNameWithoutSeparator() : '');

        // Подготовка базовых замен для полей email приза
        $prizeEmailReplacements = [
            '{prize_name}' => ($prize && $prize->name) ? $prize->getNameWithoutSeparator() : '',
            '{prize_full_name}' => $prizeFullName,
            '{prize_description}' => ($prize && $prize->description) ? $prize->description : '',
            '{prize_type}' => ($prize && $prize->type) ? $prize->type : '',
            '{prize_value}' => ($prize && $prize->value) ? $prize->value : '',
            '{prize_text_for_winner}' => ($prize && $prize->text_for_winner) ? $prize->text_for_winner : '',
            '{guest_name}' => $guestName,
            '{guest_email}' => ($guest && $guest->email) ? $guest->email : '',
            '{guest_phone}' => ($guest && $guest->phone) ? $guest->phone : '',
            '{code}' => $spin->code ?: 'не указан',
            '{company_name}' => $settings->company_name ?: 'Колесо фортуны',
        ];

        // Название приза для email
        $prizeEmailName = ($prize && $prize->email_name) ? $prize->email_name : (($prize && $prize->full_name) ? $prize->full_name : (($prize && $prize->name) ? $prize->getNameWithoutSeparator() : ''));
        
        // Замена переменных в email_name
        if ($prize && $prize->email_name) {
            $prizeEmailName = $prize->replaceEmailVariables($prize->email_name, $prizeEmailReplacements);
        }
        
        $prizeEmailNameHtml = '';
        if ($prizeEmailName) {
            $prizeEmailNameHtml = "<div class=\"prize-email-name\">{$prizeEmailName}</div>";
        }

        // Текст после поздравления
        $prizeEmailTextAfterCongratulation = '';
        $prizeEmailTextAfterCongratulationHtml = '';
        if ($prize && $prize->email_text_after_congratulation) {
            $prizeEmailTextAfterCongratulation = $prize->replaceEmailVariables(
                $prize->email_text_after_congratulation,
                $prizeEmailReplacements
            );
            $prizeEmailTextAfterCongratulationHtml = "<div class=\"prize-email-text-after-congratulation\">{$prizeEmailTextAfterCongratulation}</div>";
        }

        // Текст после кода купона
        $prizeEmailCouponAfterCodeText = '';
        $prizeEmailCouponAfterCodeTextHtml = '';
        if ($prize && $prize->email_coupon_after_code_text) {
            $prizeEmailCouponAfterCodeText = $prize->replaceEmailVariables(
                $prize->email_coupon_after_code_text,
                $prizeEmailReplacements
            );
            $prizeEmailCouponAfterCodeTextHtml = "<div class=\"prize-email-coupon-after-code-text\">{$prizeEmailCouponAfterCodeText}</div>";
        }

        return [
            '{logo_html}' => $logoHtml,
            '{logo_url}' => $settings->logo ? $this->getFileUrl($settings->logo) : '',
            '{company_name}' => $settings->company_name ?: 'Колесо фортуны',
            '{guest_name_html}' => $guestNameHtml,
            '{guest_name}' => $guestName,
            '{guest_email}' => ($guest && $guest->email) ? $guest->email : '',
            '{guest_phone}' => ($guest && $guest->phone) ? $guest->phone : '',
            '{prize_name}' => ($prize && $prize->name) ? $prize->getNameWithoutSeparator() : '',
            '{prize_full_name}' => $prizeFullName,
            '{prize_email_name}' => $prizeEmailName,
            '{prize_email_name_html}' => $prizeEmailNameHtml,
            '{prize_description_html}' => $prizeDescriptionHtml,
            '{prize_description}' => ($prize && $prize->description) ? $prize->description : '',
            '{prize_text_for_winner_html}' => $prizeTextForWinnerHtml,
            '{prize_text_for_winner}' => ($prize && $prize->text_for_winner) ? $prize->text_for_winner : '',
            '{prize_email_text_after_congratulation}' => $prizeEmailTextAfterCongratulation,
            '{prize_email_text_after_congratulation_html}' => $prizeEmailTextAfterCongratulationHtml,
            '{prize_email_coupon_after_code_text}' => $prizeEmailCouponAfterCodeText,
            '{prize_email_coupon_after_code_text_html}' => $prizeEmailCouponAfterCodeTextHtml,
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
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $settings = $this->emailSettings ?? Setting::getInstance();
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
