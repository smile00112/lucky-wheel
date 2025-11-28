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
        this.canvas = null;
        this.resizeHandler = null;
    }

    async init() {
        console.log('[LuckyWheel] WheelController.init() started');
        try {
            console.log('[LuckyWheel] Loading wheel data...');
            const wheelData = await this.api.loadWheelData();
            console.log('[LuckyWheel] Wheel data loaded:', wheelData);
            const prizes = wheelData.prizes || [];
            console.log('[LuckyWheel] Prizes count:', prizes.length);

            if (prizes.length === 0) {
                throw new Error(this.config.getText('error_no_prizes'));
            }

            // Обновляем тексты из API, если они есть
            if (wheelData.texts) {
                this.config.updateTexts(wheelData.texts);
            }

            this.state.set('wheelData', wheelData);
            this.state.set('prizes', prizes);

            // Загрузка изображений с таймаутом
            console.log('[LuckyWheel] Loading prize images...');
            try {
                await Promise.race([
                    this.imageLoader.loadPrizeImages(prizes),
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Image loading timeout')), 20000)
                    )
                ]);
                console.log('[LuckyWheel] Prize images loaded');
            } catch (error) {
                console.warn('[LuckyWheel] Image loading failed or timeout:', error);
                // Продолжаем работу без изображений
            }

            console.log('[LuckyWheel] Showing wheel content...');
            this.showWheelContent();

            // Инициализируем canvas после показа контента, чтобы размеры были правильными
            const canvas = document.getElementById('wheelCanvas');
            console.log('[LuckyWheel] Canvas element:', canvas ? 'found' : 'not found');
            if (canvas) {
                this.canvas = canvas;
                // Небольшая задержка для гарантии, что DOM обновился
                setTimeout(() => {
                    console.log('[LuckyWheel] Initializing canvas...');
                    this.renderer.init(canvas);
                    this.renderer.draw(0);
                    console.log('[LuckyWheel] Canvas initialized and drawn');
                    this.setupResizeHandler();
                }, 50);
            }
            this.updateSpinsInfo();
            console.log('[LuckyWheel] Checking and applying won prize...');
            await this.checkAndApplyWonPrize();

            Utils.notifyParent('ready', {});
            console.log('[LuckyWheel] WheelController.init() completed successfully');
        } catch (error) {
            console.error('[LuckyWheel] Wheel initialization error:', error);
            this.showError(this.config.getText('error_load_data') + ' ' + error.message);
            // Убеждаемся, что контент показан даже при ошибке
            this.showWheelContent();
        }
    }

    showWheelContent() {
        console.log('[LuckyWheel] showWheelContent() called');
        const loading = document.getElementById('loading');
        const content = document.getElementById('wheelContent');

        console.log('[LuckyWheel] Loading element:', loading ? 'found' : 'not found');
        console.log('[LuckyWheel] Content element:', content ? 'found' : 'not found');

        if (loading) {
            loading.style.display = 'none';
            console.log('[LuckyWheel] Loading hidden');
        } else {
            console.warn('[LuckyWheel] Loading element not found!');
        }

        if (content) {
            content.style.display = 'flex';
            console.log('[LuckyWheel] Content shown');
        } else {
            console.warn('[LuckyWheel] Content element not found!');
        }
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
console.log('applyWonPrize', winData);
        if (prize?.email_image) {
            this.notification.showPrizeImage(prize.email_image);
        }

        this.notification.show(prize, code, winData.guest_has_data);

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

            // Отправляем запрос о завершении вращения после окончания анимации
            if (data.prize && data.spin_id) {
                try {
                    await this.api.completeSpin(data.spin_id);
                } catch (error) {
                    console.error('Failed to complete spin:', error);
                    // Не блокируем выполнение при ошибке завершения
                }
            }

            this.updateSpinsInfo(data.spins_count, data.spins_limit);

            if (data.prize) {
                this.state.saveWin(data.prize, data.code, data.guest_has_data, data.spin_id);
                Utils.notifyParent('win', data.prize);

                setTimeout(() => {
                    this.notification.show(data.prize, data.code, data.guest_has_data);
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

    setupResizeHandler() {
        // Удаляем предыдущий обработчик, если он есть
        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
        }

        // Создаем новый обработчик с debounce
        let resizeTimeout;
        this.resizeHandler = () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (window.innerWidth <= 768 && this.canvas) {
                    const currentRotation = this.state.get('currentRotation') || 0;
                    this.renderer.init(this.canvas);
                    this.renderer.draw(currentRotation);
                }
            }, 250);
        };

        window.addEventListener('resize', this.resizeHandler);
    }
}

