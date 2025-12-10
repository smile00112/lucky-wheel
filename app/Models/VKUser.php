<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VKUser extends Model
{
    protected $fillable = [
        'guest_id',
        'vk_id',
        'first_name',
        'last_name',
        'phone',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public static function findByVkId(int $vkId): ?self
    {
        return self::where('vk_id', $vkId)->first();
    }
}

