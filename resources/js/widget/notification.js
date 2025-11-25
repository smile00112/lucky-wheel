import { Utils } from './utils.js';

export class NotificationManager {
    constructor(state, config, api) {
        this.state = state;
        this.config = config;
        this.api = api;
    }

    async show(prize, code, guestHasData = null) {
        const notification = document.getElementById('winNotification');
        const message = document.getElementById('winNotificationMessage');
        const codeInput = document.getElementById('winNotificationCode');
        const codeContainer = document.getElementById('winNotificationCodeContainer');
        const formContainer = document.getElementById('winNotificationFormContainer');
        const sendContainer = document.getElementById('winNotificationSendContainer');
        const pdfLink = document.getElementById('winNotificationPdfLink');

        if (!prize || !notification || !message) return;

        const winText = this.config.getText('win_notification_win_text');
        let messageText = `<strong>${winText} ${prize.name}</strong>`;
        if (prize.text_for_winner) {
            messageText += `<br>${prize.text_for_winner}`;
        }
        message.innerHTML = messageText;

        if (codeInput) {
            if (code && code.toString().trim()) {
                codeInput.value = code.toString().trim();
                codeInput.placeholder = '';
            } else {
                codeInput.value = '';
                codeInput.placeholder = this.config.getText('code_not_specified');
            }
        }

        if (codeContainer) {
            codeContainer.style.display = 'flex';
        }

        if (pdfLink) {
            pdfLink.style.display = 'none';
        }

        const hasData = await this.setupFormVisibility(formContainer, sendContainer, guestHasData);
        await this.setupPdfLink(pdfLink, hasData);

        notification.style.display = 'block';
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
    }

    hide() {
        const notification = document.getElementById('winNotification');
        if (notification) {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.style.display = 'none';
            }, 500);
        }
    }

    async setupPdfLink(pdfLink, guestHasData = null) {
        if (!pdfLink) return;

        if (guestHasData === null || guestHasData === undefined) {
            try {
                const data = await this.api.checkTodayWin(this.config.guestId);
                if (data.has_win && data.guest_has_data !== undefined) {
                    guestHasData = data.guest_has_data;
                } else {
                    const guestData = await this.api.getGuestInfo(this.config.guestId);
                    guestHasData = guestData.has_data || false;
                }
            } catch (e) {
                guestHasData = false;
            }
        }

        if (guestHasData === false) {
            pdfLink.style.display = 'none';
            return;
        }

        const winData = this.state.getWinData();
        let spinId = winData?.spin_id;

        if (!spinId) {
            try {
                const data = await this.api.checkTodayWin(this.config.guestId);
                if (data.has_win && data.spin_id) {
                    spinId = data.spin_id;
                }
            } catch (e) {
                console.warn('Could not get spin_id for PDF:', e);
            }
        }

        if (spinId) {
            pdfLink.href = `${this.config.apiUrl}/spin/${spinId}/download-pdf`;
            pdfLink.style.display = 'flex';
        } else {
            pdfLink.style.display = 'none';
        }
    }

    async setupFormVisibility(formContainer, sendContainer, guestHasData) {
        if (guestHasData === null || guestHasData === undefined) {
            try {
                const data = await this.api.checkTodayWin(this.config.guestId);
                if (data.has_win && data.guest_has_data !== undefined) {
                    guestHasData = data.guest_has_data;
                } else {
                    const guestData = await this.api.getGuestInfo(this.config.guestId);
                    guestHasData = guestData.has_data || false;
                }
            } catch (e) {
                guestHasData = false;
            }
        }

        if (guestHasData === true) {
            if (formContainer) formContainer.style.display = 'none';
            if (sendContainer) sendContainer.style.display = 'block';
        } else {
            if (formContainer) formContainer.style.display = 'block';
            if (sendContainer) sendContainer.style.display = 'none';

            const phoneInput = document.getElementById('winNotificationPhone');
            if (phoneInput) {
                Utils.applyPhoneMask(phoneInput);
            }
        }

        return guestHasData;
    }

    showPrizeImage(imageUrl) {
        const container = document.getElementById('winNotificationImageContainer');
        const image = document.getElementById('winNotificationImage');

        if (!container || !image) return;

        let url = imageUrl;
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            if (!url.startsWith('/')) {
                url = `${this.config.appUrl}/storage/${imageUrl}`;
            }
        }

        image.src = url;
        image.alt = this.config.getText('prize_image_alt');
        container.style.display = 'block';
    }

    showWonPrizeBlock(prizeCode) {
        const block = document.getElementById('wonPrizeBlock');
        const codeElement = document.getElementById('wonPrizeCode');

        if (block && codeElement) {
            codeElement.textContent = prizeCode || '';
            block.style.display = prizeCode ? 'block' : 'none';
        }
    }

    hideWonPrizeBlock() {
        const block = document.getElementById('wonPrizeBlock');
        if (block) {
            block.style.display = 'none';
        }
    }
}

