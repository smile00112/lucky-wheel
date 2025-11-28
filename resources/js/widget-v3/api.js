export class ApiService {
    constructor(config) {
        this.config = config;
    }

    async request(endpoint, options = {}) {
        const url = `${this.config.apiUrl}${endpoint}`;
        console.log('[LuckyWheel] API request:', url, options.method || 'GET');
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };

        const timeout = 15000; // 15 секунд
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            console.warn('[LuckyWheel] API request timeout:', url);
            controller.abort();
        }, timeout);

        try {
            const response = await fetch(url, { 
                ...defaultOptions, 
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            console.log('[LuckyWheel] API response received:', url, response.status);
            const data = await response.json();

            if (!response.ok) {
                // Сервер передает локализованный текст в message, а error содержит код
                let errorMsg = data.message || data.error || this.config.getText('error_request_failed');
                
                // Переводим известные коды ошибок
                if (data.error === 'Validation failed') {
                    errorMsg = this.config.getText('error_validation_failed');
                } else if (data.error === 'Prize already claimed today') {
                    errorMsg = this.config.getText('error_prize_already_claimed_today');
                } else if (data.error && !data.message) {
                    // Пытаемся найти перевод для кода ошибки
                    const translationKey = `error_${data.error.toLowerCase().replace(/\s+/g, '_')}`;
                    const translation = this.config.getText(translationKey);
                    if (translation) {
                        errorMsg = translation;
                    }
                }
                
                console.error('[LuckyWheel] API error response:', url, errorMsg);
                throw new Error(errorMsg);
            }

            console.log('[LuckyWheel] API request successful:', url);
            return data;
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                console.error('[LuckyWheel] API request timeout:', url);
                throw new Error('Request timeout');
            }
            // Обработка ошибки "Failed to fetch" (нет сети, CORS и т.д.)
            if (error.message === 'Failed to fetch' || error.message.includes('Failed to fetch')) {
                console.error('[LuckyWheel] API request failed (network error):', url);
                throw new Error(this.config.getText('error_failed_to_fetch'));
            }
            console.error('[LuckyWheel] API request failed:', url, error);
            throw error;
        }
    }

    async createOrGetGuest() {
        const storageKey = this.config.getStorageKey('guest');
        const savedGuestId = localStorage.getItem(storageKey);

        if (savedGuestId) {
            return savedGuestId;
        }

        const data = await this.request('/guest', {
            method: 'POST',
            body: JSON.stringify({ wheel_slug: this.config.wheelSlug }),
        });

        const guestId = String(data.id || data.guest_id);
        if (guestId) {
            localStorage.setItem(storageKey, guestId);
            return guestId;
        }

        throw new Error(this.config.getText('error_failed_to_get_guest'));
    }

    async loadWheelData() {
        return this.request(`/wheel/${this.config.wheelSlug}`);
    }

    async checkTodayWin(guestId) {
        return this.request(`/wheel/${this.config.wheelSlug}/today-win?guest_id=${guestId}`);
    }

    async spin(guestId) {
        return this.request('/spin', {
            method: 'POST',
            body: JSON.stringify({
                wheel_slug: this.config.wheelSlug,
                guest_id: parseInt(guestId),
            }),
        });
    }

    async claimPrize(guestId, formData) {
        return this.request(`/guest/${guestId}/claim-prize`, {
            method: 'POST',
            body: JSON.stringify({
                ...formData,
                wheel_slug: this.config.wheelSlug,
            }),
        });
    }

    async sendPrizeEmail(spinId) {
        return this.request(`/spin/${spinId}/send-email`, {
            method: 'POST',
        });
    }

    async completeSpin(spinId) {
        return this.request(`/spin/${spinId}/complete`, {
            method: 'POST',
        });
    }

    async getGuestInfo(guestId) {
        return this.request(`/guest/${guestId}/info`);
    }

    async updateGuest(guestId, formData) {
        return this.request(`/guest/${guestId}`, {
            method: 'POST',
            body: JSON.stringify(formData),
        });
    }
}

