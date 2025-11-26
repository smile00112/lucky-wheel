import { ru } from './translations/ru.js';

export class Config {
    constructor() {
        this.apiUrl = window.API_URL || '';
        this.appUrl = window.APP_URL || '';
        this.wheelSlug = window.WHEEL_SLUG || '';
        this.guestId = this.getGuestIdFromUrl();
        
        // Тексты по умолчанию из языкового файла
        this.defaultTexts = {
            ...ru,
            loading_text: 'Загрузка...',
            spin_button_text: 'Крутить колесо!',
            spin_button_blocked_text: 'Вы уже выиграли сегодня. Попробуйте завтра!',
            won_prize_label: 'Выиграно сегодня:',
            win_notification_title: '🎉 Поздравляем с выигрышем!',
            win_notification_win_text: 'Вы выиграли:',
            copy_code_button_title: 'Копировать код',
            code_not_specified: 'Код не указан',
            download_pdf_text: 'Скачать сертификат PDF',
            form_description: 'Для получения приза на почту заполните данные:',
            form_name_placeholder: 'Ваше имя',
            form_email_placeholder: 'Email',
            form_phone_placeholder: '+7 (XXX) XXX-XX-XX',
            form_submit_text: 'Отправить приз',
            form_submit_loading: 'Отправка...',
            form_submit_success: '✓ Приз отправлен!',
            form_submit_error: 'Приз уже получен',
            form_success_message: '✓ Данные сохранены! Приз будет отправлен на указанную почту.',
            prize_image_alt: 'Приз',
            spins_info_format: 'Вращений: {count} / {limit}',
            spins_limit_format: 'Лимит вращений: {limit}',
            error_init_guest: 'Ошибка инициализации: не удалось создать гостя',
            error_init: 'Ошибка инициализации:',
            error_no_prizes: 'Нет доступных призов',
            error_load_data: 'Ошибка загрузки данных:',
            error_spin: 'При розыгрыше произошла ошибка! Обратитесь в поддержку сервиса.',
            error_general: 'Ошибка:',
            error_send: 'Ошибка при отправке',
            error_copy_code: 'Не удалось скопировать код. Пожалуйста, скопируйте вручную:',
            wheel_default_name: 'Колесо Фортуны',
        };
        
        // Получаем тексты из window или используем значения по умолчанию
        this.texts = window.WHEEL_TEXTS ? { ...this.defaultTexts, ...window.WHEEL_TEXTS } : this.defaultTexts;
    }

    getText(key) {
        return this.texts[key] || this.defaultTexts[key] || '';
    }

    updateTexts(texts) {
        this.texts = { ...this.defaultTexts, ...texts };
    }

    getGuestIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const guestId = params.get('guest_id');
        if (guestId && !isNaN(guestId) && parseInt(guestId) > 0) {
            return parseInt(guestId);
        }
        return null;
    }

    getStorageKey(key) {
        return `lucky_wheel_${key}_${this.wheelSlug}`;
    }
}

