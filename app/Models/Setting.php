<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'logo',
        'email_template',
        'pdf_template',
        'settings',
        'notification_email',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Получить единственную запись настроек или создать новую
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}

