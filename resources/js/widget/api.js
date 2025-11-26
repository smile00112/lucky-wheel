export class ApiService {
    constructor(config) {
        this.config = config;
    }

    async request(endpoint, options = {}) {
        const url = `${this.config.apiUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };

        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();

            if (!response.ok) {
                // Сервер передает локализованный текст в message, а error содержит код
                const errorMsg = data.message || data.error || this.config.getText('error_request_failed');
                throw new Error(errorMsg);
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
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
}

