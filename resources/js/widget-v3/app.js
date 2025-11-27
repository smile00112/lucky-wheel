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

        // Заполнение формы данными гостя, если они переданы
        this.fillGuestForm();

        console.log('[LuckyWheel] Checking today win...');
        try {
            await this.checkTodayWin();
            console.log('[LuckyWheel] checkTodayWin() completed');
        } catch (error) {
            console.error('[LuckyWheel] Error in checkTodayWin():', error);
        }

        console.log('[LuckyWheel] Initializing controller...');
        try {
            await this.controller.init();
            console.log('[LuckyWheel] controller.init() completed');
        } catch (error) {
            console.error('[LuckyWheel] Error in controller.init():', error);
            // Убеждаемся, что контент показан даже при ошибке
            this.controller.showWheelContent();
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
        const spinButton = document.getElementById('spinButton');
        if (spinButton) {
            spinButton.addEventListener('click', () => this.controller.spin());
        }

        const closeButton = document.querySelector('.win-notification-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => this.notification.hide());
        }

        const copyButton = document.querySelector('[onclick*="copyPrizeCode"]');
        if (copyButton) {
            copyButton.removeAttribute('onclick');
            copyButton.addEventListener('click', (e) => this.formHandler.copyCode(e));
        }

        const form = document.getElementById('winNotificationForm');
        if (form) {
            form.removeAttribute('onsubmit');
            form.addEventListener('submit', (e) => this.formHandler.submit(e));
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
            // Применяем маску к телефону после заполнения
            if (Utils.applyPhoneMask) {
                Utils.applyPhoneMask(phoneInput);
            }
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

