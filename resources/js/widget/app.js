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
        if (!this.config.guestId) {
            try {
                this.config.guestId = await this.api.createOrGetGuest();
                if (!this.config.guestId) {
                    this.controller.showError('Ошибка инициализации: не удалось создать гостя');
                    return;
                }
            } catch (error) {
                this.controller.showError('Ошибка инициализации: ' + error.message);
                return;
            }
        }


        const phoneInput = document.getElementById('winNotificationPhone');
        if (phoneInput) {
            Utils.applyPhoneMask(phoneInput);
        }

        await this.checkTodayWin();
        await this.controller.init();

        setInterval(() => this.checkTodayWin(), 60000);

        this.setupEventListeners();
    }

    async checkTodayWin() {
        const winData = this.state.getWinData();

        if (winData && this.state.isTodayWin(winData)) {
            this.controller.applyWonPrize(winData);
            return;
        }

        if (winData && !this.state.isTodayWin(winData)) {
            this.state.clearWin();
            this.controller.unblockSpinning();
            this.notification.hide();
            this.notification.hideWonPrizeBlock();
            this.state.set('currentRotation', 0);
            this.renderer.draw(0);
            return;
        }

        try {
            const data = await this.api.checkTodayWin(this.config.guestId);
            if (data.has_win && data.prize) {
                this.state.saveWin(data.prize, data.code, data.guest_has_data, data.spin_id);
                this.controller.applyWonPrize({
                    prize: data.prize,
                    code: data.code,
                    guest_has_data: data.guest_has_data,
                    spin_id: data.spin_id,
                });
            } else {
                this.controller.unblockSpinning();
                this.notification.hideWonPrizeBlock();
                this.state.set('currentRotation', 0);
                this.renderer.draw(0);
            }
        } catch (error) {
            console.error('Error checking today win:', error);
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
}

document.addEventListener('DOMContentLoaded', () => {
    const app = new LuckyWheelApp();
    app.init();
});

