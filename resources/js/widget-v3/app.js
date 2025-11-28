import { Config } from './config.js';
import { ApiService } from './api.js';
import { StateManager } from './state.js';
import { WheelRenderer } from './wheel-renderer.js';
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

        // Показываем форму при загрузке, если нет выигрыша
        if (!hasWin) {
            this.showInitialForm();
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
        const sendContainer = document.getElementById('winNotificationSendContainer');
        const spinButton = document.getElementById('spinButton');
        const submitBtn = document.getElementById('winNotificationSubmitBtn');

        if (!notification || !formContainer) return;

        // Скрываем секцию с результатами
        if (winningFormContainer) {
            winningFormContainer.style.display = 'none';
        }

        // Показываем форму
        notification.style.display = 'block';
        notification.classList.add('show');
        formContainer.style.display = 'block';
        if (sendContainer) sendContainer.style.display = 'none';
        if (spinButton) spinButton.style.display = 'block';
        if (submitBtn) submitBtn.style.display = 'none';
    }

    async handleSpinButtonClick() {
        const spinButton = document.getElementById('spinButton');
        if (!spinButton || spinButton.disabled) return;

        const nameInput = document.getElementById('winNotificationName');
        const emailInput = document.getElementById('winNotificationEmail');
        const phoneInput = document.getElementById('winNotificationPhone');
        const agreementCheckbox = document.getElementById('winNotificationAgreement');

        if (!nameInput || !emailInput || !phoneInput) {
            console.error('[LuckyWheel] Form inputs not found');
            return;
        }

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const phone = phoneInput.value.trim();

        if (!name || !email || !phone) {
            alert('Пожалуйста, заполните все поля формы');
            return;
        }

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
}

console.log('[LuckyWheel] Script loaded, document.readyState:', document.readyState);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[LuckyWheel] DOMContentLoaded fired');
        const app = new LuckyWheelApp();
        app.init();
    });
} else {
    console.log('[LuckyWheel] DOM already loaded, initializing immediately');
    const app = new LuckyWheelApp();
    app.init();
}
