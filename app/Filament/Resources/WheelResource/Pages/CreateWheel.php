<?php

namespace App\Filament\Resources\WheelResource\Pages;

use App\Filament\Resources\WheelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWheel extends CreateRecord
{
    protected static string $resource = WheelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Заполняем settings значениями по умолчанию, если они не заполнены
        if (empty($data['settings']) || !is_array($data['settings'])) {
            $data['settings'] = [];
        }

        $defaultSettings = [
            'loading_text' => 'Загрузка...',
            'spin_button_text' => 'Крутить колесо!',
            'spin_button_blocked_text' => 'Вы уже выиграли сегодня. Попробуйте завтра!',
            'won_prize_label' => 'Выиграно сегодня:',
            'win_notification_title' => 'Ваш подарок',
            'win_notification_win_text' => 'Скопируйте промокод или покажите QR-код на ресепшене',
            'copy_code_button_title' => 'Копировать код',
            'code_not_specified' => 'Код не указан',
            'download_pdf_text' => 'Скачать сертификат PDF',
            'form_description' => 'Для получения приза на почту заполните данные:',
            'form_name_placeholder' => 'Ваше имя',
            'form_email_placeholder' => 'Email',
            'form_phone_placeholder' => '+7 (XXX) XXX-XX-XX',
            'form_submit_text' => 'Отправить приз',
            'form_submit_loading' => 'Отправка...',
            'form_submit_success' => '✓ Приз отправлен!',
            'form_submit_error' => 'Приз уже получен',
            'form_success_message' => '✓ Данные сохранены! Приз будет отправлен на указанную почту.',
            'prize_image_alt' => 'Приз',
            'spins_info_format' => 'Вращений: {count} / {limit}',
            'spins_limit_format' => 'Лимит вращений: {limit}',
            'error_init_guest' => 'Ошибка инициализации: не удалось создать гостя',
            'error_init' => 'Ошибка инициализации:',
            'error_no_prizes' => 'Нет доступных призов',
            'error_load_data' => 'Ошибка загрузки данных:',
            'error_spin' => 'При розыгрыше произошла ошибка! Обратитесь в поддержку сервиса.',
            'error_general' => 'Ошибка:',
            'error_send' => 'Ошибка при отправке',
            'error_copy_code' => 'Не удалось скопировать код. Пожалуйста, скопируйте вручную:',
            'wheel_default_name' => 'Колесо Фортуны',
        ];

        $data['settings'] = array_merge($defaultSettings, $data['settings']);

        // Заполняем style_settings значениями по умолчанию, если они не заполнены
        $styleSettings = $data['style_settings'] ?? null;

        // Если пришла строка JSON, декодируем её
        if (is_string($styleSettings)) {
            $decoded = json_decode($styleSettings, true);
            $styleSettings = ($decoded !== null && json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
        }

        if (empty($styleSettings) || !is_array($styleSettings)) {
            $styleSettings = [];
        }

        $defaultStyleSettings = \App\Models\Wheel::getDefaultStyleSettings();

        // Объединяем дефолтные значения с переданными
        $result = $defaultStyleSettings;
        foreach ($styleSettings as $key => $value) {
            if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                $result[$key] = array_merge($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        $data['style_settings'] = $result;

        return $data;
    }
}



