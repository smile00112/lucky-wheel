import { Utils } from './utils.js';

export class FormHandler {
    constructor(state, config, api, notification) {
        this.state = state;
        this.config = config;
        this.api = api;
        this.notification = notification;
    }

    async submit(event) {
        if (event) {
            event.preventDefault();
        }

        const formContainer = document.getElementById('winNotificationFormContainer');
        const sendContainer = document.getElementById('winNotificationSendContainer');
        const submitBtn = this.getSubmitButton(formContainer, sendContainer);

        if (!submitBtn) return;

        this.setButtonLoading(submitBtn, true);

        try {
            if (formContainer && formContainer.style.display !== 'none') {
                await this.submitFormData(submitBtn);
            } else {
                await this.sendPrizeEmail(submitBtn);
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.setButtonLoading(submitBtn, false);
        }
    }

    getSubmitButton(formContainer, sendContainer) {
        let btn = document.getElementById('winNotificationSubmitBtn');
        if (sendContainer?.style.display === 'block') {
            btn = sendContainer.querySelector('#winNotificationSubmitBtn2');
        }
        return btn;
    }

    setButtonLoading(button, loading) {
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.textContent = this.config.getText('form_submit_loading');
            button.style.cursor = 'not-allowed';
        } else {
            button.disabled = false;
            button.textContent = this.config.getText('form_submit_text');
            button.style.cursor = 'pointer';
        }
    }

    setButtonSuccess(button) {
        if (!button) return;
        button.disabled = true;
        button.textContent = this.config.getText('form_submit_success');
        button.style.background = '#4caf50';
        button.style.color = 'white';
        button.style.cursor = 'not-allowed';
    }

    setButtonError(button, message) {
        if (!button) return;
        button.disabled = true;
        button.textContent = message || this.config.getText('form_submit_error');
        button.style.background = '#ff6b6b';
        button.style.color = 'white';
        button.style.cursor = 'not-allowed';
    }

    getFormData() {
        const nameInput = document.getElementById('winNotificationName');
        const emailInput = document.getElementById('winNotificationEmail');
        const phoneInput = document.getElementById('winNotificationPhone');

        let phoneValue = phoneInput?.value || '';
        phoneValue = Utils.formatPhone(phoneValue);
        if (phoneValue && !phoneValue.startsWith('+')) {
            phoneValue = '+' + phoneValue;
        }

        return {
            name: nameInput?.value || '',
            email: emailInput?.value || '',
            phone: phoneValue || '',
        };
    }

    async submitFormData(submitBtn) {
        const formData = this.getFormData();

        try {
            const data = await this.api.claimPrize(this.config.guestId, formData);

            this.setButtonSuccess(submitBtn);

            const formContainer = document.getElementById('winNotificationFormContainer');
            const sendContainer = document.getElementById('winNotificationSendContainer');
            if (formContainer) formContainer.style.display = 'none';
            if (sendContainer) sendContainer.style.display = 'none';

            const winData = this.state.getWinData();
            if (winData?.prize?.email_image) {
                this.notification.showPrizeImage(winData.prize.email_image);
            }

            const message = document.getElementById('winNotificationMessage');
            if (message) {
                const successMsg = this.config.getText('form_success_message');
                message.innerHTML += '<br><br><strong style="color: #4caf50;">' + successMsg + '</strong>';
            }

            const pdfLink = document.getElementById('winNotificationPdfLink');
            if (pdfLink) {
                await this.notification.setupPdfLink(pdfLink, true);
            }

            Utils.notifyParent('claim-prize', { guest_id: data.guest_id });
        } catch (error) {
            this.handleError(error, submitBtn);
        }
    }

    async sendPrizeEmail(submitBtn) {
        const winData = this.state.getWinData();
        const spinId = winData?.spin_id;

        if (!spinId) {
            this.setButtonLoading(submitBtn, false);
            throw new Error('Spin ID not found');
        }

        try {
            await this.api.sendPrizeEmail(spinId);
            this.setButtonSuccess(submitBtn);
        } catch (error) {
            this.handleError(error, submitBtn);
        }
    }

    handleError(error, submitBtn) {
        const errorMessage = error.message || this.config.getText('error_send');

        if (errorMessage.includes('already claimed') || errorMessage.includes('уже получен')) {
            this.setButtonError(submitBtn, this.config.getText('form_submit_error'));

            const message = document.getElementById('winNotificationMessage');
            if (message) {
                const originalMessage = message.innerHTML;
                message.innerHTML = originalMessage + `<br><br><strong style="color: #ff6b6b;">⚠️ ${errorMessage}</strong>`;
            }
        } else {
            this.setButtonLoading(submitBtn, false);
        }
    }

    async copyCode(event) {
        const codeInput = document.getElementById('winNotificationCode');
        const code = codeInput?.value;

        if (!code) return;

        try {
            await Utils.copyToClipboard(code);
            this.showCopyFeedback(event);
        } catch (err) {
            const errorMsg = this.config.getText('error_copy_code');
            alert(errorMsg + ' ' + code);
        }
    }

    showCopyFeedback(event) {
        const button = event.target.closest('button');
        if (!button) return;

        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#28a745" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
        button.style.background = '#d4edda';

        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.background = 'white';
        }, 2000);
    }
}

