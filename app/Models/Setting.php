<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'logo',
        'email_template',
        'pdf_template',
        'settings',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Пользователь-владелец настроек
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить настройки для пользователя (owner)
     */
    public static function getForUser(User $user): self
    {
        $owner = $user->getOwner();
        return static::firstOrCreate(['user_id' => $owner->id]);
    }

    /**
     * Получить массив SMTP настроек для конфигурации
     * Если настройки в БД не заполнены, используются значения из .env
     */
    public function getSmtpConfig(): array
    {
        return [
            'mailer' => !empty($this->mail_mailer) ? $this->mail_mailer : config('mail.default'),
            'host' => !empty($this->mail_host) ? $this->mail_host : config('mail.mailers.smtp.host'),
            'port' => !empty($this->mail_port) ? $this->mail_port : config('mail.mailers.smtp.port'),
            'username' => !empty($this->mail_username) ? $this->mail_username : config('mail.mailers.smtp.username'),
            'password' => !empty($this->mail_password) ? $this->mail_password : config('mail.mailers.smtp.password'),
            'encryption' => !empty($this->mail_encryption) ? $this->mail_encryption : config('mail.mailers.smtp.encryption'),
            'from' => [
                'address' => !empty($this->mail_from_address) ? $this->mail_from_address : config('mail.from.address'),
                'name' => !empty($this->mail_from_name) ? $this->mail_from_name : config('mail.from.name'),
            ],
        ];
    }

    /**
     * @deprecated Используйте getForUser() вместо этого метода
     */
    public static function getInstance(): self
    {
        $user = auth()->user();
        if ($user) {
            return static::getForUser($user);
        }
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Scope для фильтрации по компании пользователя
     */
    public function scopeForCompany(Builder $query, ?int $companyId = null): Builder
    {
        if (!$companyId) {
            $user = auth()->user();
            if (!$user || $user->isAdmin()) {
                return $query;
            }
            $companyId = $user->company_id;
        }

        if (!$companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('user', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }
}

