export class Utils {
    static formatPhone(value) {
        const digits = value.replace(/\D/g, '');
        if (digits.startsWith('8')) {
            return '7' + digits.substring(1);
        }
        return digits.substring(0, 11);
    }

    static formatPhoneDisplay(value) {
        const digits = this.formatPhone(value);
        if (!digits) return '';

        let formatted = '+7';
        if (digits.length > 1) {
            formatted += ` (${digits.substring(1, 4)}`;
            if (digits.length >= 4) {
                formatted += `) ${digits.substring(4, 7)}`;
                if (digits.length >= 7) {
                    formatted += `-${digits.substring(7, 9)}`;
                    if (digits.length >= 9) {
                        formatted += `-${digits.substring(9, 11)}`;
                    }
                }
            }
        }
        return formatted;
    }

    static applyPhoneMask(input) {
        if (!input || input.hasAttribute('data-mask-applied')) return;

        const handler = (e) => {
            const value = e.target.value.replace(/\D/g, '');
            e.target.value = this.formatPhoneDisplay(value);
        };

        input.addEventListener('input', handler);
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            input.value = this.formatPhoneDisplay(pasted);
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && input.value.length <= 4) {
                e.preventDefault();
                input.value = '';
            }
        });

        input.setAttribute('data-mask-applied', 'true');
    }

    static async copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        const input = document.createElement('input');
        input.value = text;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        input.setSelectionRange(0, 99999);

        try {
            document.execCommand('copy');
            return Promise.resolve();
        } catch (err) {
            return Promise.reject(err);
        } finally {
            document.body.removeChild(input);
        }
    }

    static notifyParent(action, data) {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                type: 'lucky-wheel',
                action,
                data,
            }, '*');
        }
    }

    static getColorByIndex(index) {
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A',
            '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2',
            '#F8B739', '#E74C3C', '#3498DB', '#2ECC71'
        ];
        return colors[index % colors.length];
    }

    static normalizeAngle(angle) {
        return ((angle % (2 * Math.PI)) + (2 * Math.PI)) % (2 * Math.PI);
    }
}

