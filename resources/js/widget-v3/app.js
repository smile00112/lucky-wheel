import { Config } from './config.js';
import { ApiService } from './api.js';
import { StateManager } from './state.js';
import { WheelRenderer } from './wheel-renderer-v2.js';
import { WheelAnimation } from './wheel-animation.js';
import { ImageLoader } from './image-loader.js';
import { NotificationManager } from './notification.js';
import { FormHandler } from './form-handler.js';
import { WheelController } from './wheel-controller.js';
import { Utils } from './utils.js';

class LuckyWheelApp {
    constructor() {
        this.config = new Config();
        this.state = new StateManager(this.config);
        this.api = new ApiService(this.config);
        this.renderer = new WheelRenderer(this.state);
        this.animation = new WheelAnimation(this.state, this.renderer);
        this.imageLoader = new ImageLoader(this.state, this.config);
        this.notification = new NotificationManager(this.state, this.config, this.api);
        this.formHandler = new FormHandler(this.state, this.config, this.api, this.notification);
        this.controller = new WheelController(
            this.state,
            this.config,
            this.api,
            this.renderer,
            this.animation,
            this.imageLoader,
            this.notification
        );
    }

    async init() {
        console.log('[LuckyWheel] App.init() started');
        
        if (!this.config.guestId) {
            console.log('[LuckyWheel] No guestId, creating/getting guest...');
            try {
                this.config.guestId = await this.api.createOrGetGuest();
                console.log('[LuckyWheel] Guest ID obtained:', this.config.guestId);
                if (!this.config.guestId) {
                    console.error('[LuckyWheel] Failed to get guest ID');
                    this.controller.showError(this.config.getText('error_init_guest'));
                    return;
                }
            } catch (error) {
                console.error('[LuckyWheel] Error creating/getting guest:', error);
                this.controller.showError(this.config.getText('error_init') + ' ' + error.message);
                return;
            }
        } else {
            console.log('[LuckyWheel] Using existing guestId:', this.config.guestId);
        }

        const phoneInput = document.getElementById('winNotificationPhone');
        if (phoneInput) {
            Utils.applyPhoneMask(phoneInput);
        }

        this.fillGuestForm();
        this.setupFieldValidationListeners();

        console.log('[LuckyWheel] Checking today win...');
        let hasWin = false;
        try {
            await this.checkTodayWin();
            console.log('[LuckyWheel] checkTodayWin() completed');
            const winData = this.state.getWinData();
            hasWin = winData && this.state.isTodayWin(winData);
        } catch (error) {
            console.error('[LuckyWheel] Error in checkTodayWin():', error);
        }

        console.log('[LuckyWheel] Initializing controller...');
        try {
            await this.controller.init();
            console.log('[LuckyWheel] controller.init() completed');
        } catch (error) {
            console.error('[LuckyWheel] Error in controller.init():', error);
            this.controller.showWheelContent();
        }

        // Показываем форму или блок информации о колесе при загрузке, если нет выигрыша
        if (!hasWin) {
            const wheelData = this.state.get('wheelData');
            const forceDataCollection = wheelData?.force_data_collection ?? true;
            
            if (forceDataCollection) {
                this.showInitialForm();
            } else {
                this.showWheelInfoBlock();
            }
        }

        setInterval(() => this.checkTodayWin(), 60000);

        this.setupEventListeners();
        console.log('[LuckyWheel] App.init() completed');
    }

