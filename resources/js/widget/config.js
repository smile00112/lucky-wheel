export class Config {
    constructor() {
        this.apiUrl = window.API_URL || '';
        this.appUrl = window.APP_URL || '';
        this.wheelSlug = window.WHEEL_SLUG || '';
        this.guestId = this.getGuestIdFromUrl();
    }

    getGuestIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('guest_id');
    }

    getStorageKey(key) {
        return `lucky_wheel_${key}_${this.wheelSlug}`;
    }
}

