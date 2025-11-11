/**
 * Lucky Wheel Widget
 * Скрипт для встраивания колеса фортуны на сторонние сайты
 *
 * Использование:
 * <script src="https://yourdomain.com/js/lucky-wheel-widget.js"></script>
 * <script>
 *   LuckyWheel.init({
 *     slug: 'wheel-slug',
 *     apiUrl: 'https://yourdomain.com/api/widget',
 *     container: '#wheel-container',
 *     width: '600px',
 *     height: '700px',
 *     open: true,
 *     onSpin: function(spinData) { console.log('Spin:', spinData); },
 *     onWin: function(prize) { console.log('Win:', prize); },
 *     onError: function(error) { console.error('Error:', error); }
 *   });
 * </script>
 */

(function (window, document) {
    'use strict';

    const LuckyWheel = {
        config: {
            apiUrl: '',
            slug: '',
            container: null,
            width: '600px',
            height: '700px',
            guestId: null,
            iframe: null,
            modal: null,
            floatingIcon: null,
            isModalOpen: false,
            open: false,
            callbacks: {
                onSpin: null,
                onWin: null,
                onError: null,
                onLoad: null,
            }
        },

        /**
         * Инициализация виджета
         */
        init: function (options) {
            // Объединение конфигурации
            Object.assign(this.config, options);
            Object.assign(this.config.callbacks, options);

            if (!this.config.slug) {
                console.error('LuckyWheel: slug is required');
                return;
            }

            if (!this.config.apiUrl) {
                console.error('LuckyWheel: apiUrl is required');
                return;
            }

            // Ждем, пока DOM будет готов
            const initWidget = () => {
                if (document.body && document.head) {
                    // Получение или создание гостя
                    this.getOrCreateGuest()
                        .then(() => {
                            this.createFloatingIcon();
                            this.createModal();
                            // Проверяем, нужно ли открыть модальное окно
                            // Приоритет: config.open > localStorage
                            const shouldOpen = this.config.open === true || this.config.open === 'true';
                            const storedOpen = localStorage.getItem('lucky_wheel_modal_open') === 'true';
                            const hasStoredOpen = localStorage.getItem('lucky_wheel_modal_open');
                            if (shouldOpen && !hasStoredOpen){
                                this.openModal();
                            }
                            if (shouldOpen && hasStoredOpen && !storedOpen){
                                //не открываем
                            }
                            else
                            if (shouldOpen && storedOpen) {
                                this.openModal();
                            }
                        })
                        .catch((error) => {
                            console.error('LuckyWheel: Failed to initialize', error);
                            if (this.config.callbacks.onError) {
                                this.config.callbacks.onError(error);
                            }
                        });
                } else {
                    // Если DOM еще не готов, ждем события
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initWidget, { once: true });
                    } else {
                        // DOM уже загружен, но body еще нет - используем более надежный способ
                        const checkBody = () => {
                            if (document.body && document.head) {
                                initWidget();
                            } else {
                                // Продолжаем проверять с интервалом, но не более 5 секунд
                                const maxAttempts = 50;
                                let attempts = 0;
                                const intervalId = setInterval(() => {
                                    attempts++;
                                    if (document.body && document.head) {
                                        clearInterval(intervalId);
                                        initWidget();
                                    } else if (attempts >= maxAttempts) {
                                        clearInterval(intervalId);
                                        console.error('LuckyWheel: DOM is not ready after 5 seconds');
                                        if (this.config.callbacks.onError) {
                                            this.config.callbacks.onError(new Error('DOM is not ready'));
                                        }
                                    }
                                }, 100);
                            }
                        };
                        checkBody();
                    }
                }
            };

            initWidget();

            // Обработка сообщений от iframe
            window.addEventListener('message', this.handleMessage.bind(this), false);
        },

        /**
         * Получить или создать гостя
         */
        getOrCreateGuest: function () {
            return new Promise((resolve, reject) => {
                // Проверяем localStorage для guest_id
                const storedGuestId = localStorage.getItem('lucky_wheel_guest_id');

                if (storedGuestId) {
                    this.config.guestId = parseInt(storedGuestId);
                    resolve();
                    return;
                }

                // Создаем нового гостя через API
                fetch(`${this.config.apiUrl}/guest`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        // Можно добавить email, phone, name если есть
                    }),
                })
                .then(response => {
                    if (!response.ok) {
                        // Пытаемся получить детали ошибки
                        return response.json().then(errorData => {
                            throw new Error(errorData.error || errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                        }).catch(() => {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // API возвращает 'id', а не 'guest_id'
                    const guestId = data.id || data.guest_id;
                    if (guestId) {
                        this.config.guestId = parseInt(guestId);
                        localStorage.setItem('lucky_wheel_guest_id', this.config.guestId.toString());
                        resolve();
                    } else {
                        console.error('LuckyWheel: Invalid guest response', data);
                        reject(new Error('Failed to create guest: invalid response'));
                    }
                })
                .catch(error => {
                    console.error('LuckyWheel: Error creating guest', error);
                    reject(error);
                });
            });
        },

        /**
         * Создать плавающую иконку
         */
        createFloatingIcon: function () {
            // Удаляем существующую иконку, если есть
            const existingIcon = document.getElementById('lucky-wheel-floating-icon');
            if (existingIcon) {
                existingIcon.remove();
            }

            // Создаем стили для иконки
            if (!document.getElementById('lucky-wheel-icon-styles')) {
                const style = document.createElement('style');
                style.id = 'lucky-wheel-icon-styles';
                style.textContent = `
                    #lucky-wheel-floating-icon {
                        position: fixed;
                        right: 48px;
                        bottom: 128px;
                        width: 70px;
                        height: 70px;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border-radius: 50%;
                        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
                        cursor: pointer;
                        z-index: 9998;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s ease;
                        animation: lucky-wheel-pulse 2s infinite;
                    }
                    #lucky-wheel-floating-icon:hover {
                        transform: scale(1.1);
                        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
                    }
                    #lucky-wheel-floating-icon svg {
                        width: 45px;
                        height: 45px;
                    }
                    @keyframes lucky-wheel-pulse {
                        0%, 100% {
                            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
                        }
                        50% {
                            box-shadow: 0 4px 30px rgba(102, 126, 234, 0.7);
                        }
                    }
                    @media (max-width: 768px) {
                        #lucky-wheel-floating-icon {
                            width: 60px;
                            height: 60px;
                            right: 15px;
                            bottom: 15px;
                        }
                        #lucky-wheel-floating-icon svg {
                            width: 40px;
                            height: 40px;
                        }
                    }
                `;
                if (document.head) {
                    document.head.appendChild(style);
                } else {
                    console.error('LuckyWheel: document.head is not available');
                    return;
                }
            }

            // Проверяем доступность document.body
            if (!document.body) {
                console.error('LuckyWheel: document.body is not available');
                return;
            }

            // Создаем иконку
            const icon = document.createElement('div');
            icon.id = 'lucky-wheel-floating-icon';
            icon.setAttribute('title', 'Колесо фортуны');
            icon.innerHTML = `
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="white" stroke-width="1.5"/>
                    <circle cx="12" cy="12" r="7" fill="none" stroke="white" stroke-width="1"/>
                    <line x1="12" y1="2" x2="12" y2="5" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="12" y1="19" x2="12" y2="22" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="2" y1="12" x2="5" y2="12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="19" y1="12" x2="22" y2="12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="6.34" y1="6.34" x2="8.12" y2="8.12" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="15.88" y1="15.88" x2="17.66" y2="17.66" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="17.66" y1="6.34" x2="15.88" y2="8.12" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="8.12" y1="15.88" x2="6.34" y2="17.66" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="12" cy="12" r="2" fill="white"/>
                    <text x="12" y="15.5" font-family="Arial, sans-serif" font-size="8" fill="white" text-anchor="middle" font-weight="bold">🎡</text>
                </svg>
            `;

            // Обработчик клика
            icon.addEventListener('click', () => {
                this.openModal();
            });

            document.body.appendChild(icon);
            this.config.floatingIcon = icon;
        },

        /**
         * Создать модальное окно
         */
        createModal: function () {
            // Удаляем существующее модальное окно, если есть
            const existingModal = document.getElementById('lucky-wheel-modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Создаем стили для модального окна
            if (!document.getElementById('lucky-wheel-modal-styles')) {
                const style = document.createElement('style');
                style.id = 'lucky-wheel-modal-styles';
                style.textContent = `
                    #lucky-wheel-modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        z-index: 9999;
                        display: none;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                        box-sizing: border-box;
                        animation: lucky-wheel-fadeIn 0.3s ease;
                    }
                    #lucky-wheel-modal-overlay.open {
                        display: flex;
                    }
                    #lucky-wheel-modal {
                        background: white;
                        border-radius: 20px;
                        max-width: 600px;
                        width: 100%;
                        max-height: 90vh;
                        position: relative;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        animation: lucky-wheel-slideUp 0.3s ease;
                        overflow: hidden;
                        height: 100%;
                        max-height: 750px;

                    }
                    #lucky-wheel-modal-close {
                        position: absolute;
                        top: 15px;
                        right: 15px;
                        width: 35px;
                        height: 35px;
                        background: rgba(0, 0, 0, 0.1);
                        border: none;
                        border-radius: 50%;
                        cursor: pointer;
                        z-index: 10000;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s ease;
                        flex-direction: column;

                        font-size: 20px;
                        line-height: 1;
                        color: white;
                        font-weight: 100;
                        padding: 0;
                    }
                    #lucky-wheel-modal-close:hover {
                        background: rgba(0, 0, 0, 0.2);
                        transform: rotate(90deg);
                    }
                    #lucky-wheel-modal-close svg {
                        width: 20px;
                        height: 20px;
                        fill: #333;
                    }
                    #lucky-wheel-modal-content {
                        width: 100%;
                        height: 100%;
                        min-height: 600px;
                        display: table;
                    }
                    #lucky-wheel-modal iframe {
                        width: 100%;
                        height: 100%;
                        border: none;
                        display: block;
                    }
                    @keyframes lucky-wheel-fadeIn {
                        from {
                            opacity: 0;
                        }
                        to {
                            opacity: 1;
                        }
                    }
                    @keyframes lucky-wheel-slideUp {
                        from {
                            transform: translateY(50px);
                            opacity: 0;
                        }
                        to {
                            transform: translateY(0);
                            opacity: 1;
                        }
                    }
                    @media (max-width: 768px) {
                        #lucky-wheel-modal {
                            max-width: 100%;
                            max-height: 100%;
                            border-radius: 0;
                            height: 100%;

                        }
                        #lucky-wheel-modal-overlay {
                            padding: 0;
                        }
                    }
                `;
                if (document.head) {
                    document.head.appendChild(style);
                } else {
                    console.error('LuckyWheel: document.head is not available');
                    return;
                }
            }

            // Проверяем доступность document.body
            if (!document.body) {
                console.error('LuckyWheel: document.body is not available');
                return;
            }

            // Создаем модальное окно
            const overlay = document.createElement('div');
            overlay.id = 'lucky-wheel-modal-overlay';

            const modal = document.createElement('div');
            modal.id = 'lucky-wheel-modal';

            const closeButton = document.createElement('button');
            closeButton.id = 'lucky-wheel-modal-close';
            closeButton.innerHTML = `×`;
            closeButton.addEventListener('click', () => {
                this.closeModal();
            });

            const content = document.createElement('div');
            content.id = 'lucky-wheel-modal-content';

            modal.appendChild(closeButton);
            modal.appendChild(content);
            overlay.appendChild(modal);

            // Закрытие по клику на overlay
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.closeModal();
                }
            });

            // Закрытие по ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.config.isModalOpen) {
                    this.closeModal();
                }
            });

            document.body.appendChild(overlay);
            this.config.modal = overlay;
        },

        /**
         * Открыть модальное окно
         */
        openModal: function () {
            if (!this.config.modal) {
                this.createModal();
            }

            // Убеждаемся, что guestId установлен
            if (!this.config.guestId) {
                this.getOrCreateGuest()
                    .then(() => {
                        this.openModal();
                    })
                    .catch((error) => {
                        console.error('LuckyWheel: Failed to get guest ID', error);
                        if (this.config.callbacks.onError) {
                            this.config.callbacks.onError(error);
                        }
                    });
                return;
            }

            // Создаем iframe, если его еще нет
            const content = document.getElementById('lucky-wheel-modal-content');
            if (!this.config.iframe || !content.contains(this.config.iframe)) {
                const embedUrl = this.config.apiUrl.replace('/api/widget', '/widget/embed');
                const iframe = document.createElement('iframe');
                iframe.id = 'lucky-wheel-iframe';
                iframe.src = `${embedUrl}/${this.config.slug}?guest_id=${this.config.guestId}`;
                iframe.style.width = '100%';
                iframe.style.heigth = '100%';
                //iframe.style.minHeight = '760px';
                iframe.style.border = 'none';
                iframe.allow = 'payment';
                iframe.setAttribute('scrolling', 'no');
                iframe.setAttribute('frameborder', '0');

                // Очищаем контент и добавляем iframe
                content.innerHTML = '';
                content.appendChild(iframe);

                this.config.iframe = iframe;

                // Обработка загрузки iframe
                iframe.onload = () => {
                    if (this.config.callbacks.onLoad) {
                        this.config.callbacks.onLoad();
                    }
                };
            }

            // Показываем модальное окно
            this.config.modal.classList.add('open');
            this.config.isModalOpen = true;
            document.body.style.overflow = 'hidden'; // Блокируем скролл страницы

            // Сохраняем состояние открытия в localStorage
            localStorage.setItem('lucky_wheel_modal_open', 'true');
        },

        /**
         * Закрыть модальное окно
         */
        closeModal: function () {
            if (this.config.modal) {
                this.config.modal.classList.remove('open');
                this.config.isModalOpen = false;
                document.body.style.overflow = ''; // Восстанавливаем скролл

                // Сохраняем состояние закрытия в localStorage
                localStorage.setItem('lucky_wheel_modal_open', 'false');
            }
        },


        /**
         * Обработка сообщений от iframe
         */
        handleMessage: function (event) {
            // Проверка источника (опционально, для безопасности)
            // if (event.origin !== this.config.apiUrl.replace('/api/widget', '')) {
            //     return;
            // }

            const data = event.data;

            if (!data || data.type !== 'lucky-wheel') {
                return;
            }

            switch (data.action) {
                case 'spin':
                    this.handleSpin(data.data);
                    break;
                case 'win':
                    this.handleWin(data.data);
                    // Закрываем модальное окно после выигрыша (опционально)
                    // Можно раскомментировать, если нужно автоматически закрывать
                    // setTimeout(() => this.closeModal(), 3000);
                    break;
                case 'claim-prize':
                    this.handleClaimPrize(data.data);
                    break;
                case 'error':
                    this.handleError(data.data);
                    break;
                case 'ready':
                    // Виджет готов
                    break;
            }
        },

        /**
         * Обработка вращения
         */
        handleSpin: function (spinData) {
            if (this.config.callbacks.onSpin) {
                this.config.callbacks.onSpin(spinData);
            }
        },

        /**
         * Обработка выигрыша
         */
        handleWin: function (prize) {
            if (this.config.callbacks.onWin) {
                this.config.callbacks.onWin(prize);
            }
        },

        /**
         * Обработка успешного claim-prize
         */
        handleClaimPrize: function (data) {
            // Если в ответе есть guest_id (число), обновляем localStorage
            if (data && data.guest_id && typeof data.guest_id === 'number') {
                this.config.guestId = parseInt(data.guest_id);
                localStorage.setItem('lucky_wheel_guest_id', this.config.guestId.toString());
            }
        },

        /**
         * Обработка ошибки
         */
        handleError: function (error) {
            if (this.config.callbacks.onError) {
                this.config.callbacks.onError(error);
            }
        },

        /**
         * Отправить сообщение в iframe
         */
        sendMessage: function (action, data) {
            if (this.config.iframe && this.config.iframe.contentWindow) {
                this.config.iframe.contentWindow.postMessage({
                    type: 'lucky-wheel',
                    action: action,
                    data: data,
                }, '*');
            }
        },

        /**
         * Выполнить вращение (можно вызвать извне)
         */
        spin: function () {
            this.sendMessage('spin', {});
        },

        /**
         * Уничтожить виджет
         */
        destroy: function () {
            // Закрываем модальное окно
            this.closeModal();

            // Удаляем иконку
            if (this.config.floatingIcon && this.config.floatingIcon.parentNode) {
                this.config.floatingIcon.parentNode.removeChild(this.config.floatingIcon);
            }

            // Удаляем модальное окно
            if (this.config.modal && this.config.modal.parentNode) {
                this.config.modal.parentNode.removeChild(this.config.modal);
            }

            // Удаляем iframe
            if (this.config.iframe && this.config.iframe.parentNode) {
                this.config.iframe.parentNode.removeChild(this.config.iframe);
            }

            // Удаляем стили
            const iconStyles = document.getElementById('lucky-wheel-icon-styles');
            if (iconStyles) {
                iconStyles.remove();
            }
            const modalStyles = document.getElementById('lucky-wheel-modal-styles');
            if (modalStyles) {
                modalStyles.remove();
            }

            this.config.iframe = null;
            this.config.modal = null;
            this.config.floatingIcon = null;
            this.config.isModalOpen = false;
            document.body.style.overflow = '';
            window.removeEventListener('message', this.handleMessage.bind(this));
        }
    };

    // Экспорт в глобальную область видимости
    window.LuckyWheel = LuckyWheel;

    // Автоматическая инициализация, если данные переданы через data-атрибуты
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            const script = document.querySelector('script[data-lucky-wheel]');
            if (script) {
                const slug = script.getAttribute('data-slug');
                const apiUrl = script.getAttribute('data-api-url') || '';
                const container = script.getAttribute('data-container') || '#lucky-wheel-container';
                const open = script.getAttribute('data-open') === 'true';

                if (slug && apiUrl) {
                    LuckyWheel.init({
                        slug: slug,
                        apiUrl: apiUrl,
                        container: container,
                        open: open
                    });
                }
            }
        });
    }

})(window, document);

