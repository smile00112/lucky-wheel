<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prize extends Model
{
    protected $fillable = [
        'wheel_id',
        'name',
        'mobile_name',
        'sector_view',
        'email_name',
        'email_text_after_congratulation',
        'email_coupon_after_code_text',
        'description',
        'text_for_winner',
        'type',
        'value',
        'probability',
        'image',
        'email_image',
        'color',
        'use_gradient',
        'gradient_start',
        'gradient_end',
        'text_color',
        'font_size',
        'is_active',
        'sort',
        'quantity_limit',
        'quantity_day_limit',
        'quantity_guest_limit',
        'quantity_used',
    ];

    protected $attributes = [
        'text_color' => '#ffffff',
        'sector_view' => 'text_with_image',
    ];

    protected $casts = [
        'probability' => 'decimal:2',
        'is_active' => 'boolean',
        'use_gradient' => 'boolean',
        'sort' => 'integer',
        'quantity_limit' => 'integer',
        'quantity_day_limit' => 'integer',
        'quantity_guest_limit' => 'integer',
        'quantity_used' => 'integer',
        'font_size' => 'integer',
    ];

    /**
     * Колесо, к которому относится приз
     */
    public function wheel(): BelongsTo
    {
        return $this->belongsTo(Wheel::class);
    }

    /**
     * Вращения, где выигран этот приз
     */
    public function spins(): HasMany
    {
        return $this->hasMany(Spin::class);
    }

    /**
     * Проверка доступности приза (не превышен ли лимит)
     */
    public function isAvailable(): bool
    {
        if ($this->quantity_limit === null) {
            return true;
        }

        return $this->quantity_used < $this->quantity_limit;
    }

    /**
     * Увеличить счетчик использованных призов
     */
    public function incrementUsed(): void
    {
        $this->increment('quantity_used');
    }

    /**
     * Проверка доступности приза для гостя (не превышен ли лимит гостя)
     */
    public function isAvailableForGuest(int $guestId): bool
    {
        if ($this->quantity_guest_limit === null) {
            return true;
        }

        $guestWinsCount = $this->spins()
            ->where('guest_id', $guestId)
            ->count();

        return $guestWinsCount < $this->quantity_guest_limit;
    }

    /**
     * Проверка дневного лимита приза
     */
    public function isAvailableToday(): bool
    {
        if ($this->quantity_day_limit === null) {
            return true;
        }

        $todayWins = $this->spins()
            ->whereDate('created_at', today())
            ->count();

        return $todayWins < $this->quantity_day_limit;
    }

    /**
     * Полная проверка доступности приза (все лимиты)
     */
    public function isFullyAvailable(int $guestId = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if (!$this->isAvailableToday()) {
            return false;
        }

        if ($guestId && !$this->isAvailableForGuest($guestId)) {
            return false;
        }

        return true;
    }

    public function getUsedAttribute(): int
    {
        return $this->spins()->where('status', 'claimed')->count();
    }
    public function getWinsAttribute(): int
    {
        return $this->spins()->count();
    }

    /**
     * Получить название приза без разделителя
     * Удаляет все после разделителей: |, - , —, | , | , <br>, <br/>, <br />
     */
    public function getNameWithoutSeparator(): string
    {
        if (!$this->name) {
            return '';
        }

        // Сначала обрабатываем HTML теги <br>
        $name = preg_replace('/<br\s*\/?>/i', '|', $this->name);

        $separators = ['|', ' - ', ' — ', ' | ', '| ', ' |', '  '];

        return str_replace($separators, ' ', $name);
//        foreach ($separators as $separator) {
//            $pos = strpos($name, $separator);
//            if ($pos !== false) {
//                return trim(substr($name, 0, $pos));
//            }
//        }

        return $this->name;
    }
}
