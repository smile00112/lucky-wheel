import { Utils } from './utils.js';

export class WheelController {
    constructor(state, config, api, renderer, animation, imageLoader, notification) {
        this.state = state;
        this.config = config;
        this.api = api;
        this.renderer = renderer;
        this.animation = animation;
        this.imageLoader = imageLoader;
        this.notification = notification;
    }

    async init() {
        try {
            const wheelData = await this.api.loadWheelData();
            const prizes = wheelData.prizes || [];

            if (prizes.length === 0) {
                throw new Error(this.config.getText('error_no_prizes'));
            }

            // Обновляем тексты из API, если они есть
            if (wheelData.texts) {
                this.config.updateTexts(wheelData.texts);
            }

            this.state.set('wheelData', wheelData);
            this.state.set('prizes', prizes);

            await this.imageLoader.loadPrizeImages(prizes);

            this.showWheelContent();

            // Инициализируем canvas после показа контента, чтобы размеры были правильными
            const canvas = document.getElementById('wheelCanvas');
            if (canvas) {
                // Небольшая задержка для гарантии, что DOM обновился
                setTimeout(() => {
                    this.renderer.init(canvas);
                    this.renderer.draw(0);
                }, 50);
            }
            this.updateSpinsInfo();
            await this.checkAndApplyWonPrize();

            Utils.notifyParent('ready', {});
        } catch (error) {
            console.error('Wheel initialization error:', error);
            this.showError(this.config.getText('error_load_data') + ' ' + error.message);
        }
    }

    showWheelContent() {
        const loading = document.getElementById('loading');
        const content = document.getElementById('wheelContent');

        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'block';
    }

    updateSpinsInfo(spinsCount = null, spinsLimit = null) {
        const infoEl = document.getElementById('spinsInfo');
        const wheelData = this.state.get('wheelData');

        if (!infoEl || !wheelData?.spins_limit) {
            if (infoEl) infoEl.textContent = '';
            return;
        }

        if (spinsCount !== null && spinsLimit !== null) {
            const format = this.config.getText('spins_info_format');
            infoEl.textContent = format.replace('{count}', spinsCount).replace('{limit}', spinsLimit);
        } else {
            const format = this.config.getText('spins_limit_format');
            infoEl.textContent = format.replace('{limit}', wheelData.spins_limit);
        }
    }

    async checkAndApplyWonPrize() {
        const prizes = this.state.get('prizes');
        if (!prizes || prizes.length === 0) return;

        const winData = this.state.getWinData();
        if (winData && this.state.isTodayWin(winData)) {
            this.applyWonPrize(winData);
            return;
        }

        try {
            const data = await this.api.checkTodayWin(this.config.guestId);
            if (data.has_win && data.prize) {
                this.state.saveWin(data.prize, data.code, data.guest_has_data, data.spin_id);
                this.applyWonPrize({
                    prize: data.prize,
                    code: data.code,
                    guest_has_data: data.guest_has_data,
                    spin_id: data.spin_id,
                });
            }
        } catch (error) {
            console.error('Error checking today win:', error);
        }
    }

    applyWonPrize(winData) {
        const { prize, code } = winData;

        if (prize?.email_image) {
            this.notification.showPrizeImage(prize.email_image);
        }

        this.notification.show(prize, code, winData.guest_has_data);
        this.notification.showWonPrizeBlock(code);

        const rotation = this.renderer.calculateRotationForPrize(prize.id);
        this.state.set('currentRotation', rotation);
        
        // Проверяем, что canvas инициализирован перед рисованием
        const canvas = this.state.get('canvas');
        const ctx = this.state.get('ctx');
        if (canvas && ctx) {
            this.renderer.draw(rotation);
        }

        this.blockSpinning();
    }

    async spin() {
        if (this.state.get('isSpinning')) return;

        this.state.set('isSpinning', true);
        const spinButton = document.getElementById('spinButton');
        if (spinButton) {
            spinButton.disabled = true;
        }

        this.hideError();

        try {
            const data = await this.api.spin(this.config.guestId);

            Utils.notifyParent('spin', data);

            const prizeIndex = data.prize ? this.renderer.findPrizeIndex(data.prize.id) : -1;

            await this.animation.animate(prizeIndex);

            this.updateSpinsInfo(data.spins_count, data.spins_limit);

            if (data.prize) {
                this.state.saveWin(data.prize, data.code, data.guest_has_data, data.spin_id);
                Utils.notifyParent('win', data.prize);

                setTimeout(() => {
                    this.notification.show(data.prize, data.code, data.guest_has_data);
                    this.notification.showWonPrizeBlock(data.code);
                }, 500);

                this.blockSpinning();
            } else {
                alert(this.config.getText('error_spin'));
            }
        } catch (error) {
            if (error.message.includes('Already won today')) {
                const winData = this.state.getWinData();
                if (winData) {
                    setTimeout(() => {
                        this.notification.show(winData.prize, winData.code, winData.guest_has_data);
                        this.notification.showWonPrizeBlock(winData.code);
                    }, 100);
                    this.blockSpinning();
                }
            } else {
                this.showError(this.config.getText('error_general') + ' ' + error.message);
                Utils.notifyParent('error', { message: error.message });
            }
        } finally {
            this.state.set('isSpinning', false);
            const wheelData = this.state.get('wheelData');
            if (wheelData?.spins_limit) {
                setTimeout(() => this.init(), 500);
            } else if (spinButton) {
                spinButton.disabled = false;
            }
        }
    }

    blockSpinning() {
        const spinButton = document.getElementById('spinButton');
        if (spinButton) {
            spinButton.disabled = true;
            spinButton.textContent = this.config.getText('spin_button_blocked_text');
            spinButton.style.cursor = 'not-allowed';
        }
    }

    unblockSpinning() {
        const spinButton = document.getElementById('spinButton');
        if (spinButton) {
            spinButton.disabled = false;
            spinButton.textContent = this.config.getText('spin_button_text');
            spinButton.style.cursor = 'pointer';
        }
    }

    showError(message) {
        const loading = document.getElementById('loading');
        const errorEl = document.getElementById('error');
        const winNotification = document.getElementById('winNotification');

        if (loading) loading.style.display = 'none';
        if (!errorEl) return;

        errorEl.textContent = message;
        errorEl.classList.add('show');

        const isWinNotificationVisible = winNotification &&
            winNotification.style.display !== 'none' &&
            winNotification.classList.contains('show');

        if (isWinNotificationVisible) {
            errorEl.classList.add('error-overlay');
            setTimeout(() => {
                errorEl.classList.remove('show', 'error-overlay');
            }, 5000);
        } else {
            errorEl.classList.remove('error-overlay');
        }
    }

    hideError() {
        const errorEl = document.getElementById('error');
        if (errorEl) {
            errorEl.classList.remove('show', 'error-overlay');
        }
    }
}

