<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wheel extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'technical_name',
        'description',
        'slug',
        'is_active',
        'force_data_collection',
        'settings',
        'style_settings',
        'spins_limit',
        'refresh_hour',
        'probability_type',
        'starts_at',
        'ends_at',
        'image',
        'company_name',
        'logo',
        'email_template',
        'pdf_template',
        'use_wheel_email_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'force_data_collection' => 'boolean',
        'settings' => 'array',
        'style_settings' => 'array',
        'spins_limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'use_wheel_email_settings' => 'boolean',
    ];

    /**
     * Владелец колеса
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Призы колеса
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class)->orderBy('sort');
    }

    /**
     * Активные призы колеса
     */
    public function activePrizes(): HasMany
    {
        return $this->hasMany(Prize::class)->where('is_active', true)->orderBy('sort');
    }

    /**
     * Вращения колеса
     */
    public function spins(): HasMany
    {
        return $this->hasMany(Spin::class);
    }

    /**
     * Выигрыши колеса
     */
    public function wins(): HasMany
    {
        return $this->hasMany(Spin::class)->whereNotNull('prize_id');
    }

    /**
     * Интеграция платформы
     */
    public function platformIntegration(): HasOne
    {
        return $this->hasOne(PlatformIntegration::class);
    }

    /**
     * Scope для фильтрации по ролям пользователя
     * Владелец видит все колеса, менеджер - только свои
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isOwner()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Получить дефолтные настройки стилей
     * Все стили должны соответствовать тем, что используются в generateStyleCss()
     */
    public static function getDefaultStyleSettings(): array
    {
        return [
            // .lucky-wheel-content
            'content' => [
                'font_family' => 'Arial, sans-serif',
                'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            ],
            // .lucky-wheel-container
            'container' => [
                'background' => '#ffffff',
                'border_radius' => '20px',
                'padding' => '30px 20px',
                'max_width' => '450px',
            ],
            // .lucky-wheel-container h1
            'title' => [
                'color' => '#333333',
                'font_size' => '1.8em',
                'margin_bottom' => '20px',
            ],
            // .lucky-wheel-container .description
            'description' => [
                'color' => '#666666',
                'font_size' => '14px',
                'margin_bottom' => '35px',
            ],
            // .pointer
            'pointer' => [
                'color' => '#ff4444',
            ],
            // .spin-button
            'spin_button' => [
                'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'color' => '#ffffff',
                'font_size' => '16px',
                'font_weight' => 'bold',
                'padding' => '15px 40px',
                'border_radius' => '50px',
                'max_width' => '300px',
            ],
            // .won-prize-block
            'won_prize_block' => [
                'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'color' => '#ffffff',
                'padding' => '10px 20px',
                'border_radius' => '10px',
            ],
            // .won-prize-label
            'won_prize_label' => [
                'font_size' => '11px',
                'opacity' => '0.9',
            ],
            // .won-prize-name
            'won_prize_name' => [
                'font_size' => '14px',
                'font_weight' => 'bold',
            ],
            // .win-notification
            'win_notification' => [
                'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'color' => '#ffffff',
                'padding' => '20px',
                'border_radius' => '15px 15px 0 0',
            ],
            // .win-notification h3
            'win_notification_title' => [
                'font_size' => '1.3em',
            ],
            // .win-notification-message
            'win_notification_message' => [
                'font_size' => '14px',
            ],
            // .win-notification-code input
            'win_notification_code_input' => [
                'background' => 'rgba(255, 255, 255, 0.9)',
                'color' => '#333333',
                'font_size' => '16px',
                'font_weight' => 'bold',
                'border_radius' => '6px',
                'padding' => '12px',
            ],
            // .win-notification-submit-btn
            'win_notification_submit_button' => [
                'background' => '#ffffff',
                'color' => '#667eea',
                'font_size' => '16px',
                'font_weight' => 'bold',
                'border_radius' => '8px',
                'padding' => '14px',
            ],
            // .spins-info
            'spins_info' => [
                'font_size' => '12px',
                'color' => '#999999',
            ],
            // .error
            'error' => [
                'background' => '#ffeeee',
                'border_color' => '#ffcccc',
                'color' => '#cc3333',
                'padding' => '15px',
                'border_radius' => '10px',
            ],
        ];
    }

    /**
     * Получить настройки стилей с дефолтными значениями
     */
    public function getStyleSettingsWithDefaults(): array
    {
        $defaults = static::getDefaultStyleSettings();
        $settings = $this->style_settings ?? [];

        // Рекурсивно объединяем настройки с дефолтными значениями
        return $this->arrayMergeRecursive($defaults, $settings);
    }

    /**
     * Рекурсивное объединение массивов
     */
    private function arrayMergeRecursive(array $defaults, array $settings): array
    {
        $result = $defaults;
        foreach ($settings as $key => $value) {
            if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                $result[$key] = $this->arrayMergeRecursive($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Генерировать CSS из настроек стилей
     */
    public function generateStyleCss(): string
    {
        $styles = $this->getStyleSettingsWithDefaults();
        if (empty($styles)) {
            return '';
        }
        
        $css = '';

        // Content
        if (isset($styles['content'])) {
            $content = $styles['content'];
            $css .= ".lucky-wheel-content {\n";
            if (isset($content['font_family'])) {
                $css .= "    font-family: {$content['font_family']};\n";
            }
            if (isset($content['background'])) {
                $css .= "    background: {$content['background']};\n";
            }
            $css .= "}\n\n";
        }

        // Container
        if (isset($styles['container'])) {
            $container = $styles['container'];
            $css .= ".lucky-wheel-container {\n";
            if (isset($container['background'])) {
                $css .= "    background: {$container['background']};\n";
            }
            if (isset($container['border_radius'])) {
                $css .= "    border-radius: {$container['border_radius']};\n";
            }
            if (isset($container['padding'])) {
                $css .= "    padding: {$container['padding']};\n";
            }
            if (isset($container['max_width'])) {
                $css .= "    max-width: {$container['max_width']};\n";
            }
            $css .= "}\n\n";
        }

        // Title
        if (isset($styles['title'])) {
            $title = $styles['title'];
            $css .= ".lucky-wheel-container h1 {\n";
            if (isset($title['color'])) {
                $css .= "    color: {$title['color']};\n";
            }
            if (isset($title['font_size'])) {
                $css .= "    font-size: {$title['font_size']};\n";
            }
            if (isset($title['margin_bottom'])) {
                $css .= "    margin-bottom: {$title['margin_bottom']};\n";
            }
            $css .= "}\n\n";
        }

        // Description
        if (isset($styles['description'])) {
            $description = $styles['description'];
            $css .= ".lucky-wheel-container .description {\n";
            if (isset($description['color'])) {
                $css .= "    color: {$description['color']};\n";
            }
            if (isset($description['font_size'])) {
                $css .= "    font-size: {$description['font_size']};\n";
            }
            if (isset($description['margin_bottom'])) {
                $css .= "    margin-bottom: {$description['margin_bottom']};\n";
            }
            $css .= "}\n\n";
        }

        // Pointer
        if (isset($styles['pointer'])) {
            $pointer = $styles['pointer'];
            $css .= ".pointer {\n";
            if (isset($pointer['color'])) {
                $css .= "    border-top-color: {$pointer['color']};\n";
            }
            $css .= "}\n\n";
        }

        // Spin Button
        if (isset($styles['spin_button'])) {
            $button = $styles['spin_button'];
            $css .= ".spin-button {\n";
            if (isset($button['background'])) {
                $css .= "    background: {$button['background']};\n";
            }
            if (isset($button['color'])) {
                $css .= "    color: {$button['color']};\n";
            }
            if (isset($button['font_size'])) {
                $css .= "    font-size: {$button['font_size']};\n";
            }
            if (isset($button['font_weight'])) {
                $css .= "    font-weight: {$button['font_weight']};\n";
            }
            if (isset($button['padding'])) {
                $css .= "    padding: {$button['padding']};\n";
            }
            if (isset($button['border_radius'])) {
                $css .= "    border-radius: {$button['border_radius']};\n";
            }
            if (isset($button['max_width'])) {
                $css .= "    max-width: {$button['max_width']};\n";
            }
            $css .= "}\n\n";
        }

        // Won Prize Block
        if (isset($styles['won_prize_block'])) {
            $block = $styles['won_prize_block'];
            $css .= ".won-prize-block {\n";
            if (isset($block['background'])) {
                $css .= "    background: {$block['background']};\n";
            }
            if (isset($block['color'])) {
                $css .= "    color: {$block['color']};\n";
            }
            if (isset($block['padding'])) {
                $css .= "    padding: {$block['padding']};\n";
            }
            if (isset($block['border_radius'])) {
                $css .= "    border-radius: {$block['border_radius']};\n";
            }
            $css .= "}\n\n";
        }

        // Won Prize Label
        if (isset($styles['won_prize_label'])) {
            $label = $styles['won_prize_label'];
            $css .= ".won-prize-label {\n";
            if (isset($label['font_size'])) {
                $css .= "    font-size: {$label['font_size']};\n";
            }
            if (isset($label['opacity'])) {
                $css .= "    opacity: {$label['opacity']};\n";
            }
            $css .= "}\n\n";
        }

        // Won Prize Name
        if (isset($styles['won_prize_name'])) {
            $name = $styles['won_prize_name'];
            $css .= ".won-prize-name {\n";
            if (isset($name['font_size'])) {
                $css .= "    font-size: {$name['font_size']};\n";
            }
            if (isset($name['font_weight'])) {
                $css .= "    font-weight: {$name['font_weight']};\n";
            }
            $css .= "}\n\n";
        }

        // Win Notification
        if (isset($styles['win_notification'])) {
            $notification = $styles['win_notification'];
            $css .= ".win-notification {\n";
            if (isset($notification['background'])) {
                $css .= "    background: {$notification['background']};\n";
            }
            if (isset($notification['color'])) {
                $css .= "    color: {$notification['color']};\n";
            }
            if (isset($notification['padding'])) {
                $css .= "    padding: {$notification['padding']};\n";
            }
            if (isset($notification['border_radius'])) {
                $css .= "    border-radius: {$notification['border_radius']};\n";
            }
            $css .= "}\n\n";
        }

        // Win Notification Title
        if (isset($styles['win_notification_title'])) {
            $title = $styles['win_notification_title'];
            $css .= ".win-notification h3 {\n";
            if (isset($title['font_size'])) {
                $css .= "    font-size: {$title['font_size']};\n";
            }
            $css .= "}\n\n";
        }

        // Win Notification Message
        if (isset($styles['win_notification_message'])) {
            $message = $styles['win_notification_message'];
            $css .= ".win-notification-message {\n";
            if (isset($message['font_size'])) {
                $css .= "    font-size: {$message['font_size']};\n";
            }
            $css .= "}\n\n";
        }

        // Win Notification Code Input
        if (isset($styles['win_notification_code_input'])) {
            $input = $styles['win_notification_code_input'];
            $css .= ".win-notification-code input {\n";
            if (isset($input['background'])) {
                $css .= "    background: {$input['background']};\n";
            }
            if (isset($input['color'])) {
                $css .= "    color: {$input['color']};\n";
            }
            if (isset($input['font_size'])) {
                $css .= "    font-size: {$input['font_size']};\n";
            }
            if (isset($input['font_weight'])) {
                $css .= "    font-weight: {$input['font_weight']};\n";
            }
            if (isset($input['border_radius'])) {
                $css .= "    border-radius: {$input['border_radius']};\n";
            }
            if (isset($input['padding'])) {
                $css .= "    padding: {$input['padding']};\n";
            }
            $css .= "}\n\n";
        }

        // Win Notification Submit Button
        if (isset($styles['win_notification_submit_button'])) {
            $button = $styles['win_notification_submit_button'];
            $css .= ".win-notification-submit-btn {\n";
            if (isset($button['background'])) {
                $css .= "    background: {$button['background']};\n";
            }
            if (isset($button['color'])) {
                $css .= "    color: {$button['color']};\n";
            }
            if (isset($button['font_size'])) {
                $css .= "    font-size: {$button['font_size']};\n";
            }
            if (isset($button['font_weight'])) {
                $css .= "    font-weight: {$button['font_weight']};\n";
            }
            if (isset($button['border_radius'])) {
                $css .= "    border-radius: {$button['border_radius']};\n";
            }
            if (isset($button['padding'])) {
                $css .= "    padding: {$button['padding']};\n";
            }
            $css .= "}\n\n";
        }

        // Spins Info
        if (isset($styles['spins_info'])) {
            $info = $styles['spins_info'];
            $css .= ".spins-info {\n";
            if (isset($info['font_size'])) {
                $css .= "    font-size: {$info['font_size']};\n";
            }
            if (isset($info['color'])) {
                $css .= "    color: {$info['color']};\n";
            }
            $css .= "}\n\n";
        }

        // Error
        if (isset($styles['error'])) {
            $error = $styles['error'];
            $css .= ".error {\n";
            if (isset($error['background'])) {
                $css .= "    background: {$error['background']};\n";
            }
            if (isset($error['border_color'])) {
                $css .= "    border-color: {$error['border_color']};\n";
            }
            if (isset($error['color'])) {
                $css .= "    color: {$error['color']};\n";
            }
            if (isset($error['padding'])) {
                $css .= "    padding: {$error['padding']};\n";
            }
            if (isset($error['border_radius'])) {
                $css .= "    border-radius: {$error['border_radius']};\n";
            }
            $css .= "}\n\n";
        }

        return $css;
    }

}
