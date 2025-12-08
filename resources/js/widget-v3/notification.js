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
        const winningFormContainer = document.getElementById('winningFormContainer');
        const formContainer = document.getElementById('winNotificationFormContainer');
        const wheelInfoBlock = document.getElementById('wheelInfoBlock');
        const sendContainer = document.getElementById('winNotificationSendContainer');
        const pdfLink = document.getElementById('winNotificationPdfLink');

        if (!prize || !notification || !message) return;

        // Скрываем форму и блок информации о колесе
        if (formContainer) {
            formContainer.style.display = 'none';
        }
        if (wheelInfoBlock) {
            wheelInfoBlock.style.display = 'none';
        }
        if (sendContainer) {
            sendContainer.style.display = 'none';
        }

        // Проверяем, нужно ли показывать форму вместо результатов
        const wheelData = this.state.get('wheelData');
        const forceDataCollection = wheelData?.force_data_collection ?? true;

        if (!forceDataCollection && !guestHasData) {
            // Если force_data_collection = false и данные не заполнены, показываем форму
            if (formContainer) {
                formContainer.style.display = 'block';
            }
            if (winningFormContainer) {
                winningFormContainer.style.display = 'none';
            }

            // Показываем блок с заголовком выигрыша над формой
            const formHeader = document.getElementById('winNotificationFormHeader');
            const formInitial = document.getElementById('winNotificationFormInitial');
            const formMessage = document.getElementById('winNotificationFormMessage');

            if (formHeader) {
                formHeader.style.display = 'block';
            }
            if (formInitial) {
                formInitial.style.display = 'none';
            }
            if (formMessage) {
                const winText = this.config.getText('win_notification_win_text');
                const prizeName = prize.full_name || prize.name;
                const cleanName = this.cleanPrizeName(prizeName);
                const prizeNameHtml = this.processTextForHtml(cleanName);
                let messageText = `<strong>${prizeNameHtml}</strong>`;
                // if (prize.text_for_winner) {
                //     messageText += `<br>${prize.text_for_winner}`;
                //}
                formMessage.innerHTML = messageText;
            }

            // Обновляем winNotificationFormMessageDop с полным наименованием приза
            const winNotificationFormMessageDop = document.getElementById('winNotificationFormMessageDop');
            if (winNotificationFormMessageDop) {
                if (prize.full_name) {
                    // Если есть полное наименование, используем его вместо статического текста
                    winNotificationFormMessageDop.textContent = prize.full_name;
                }
            }

            // Показываем кнопку отправки формы вместо кнопки вращения
            const spinButton = document.getElementById('spinButton');
            const submitBtn = document.getElementById('winNotificationSubmitBtn');
            if (spinButton) {
                spinButton.style.display = 'none';
                spinButton.disabled = true;
            }
            if (submitBtn) {
                submitBtn.style.display = 'block';
                submitBtn.disabled = false;
            }

            notification.style.display = 'block';
            notification.classList.add('show');
            return;
        }

        // Показываем секцию с результатами
        const winText = this.config.getText('win_notification_win_text');
        const prizeName = prize.full_name || prize.name;
        const cleanName = this.cleanPrizeName(prizeName);
        const prizeNameHtml = this.processTextForHtml(cleanName);
        let messageText = `<strong>${prizeNameHtml}</strong>`;
        // if (prize.text_for_winner) {
        //     messageText += `<br>${winText}`;
        // }
        message.innerHTML = messageText;

        // Обновляем winNotificationMessageDop с полным наименованием приза
        const winNotificationMessageDop = document.getElementById('winNotificationMessageDop');
        if (winNotificationMessageDop) {
            if (prize.full_name) {
                // Если есть полное наименование, используем его вместо статического текста
                winNotificationMessageDop.textContent = prize.full_name;
            }
        }

        // Заполняем поле value приза
        if (codeInput) {
            if (prize.value && prize.value.toString().trim()) {
                codeInput.value = prize.value.toString().trim();
                codeInput.placeholder = '';
            } else {
                codeInput.value = '';
                codeInput.placeholder = this.config.getText('code_not_specified');
            }
        }

        // Заполняем поле промокода
        const promoCodeInput = document.getElementById('winNotificationPromoCode');
        const promoCodeContainer = document.getElementById('winNotificationPromoCodeContainer');
        if (promoCodeInput && promoCodeContainer) {
            if (code && code.toString().trim()) {
                promoCodeInput.value = code.toString().trim();
                promoCodeInput.placeholder = '';
                promoCodeContainer.style.display = 'flex';
            } else {
                promoCodeInput.value = '';
                promoCodeInput.placeholder = this.config.getText('code_not_specified');
                promoCodeContainer.style.display = 'none';
            }
        }

        // Скрываем кнопки формы
        const spinButton = document.getElementById('spinButton');
        const submitBtn = document.getElementById('winNotificationSubmitBtn');
        if (spinButton) spinButton.style.display = 'none';
        if (submitBtn) submitBtn.style.display = 'none';

        // Определяем hasData для PDF ссылки
        let hasData = guestHasData;
        if (hasData === null || hasData === undefined) {
            try {
                const data = await this.api.checkTodayWin(this.config.guestId);
                if (data.has_win && data.guest_has_data !== undefined) {
                    hasData = data.guest_has_data;
                } else {
                    const guestData = await this.api.getGuestInfo(this.config.guestId);
                    hasData = guestData.has_data || false;
                }
            } catch (e) {
                hasData = false;
            }
        }

        await this.setupPdfLink(pdfLink, hasData);

        // Показываем блок с результатами
        if (winningFormContainer) {
            winningFormContainer.style.display = 'block';
        }

        notification.style.display = 'block';
        notification.classList.add('show');
    }

    hide() {
        const notification = document.getElementById('winNotification');
        const formContainer = document.getElementById('winNotificationFormContainer');
        const winningFormContainer = document.getElementById('winningFormContainer');
        if (notification) {
            notification.classList.remove('show');
            notification.style.display = 'none';
        }
        if (formContainer) {
            formContainer.style.display = 'none';
        }
        if (winningFormContainer) {
            winningFormContainer.style.display = 'none';
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

        if (spinId && guestHasData === true) {
            pdfLink.href = `${this.config.apiUrl}/spin/${spinId}/download-pdf`;
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

        const spinButton = document.getElementById('spinButton');
        const submitBtn = document.getElementById('winNotificationSubmitBtn');

        if (guestHasData === true) {
            if (formContainer) formContainer.style.display = 'none';
            if (sendContainer) sendContainer.style.display = 'none';
            if (spinButton) spinButton.style.display = 'none';
            if (submitBtn) submitBtn.style.display = 'none';
        } else {
            // Всегда получаем данные гостя для заполнения формы
            let guestInfo = null;
            try {
                guestInfo = await this.api.getGuestInfo(this.config.guestId);
            } catch (e) {
                console.warn('Could not fetch guest info for form prefill:', e);
            }

            if (formContainer) formContainer.style.display = 'block';
            if (sendContainer) sendContainer.style.display = 'none';
            if (spinButton) spinButton.style.display = 'block';
            if (submitBtn) submitBtn.style.display = 'none';

            const phoneInput = document.getElementById('winNotificationPhone');
            if (phoneInput) {
                Utils.applyPhoneMask(phoneInput);
                if (guestInfo?.phone) {
                    phoneInput.value = Utils.formatPhoneDisplay(guestInfo.phone);
                }
            }

            const nameInput = document.getElementById('winNotificationName');
            if (nameInput && guestInfo?.name) {
                nameInput.value = guestInfo.name;
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

    processTextForHtml(text) {
        if (!text) return '';
        return text
            .replace(/<br\s*\/?>/gi, '\n')
            .split('\n')
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .join('<br>');
    }

    cleanPrizeName(name) {
        if (!name) return '';
        // Сначала обрабатываем HTML теги <br>
        let cleanName = name.replace(/<br\s*\/?>/gi, '|');
        const separators = ['|', ' - ', ' — ', ' | ', '| ', ' |'];
        for (const separator of separators) {
            const pos = cleanName.indexOf(separator);
            if (pos !== -1) {
                return cleanName.substring(0, pos).trim();
            }
        }
        return name;
    }
}

