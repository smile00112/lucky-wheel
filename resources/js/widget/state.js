export class StateManager {
    constructor(config) {
        this.config = config;
        this.state = {
            wheelData: null,
            prizes: [],
            isSpinning: false,
            currentRotation: 0,
            canvas: null,
            ctx: null,
            centerX: 0,
            centerY: 0,
            radius: 0,
            prizeImages: {},
        };
        this.listeners = new Map();
    }

    get(key) {
        return this.state[key];
    }

    set(key, value) {
        const oldValue = this.state[key];
        this.state[key] = value;
        this.notify(key, value, oldValue);
    }

    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }
        this.listeners.get(key).push(callback);
    }

    unsubscribe(key, callback) {
        const callbacks = this.listeners.get(key);
        if (callbacks) {
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    notify(key, newValue, oldValue) {
        const callbacks = this.listeners.get(key);
        if (callbacks) {
            callbacks.forEach(callback => callback(newValue, oldValue));
        }
    }

    getWinData() {
        const key = this.config.getStorageKey(`win_${this.config.guestId || ''}`);
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    }

    saveWin(prize, code, guestHasData = null, spinId = null) {
        const key = this.config.getStorageKey(`win_${this.config.guestId || ''}`);
        const winData = {
            date: new Date().toISOString(),
            prize,
            code,
            guest_has_data: guestHasData,
            spin_id: spinId,
        };
        localStorage.setItem(key, JSON.stringify(winData));
    }

    clearWin() {
        const key = this.config.getStorageKey(`win_${this.config.guestId || ''}`);
        localStorage.removeItem(key);
    }

    isTodayWin(winData) {
        if (!winData) return false;
        const winDate = new Date(winData.date);
        const today = new Date();
        return winDate.toDateString() === today.toDateString();
    }
}