    async checkTodayWin() {
        console.log('[LuckyWheel] checkTodayWin() started');
        const winData = this.state.getWinData();
        console.log('[LuckyWheel] winData from storage:', winData);

        if (winData && this.state.isTodayWin(winData)) {
            console.log('[LuckyWheel] Today win found in storage');
            this.controller.applyWonPrize(winData);
            return;
        }

        if (winData && !this.state.isTodayWin(winData)) {
            console.log('[LuckyWheel] Old win data found, clearing...');
            this.state.clearWin();
            this.controller.unblockSpinning();
            this.notification.hide();
            this.notification.hideWonPrizeBlock();
            this.state.set('currentRotation', 0);
            const canvas = this.state.get('canvas');
            const ctx = this.state.get('ctx');
            if (canvas && ctx) {
                this.renderer.draw(0);
            }
            return;
        }

        try {
            console.log('[LuckyWheel] Checking today win via API...');
            const data = await this.api.checkTodayWin(this.config.guestId);
            console.log('[LuckyWheel] API response:', data);
            if (data.has_win && data.prize) {
                console.log('[LuckyWheel] Today win found via API');
                this.state.saveWin(data.prize, data.code, data.guest_has_data, data.spin_id);
                this.controller.applyWonPrize({
                    prize: data.prize,
                    code: data.code,
                    guest_has_data: data.guest_has_data,
                    spin_id: data.spin_id,
                });
            } else {
                console.log('[LuckyWheel] No win today');
                this.controller.unblockSpinning();
                this.notification.hideWonPrizeBlock();
                this.state.set('currentRotation', 0);
                const canvas = this.state.get('canvas');
                const ctx = this.state.get('ctx');
                if (canvas && ctx) {
                    this.renderer.draw(0);
                }
            }
        } catch (error) {
            console.error('[LuckyWheel] Error checking today win:', error);
        }
    }

