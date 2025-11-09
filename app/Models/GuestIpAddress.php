<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestIpAddress extends Model
{
    protected $fillable = [
        'guest_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Гость, которому принадлежит IP-адрес
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
