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

class SpaPrizeWinElegantMail extends Mailable
{
    use Queueable, SerializesModels;

    public Spin $spin;
    public Setting $settings;
    public ?string $qrCodeDataUri = null;

    public function __construct(Spin $spin)
    {
        $this->spin = $spin->load(['prize', 'guest']);
        $this->settings = Setting::getInstance();
        $this->qrCodeDataUri = $this->generateQrCode();
    }

    protected function generateQrCode(): ?string
    {
        try {
            if (class_exists('\chillerlan\QRCode\QRCode')) {
                $qrData = $this->spin->code ?: ($this->spin->prize->value ?: 'PRIZE-' . $this->spin->id);
                
                $options = new \chillerlan\QRCode\QROptions([
                    'version' => 5,
                    'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
                    'scale' => 8,
                    'imageBase64' => false,
                ]);
                
                $qrcode = new \chillerlan\QRCode\QRCode($options);
                $qrImage = $qrcode->render($qrData);
                
                return 'data:image/png;base64,' . base64_encode($qrImage);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to generate QR code: ' . $e->getMessage());
        }

        try {
            $qrData = $this->spin->code ?: ($this->spin->prize->value ?: 'PRIZE-' . $this->spin->id);
            $encodedData = urlencode($qrData);
            return "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={$encodedData}";
        } catch (\Exception $e) {
            return null;
        }
    }

    public function envelope(): Envelope
    {
        $settings = Setting::getInstance();
        $companyName = $settings->company_name ?: 'Спа-комплекс';
        $fromAddress = config('mail.from.address', 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, $companyName),
            subject: "Поздравляем! Вы выиграли приз - {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.spa-prize-win-elegant',
            with: [
                'spin' => $this->spin,
                'settings' => $this->settings,
                'qrCodeDataUri' => $this->qrCodeDataUri,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