    setupEventListeners() {
        // Обработчики для кнопок копирования
        const copyButtons = document.querySelectorAll('#winNotificationCodeContainer button, #winNotificationPromoCodeContainer button');
        copyButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const container = e.target.closest('#winNotificationCodeContainer, #winNotificationPromoCodeContainer');
                if (container) {
                    const input = container.querySelector('input');
                    if (input) {
                        this.formHandler.copyCode(e, input);
                    }
                }
            });
        });

        const copyButton = document.querySelector('[onclick*="copyPrizeCode"]');
        if (copyButton) {
            copyButton.removeAttribute('onclick');
            copyButton.addEventListener('click', (e) => this.formHandler.copyCode(e));
        }

        const form = document.getElementById('winNotificationForm');
        if (form) {
            form.removeAttribute('onsubmit');
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.formHandler.submit(e);
            });
        }

        const spinButton = document.getElementById('spinButton');
        if (spinButton) {
            spinButton.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                await this.handleSpinButtonClick();
            });
        }

        const wheelInfoSpinButton = document.getElementById('wheelInfoSpinButton');
        if (wheelInfoSpinButton) {
            wheelInfoSpinButton.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                await this.handleWheelInfoSpinButtonClick();
            });
        }

        const submitBtn2 = document.getElementById('winNotificationSubmitBtn2');
        if (submitBtn2) {
            submitBtn2.removeAttribute('onclick');
            submitBtn2.addEventListener('click', (e) => this.formHandler.submit(e));
        }

        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'lucky-wheel') {
                if (event.data.action === 'spin') {
                    this.controller.spin();
                }
            }
        });

        window.hideWinNotification = () => this.notification.hide();
        window.submitPrizeForm = (e) => this.formHandler.submit(e);
        window.copyPrizeCode = (e) => this.formHandler.copyCode(e);
    }

    fillGuestForm() {
        const guestData = window.GUEST_DATA;
        if (!guestData) {
            return;
        }

        console.log('[LuckyWheel] Filling form with guest data:', guestData);

        const nameInput = document.getElementById('winNotificationName');
        const emailInput = document.getElementById('winNotificationEmail');
        const phoneInput = document.getElementById('winNotificationPhone');

        if (nameInput && guestData.name) {
            nameInput.value = guestData.name;
        }
        if (emailInput && guestData.email) {
            emailInput.value = guestData.email;
        }
        if (phoneInput && guestData.phone) {
            phoneInput.value = guestData.phone;
            if (Utils.applyPhoneMask) {
                Utils.applyPhoneMask(phoneInput);
            }
        }
    }

    showInitialForm() {
        const notification = document.getElementById('winNotification');
        const formContainer = document.getElementById('winNotificationFormContainer');
        const winningFormContainer = document.getElementById('winningFormContainer');
        const wheelInfoBlock = document.getElementById('wheelInfoBlock');
        const sendContainer = document.getElementById('winNotificationSendContainer');
        const spinButton = document.getElementById('spinButton');
        const submitBtn = document.getElementById('winNotificationSubmitBtn');
        const formHeader = document.getElementById('winNotificationFormHeader');
        const formInitial = document.getElementById('winNotificationFormInitial');

        if (!notification || !formContainer) return;

        // Скрываем секцию с результатами и блок информации о колесе
        if (winningFormContainer) {
            winningFormContainer.style.display = 'none';
        }
        if (wheelInfoBlock) {
            wheelInfoBlock.style.display = 'none';
        }

        // Скрываем блок с заголовком выигрыша, показываем начальный блок
        if (formHeader) {
            formHeader.style.display = 'none';
        }
        if (formInitial) {
            formInitial.style.display = 'block';
        }

        // Показываем форму
        notification.style.display = 'block';
        notification.classList.add('show');
        formContainer.style.display = 'block';
        if (sendContainer) sendContainer.style.display = 'none';
        if (spinButton) {
            spinButton.style.display = 'block';
            spinButton.disabled = false;
        }
        if (submitBtn) {
            submitBtn.style.display = 'none';
            submitBtn.disabled = false;
        }
    }

    showWheelInfoBlock() {
        const notification = document.getElementById('winNotification');
        const formContainer = document.getElementById('winNotificationFormContainer');
        const winningFormContainer = document.getElementById('winningFormContainer');
        const wheelInfoBlock = document.getElementById('wheelInfoBlock');
        const sendContainer = document.getElementById('winNotificationSendContainer');

        if (!notification || !wheelInfoBlock) return;

        // Скрываем форму и секцию с результатами
        if (formContainer) {
            formContainer.style.display = 'none';
        }
        if (winningFormContainer) {
            winningFormContainer.style.display = 'none';
        }
        if (sendContainer) {
            sendContainer.style.display = 'none';
        }

        // Обновляем данные в блоке информации о колесе
        const wheelData = this.state.get('wheelData');
        if (wheelData) {
            const nameElement = document.getElementById('wheelInfoName');
            const descElement = document.getElementById('wheelInfoDescription');
            const imageElement = document.getElementById('wheelInfoImage');
            
            if (nameElement && wheelData.name) {
                nameElement.textContent = wheelData.name;
            }
            if (descElement && wheelData.description) {
                descElement.textContent = wheelData.description;
                descElement.style.display = 'block';
            } else if (descElement) {
                descElement.style.display = 'none';
            }
            if (imageElement && wheelData.image) {
                imageElement.src = wheelData.image;
                imageElement.parentElement.style.display = 'block';
            } else if (imageElement) {
                imageElement.parentElement.style.display = 'none';
            }
        }

        // Показываем блок информации о колесе
        notification.style.display = 'block';
        notification.classList.add('show');
        wheelInfoBlock.style.display = 'block';
    }

    validateFormFields() {
        const nameInput = document.getElementById('winNotificationName');
        const emailInput = document.getElementById('winNotificationEmail');
        const agreementCheckbox = document.getElementById('winNotificationAgreement');

        let isValid = true;

        const highlightField = (input) => {
            if (input) {
                input.style.backgroundColor = '#FFF0F0';
                input.style.borderColor = '#ef4444';
            }
        };

        const clearHighlight = (input) => {
            if (input) {
                input.style.backgroundColor = '';
                input.style.borderColor = '';
            }
        };

        if (!nameInput || !emailInput) {
            console.error('[LuckyWheel] Form inputs not found');
            return false;
        }

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();

        clearHighlight(nameInput);
        clearHighlight(emailInput);

        if (!name) {
            highlightField(nameInput);
            isValid = false;
        }

        if (!email) {
            highlightField(emailInput);
            isValid = false;
        }

        if (agreementCheckbox && !agreementCheckbox.checked) {
            isValid = false;
        }

        return isValid;
    }

    setupFieldValidationListeners() {
        const nameInput = document.getElementById('winNotificationName');
        const emailInput = document.getElementById('winNotificationEmail');

        const clearHighlight = (input) => {
            if (input) {
                input.style.backgroundColor = '';
                input.style.borderColor = '';
            }
        };

        if (nameInput) {
            nameInput.addEventListener('input', () => clearHighlight(nameInput));
        }
        if (emailInput) {
            emailInput.addEventListener('input', () => clearHighlight(emailInput));
        }
    }

    async handleSpinButtonClick() {
        const spinButton = document.getElementById('spinButton');
        if (!spinButton || spinButton.disabled) return;

        if (!this.validateFormFields()) {
            return;
        }

        const agreementCheckbox = document.getElementById('winNotificationAgreement');
        if (agreementCheckbox && !agreementCheckbox.checked) {
            alert('Необходимо дать согласие на обработку персональных данных');
            return;
        }

        if (!this.config.guestId) {
            console.error('[LuckyWheel] Guest ID not found');
            this.controller.showError(this.config.getText('error_init_guest'));
            return;
        }

        spinButton.disabled = true;
        const originalText = spinButton.textContent;
        spinButton.textContent = this.config.getText('form_submit_loading');

        try {
            const formData = this.formHandler.getFormData();
            await this.api.updateGuest(this.config.guestId, formData);
            
            spinButton.textContent = originalText;
            await this.controller.spin();
        } catch (error) {
            console.error('[LuckyWheel] Error saving guest data:', error);
            this.controller.showError(this.config.getText('error_general') + ' ' + error.message);
            spinButton.disabled = false;
            spinButton.textContent = originalText;
        }
    }

    async handleWheelInfoSpinButtonClick() {
        const wheelInfoSpinButton = document.getElementById('wheelInfoSpinButton');
        if (!wheelInfoSpinButton || wheelInfoSpinButton.disabled) return;

        if (!this.config.guestId) {
            console.error('[LuckyWheel] Guest ID not found');
            this.controller.showError(this.config.getText('error_init_guest'));
            return;
        }

        wheelInfoSpinButton.disabled = true;
        const originalText = wheelInfoSpinButton.textContent;
        wheelInfoSpinButton.textContent = this.config.getText('form_submit_loading');

        try {
            await this.controller.spin();
            wheelInfoSpinButton.textContent = originalText;
        } catch (error) {
            console.error('[LuckyWheel] Error spinning:', error);
            this.controller.showError(this.config.getText('error_general') + ' ' + error.message);
            wheelInfoSpinButton.disabled = false;
            wheelInfoSpinButton.textContent = originalText;
        }
    }
}

// Глобальная переменная для хранения экземпляра приложения
window.__luckyWheelAppInstance = null;

function initializeLuckyWheel() {
    // Если уже есть экземпляр, уничтожаем его
    if (window.__luckyWheelAppInstance) {
        console.log('[LuckyWheel] Destroying previous instance');
        window.__luckyWheelAppInstance = null;
    }

    console.log('[LuckyWheel] Initializing new instance');
    const app = new LuckyWheelApp();
    window.__luckyWheelAppInstance = app;
    app.init();
}

console.log('[LuckyWheel] Script loaded, document.readyState:', document.readyState);

// Инициализация при первой загрузке
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[LuckyWheel] DOMContentLoaded fired');
        initializeLuckyWheel();
    });
} else {
    console.log('[LuckyWheel] DOM already loaded, initializing immediately');
    initializeLuckyWheel();
}

// Экспортируем функцию для повторной инициализации
window.reinitializeLuckyWheel = function() {
    console.log('[LuckyWheel] Reinitializing via window.reinitializeLuckyWheel()');
    // Небольшая задержка, чтобы DOM успел обновиться
    setTimeout(() => {
        initializeLuckyWheel();
    }, 100);
};
