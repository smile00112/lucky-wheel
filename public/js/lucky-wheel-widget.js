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
            modal: null,
            floatingIcon: null,
            isModalOpen: false,
            open: false,
            version: 'v3',
            scrollPosition: undefined,
            preventScrollHandler: null,
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

            // Проверка активности колеса
            const checkWheelActive = () => {
                return fetch(`${this.config.apiUrl}/wheel/${this.config.slug}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 404) {
                            throw new Error('Wheel is not active or not found');
                        }
                        return response.json().then(errorData => {
                            throw new Error(errorData.error || errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                        }).catch(() => {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Колесо активно, продолжаем инициализацию
                    return true;
                })
                .catch(error => {
                    console.error('LuckyWheel: Wheel is not active', error);
                    if (this.config.callbacks.onError) {
                        this.config.callbacks.onError(error);
                    }
                    throw error;
                });
            };

            // Проверяем активность колеса перед инициализацией
            checkWheelActive()
                .then(() => {
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
                            const isMainPage = window.location.pathname === '/';
                            
                            // На главной странице всегда открываем, если задан параметр open
                            if (isMainPage && shouldOpen) {
                                this.openModal();
                                return;
                            }
                            
                            // Для остальных страниц проверяем localStorage
                            const storedOpen = localStorage.getItem('lucky_wheel_modal_open') === 'true';
                            const hasStoredOpen = localStorage.getItem('lucky_wheel_modal_open');
                            
                            // Проверяем, не истекло ли 15 минут с момента закрытия
                            const closedTime = localStorage.getItem('lucky_wheel_modal_closed_time');
                            if (closedTime) {
                                const timeDiff = Date.now() - parseInt(closedTime);
                                const fifteenMinutes = 15 * 60 * 1000; // 15 минут в миллисекундах
                                if (timeDiff >= fifteenMinutes) {
                                    // Сбрасываем состояние, если прошло 15 минут
                                    localStorage.removeItem('lucky_wheel_modal_open');
                                    localStorage.removeItem('lucky_wheel_modal_closed_time');
                                    // После сброса открываем, если задан параметр
                                    if (shouldOpen) {
                                        this.openModal();
                                    }
                                    return;
                                }
                            }
                            
                            // Стандартная логика для остальных случаев
                            if (shouldOpen && !hasStoredOpen) {
                                this.openModal();
                            } else if (shouldOpen && hasStoredOpen && !storedOpen) {
                                // не открываем
                            } else if (shouldOpen && storedOpen) {
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
                })
                .catch((error) => {
                    // Колесо неактивно, прерываем работу
                    console.error('LuckyWheel: Initialization aborted - wheel is not active', error);
                    return;
                });
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
                        /*max-width: 600px;*/
                        width: 100%;
                        max-height: 90vh;
                        position: relative;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        animation: lucky-wheel-slideUp 0.3s ease;
                        overflow: hidden;
                        height: 100%;
                        max-height: 750px;
                      background: none;
                      box-shadow: none;
                    }
                    #lucky-wheel-modal-close {
                        position: absolute;
                        top: 15px;
                        right: 15px;
                        width: 35px;
                        height: 35px;
                        /* background: rgba(0, 0, 0, 0.1); */
                        border: none;
                        border-radius: 50%;
                        cursor: pointer;
                        z-index: 10000;
                        display: flex
                    ;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s
                    ease;
                        flex-direction: column;
                        font-size: 20px;
                        line-height: 1;
                        /* color: white; */
                        font-weight: 100;
                        padding: 0;
                        background: round;
                        color: #878787;
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
                        overflow-y: auto;
                        overflow-x: hidden;
                        overflow: hidden;
                        display: flex;
                        justify-content: center;
                        align-items: center;

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
                    @media screen and (max-width: 768px) {
                        #lucky-wheel-modal-overlay.open {
                            height: 100vh;
                            height: -webkit-fill-available;
                        }
                    }
                  @media (max-width: 480px) {
                    #lucky-wheel-modal-content {
                        min-height: auto !important;
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

            const content = document.getElementById('lucky-wheel-modal-content');

            // Загружаем контент колеса
            this.loadWheelContent(content)
                .then(() => {
                    if (this.config.callbacks.onLoad) {
                        this.config.callbacks.onLoad();
                    }
                })
                .catch((error) => {
                    console.error('LuckyWheel: Failed to load wheel content', error);
                    if (this.config.callbacks.onError) {
                        this.config.callbacks.onError(error);
                    }
                });

            // Показываем модальное окно
            this.config.modal.classList.add('open');
            this.config.isModalOpen = true;
            this.lockBodyScroll(); // Блокируем скролл страницы (включая iOS)
            this.hideIOSAddressBar(); // Скрываем адресную строку на iOS

            // Сохраняем состояние открытия в localStorage
            localStorage.setItem('lucky_wheel_modal_open', 'true');
            localStorage.removeItem('lucky_wheel_modal_closed_time'); // Удаляем timestamp закрытия
        },

        /**
         * Загрузить контент колеса
         */
        loadWheelContent: function (container) {
            return new Promise((resolve, reject) => {
                const version = this.config.version || 'v3';
                const embedPath = version === 'v3' ? '/widget/embed-v3' : '/widget/embed';
                const embedUrl = this.config.apiUrl.replace('/api/widget', embedPath);
                const guestParam = this.config.guestId ? `&guest_id=${this.config.guestId}` : '';
                const url = `${embedUrl}/${this.config.slug}?content_only=true${guestParam}`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        // Очищаем контейнер
                        container.innerHTML = '';

                        // Создаем временный контейнер для парсинга HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;

                        // Извлекаем контент из div.lucky-wheel-content или body
                        let wheelContent = tempDiv.querySelector('.lucky-wheel-content');
                        if (!wheelContent) {
                            wheelContent = tempDiv;
                        }

                        // Клонируем и добавляем контент
                        const contentClone = wheelContent.cloneNode(true);
                        container.appendChild(contentClone);

                        // Выполняем скрипты из загруженного контента
                        const scripts = Array.from(contentClone.querySelectorAll('script'));
                        const scriptPromises = [];

                        scripts.forEach((oldScript, index) => {
                            // Удаляем старый скрипт
                            oldScript.remove();

                            if (oldScript.src) {
                                // Для модульных скриптов (type="module") всегда загружаем заново при повторной загрузке контента
                                const isModule = oldScript.getAttribute('type') === 'module';
                                const existingScript = document.querySelector(`script[src="${oldScript.src}"]`);

                                // Для модульных скриптов или если скрипт еще не загружен
                                if (isModule || !existingScript) {
                                    // Если скрипт уже есть, но это модуль - удаляем старый для повторной загрузки
                                    if (isModule && existingScript) {
                                        existingScript.remove();
                                    }

                                    const newScript = document.createElement('script');
                                    if (oldScript.getAttribute('type')) {
                                        newScript.setAttribute('type', oldScript.getAttribute('type'));
                                    }
                                    if (oldScript.noModule) {
                                        newScript.noModule = true;
                                    }
                                    newScript.src = oldScript.src;
                                    newScript.async = oldScript.async || false;
                                    newScript.defer = oldScript.defer || false;

                                    const scriptPromise = new Promise((resolveScript) => {
                                        newScript.onload = resolveScript;
                                        newScript.onerror = resolveScript; // Продолжаем даже при ошибке
                                    });

                                    container.appendChild(newScript);
                                    scriptPromises.push(scriptPromise);
                                } else {
                                    // Скрипт уже загружен и не модуль - просто резолвим промис
                                    scriptPromises.push(Promise.resolve());
                                }
                            } else {
                                // Для inline скриптов - заменяем const/let на var, чтобы избежать ошибок повторного объявления
                                const scriptContent = oldScript.textContent;
                                if (scriptContent.trim()) {
                                    try {
                                        // Заменяем const и let на var для глобальных объявлений
                                        // Это позволяет переопределять переменные без ошибок
                                        let processedScript = scriptContent;

                                        // Заменяем const на var (только для объявлений в начале строки или после точки с запятой)
                                        processedScript = processedScript.replace(/\bconst\s+(\w+)\s*=/g, 'var $1 =');

                                        // Заменяем let на var (только для объявлений в начале строки или после точки с запятой)
                                        processedScript = processedScript.replace(/\blet\s+(\w+)\s*=/g, 'var $1 =');

                                        // Заменяем DOMContentLoaded на немедленный вызов
                                        // Вариант 1: простая замена
                                        processedScript = processedScript.replace(
                                            /document\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*async\s+function\s*\(\)\s*\{/g,
                                            '(async function() {'
                                        );
                                        // Вариант 2: если DOMContentLoaded уже произошел, вызываем функцию сразу
                                        if (document.readyState === 'complete' || document.readyState === 'interactive') {
                                            processedScript = processedScript.replace(
                                                /document\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\(\)\s*\{/g,
                                                '(function() {'
                                            );
                                        }

                                        const scriptElement = document.createElement('script');
                                        scriptElement.textContent = processedScript;
                                        container.appendChild(scriptElement);
                                        // Удаляем после выполнения
                                        setTimeout(() => {
                                            if (scriptElement.parentNode) {
                                                scriptElement.remove();
                                            }
                                        }, 0);
                                    } catch (e) {
                                        console.warn('LuckyWheel: Failed to process script', e);
                                    }
                                }
                            }
                        });

                        // Ждем загрузки внешних скриптов и выполнения inline скриптов
                        Promise.all(scriptPromises).then(() => {
                            // Даем время на выполнение всех скриптов
                            setTimeout(() => {
                                // Для версии v3 используем модульную систему
                                if (version === 'v3' && typeof window.reinitializeLuckyWheel === 'function') {
                                    console.log('LuckyWheel: Reinitializing v3 widget');
                                    window.reinitializeLuckyWheel();
                                    resolve();
                                    return;
                                }

                                // Инициализируем колесо вручную, если DOMContentLoaded уже произошел
                                if (typeof createOrGetGuest === 'function' && typeof loadWheelData === 'function') {
                                    // Вызываем инициализацию
                                    (async function() {
                                        try {
                                            // Устанавливаем GUEST_ID из конфига виджета
                                            if (this.config.guestId && typeof window !== 'undefined') {
                                                window.GUEST_ID = this.config.guestId.toString();
                                            }

                                            // Проверяем guest_id из URL или используем из конфига
                                            let guestId = new URLSearchParams(window.location.search).get('guest_id');
                                            if (!guestId && this.config.guestId) {
                                                guestId = this.config.guestId.toString();
                                                // Устанавливаем в window для использования в скриптах
                                                if (typeof window !== 'undefined') {
                                                    window.GUEST_ID = guestId;
                                                }
                                            }

                                            if (!guestId && typeof createOrGetGuest === 'function') {
                                                guestId = await createOrGetGuest();
                                                if (guestId && typeof window !== 'undefined') {
                                                    window.GUEST_ID = guestId;
                                                }
                                            }

                                            if (guestId) {
                                                // Применяем маску для телефона, если есть
                                                const phoneInput = container.querySelector('#winNotificationPhone');
                                                if (phoneInput && typeof applyPhoneMask === 'function') {
                                                    applyPhoneMask(phoneInput);
                                                }

                                                // Проверяем выигрыш сегодня
                                                if (typeof checkTodayWin === 'function') {
                                                    checkTodayWin();
                                                }

                                                // Загружаем данные колеса
                                                if (typeof loadWheelData === 'function') {
                                                    loadWheelData();
                                                }
                                            }
                                        } catch (e) {
                                            console.error('LuckyWheel: Initialization error', e);
                                        }
                                    }.bind(this))();
                                } else if (typeof loadWheelData === 'function') {
                                    // Если функции инициализации нет, просто вызываем loadWheelData
                                    loadWheelData();
                                }
                                resolve();
                            }, 200);
                        }).catch(() => {
                            // Продолжаем даже при ошибках
                            setTimeout(() => {
                                // Для версии v3 пытаемся переинициализировать
                                if (version === 'v3' && typeof window.reinitializeLuckyWheel === 'function') {
                                    window.reinitializeLuckyWheel();
                                } else if (typeof loadWheelData === 'function') {
                                    loadWheelData();
                                }
                                resolve();
                            }, 200);
                        });
                    })
                    .catch(error => {
                        console.error('LuckyWheel: Error loading wheel content', error);
                        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Ошибка загрузки колеса</div>';
                        reject(error);
                    });
            });
        },

        /**
         * Закрыть модальное окно
         */
        closeModal: function () {
            if (this.config.modal) {
                this.config.modal.classList.remove('open');
                this.config.isModalOpen = false;
                this.unlockBodyScroll(); // Восстанавливаем скролл

                // Очищаем контент при закрытии
                const content = document.getElementById('lucky-wheel-modal-content');
                if (content) {
                    content.innerHTML = '';
                }

                // Сохраняем состояние закрытия в localStorage
                localStorage.setItem('lucky_wheel_modal_open', 'false');
                localStorage.setItem('lucky_wheel_modal_closed_time', Date.now().toString());
            }
        },

        /**
         * Заблокировать скролл страницы (включая iOS)
         */
        lockBodyScroll: function () {
            const scrollY = window.scrollY || window.pageYOffset;
            const body = document.body;
            
            // Сохраняем позицию скролла
            this.config.scrollPosition = scrollY;
            
            // Блокируем скролл через CSS
            body.style.overflow = 'hidden';
            body.style.position = 'fixed';
            body.style.top = `-${scrollY}px`;
            body.style.width = '100%';
            
            // Блокируем touchmove на iOS
            const preventScroll = (e) => {
                // Разрешаем скролл внутри модального окна
                const modal = this.config.modal;
                if (modal && modal.contains(e.target)) {
                    return;
                }
                e.preventDefault();
            };
            
            this.config.preventScrollHandler = preventScroll;
            document.addEventListener('touchmove', preventScroll, { passive: false });
        },

        /**
         * Разблокировать скролл страницы
         */
        unlockBodyScroll: function () {
            const body = document.body;
            
            // Восстанавливаем стили
            body.style.overflow = '';
            body.style.position = '';
            body.style.top = '';
            body.style.width = '';
            
            // Удаляем обработчик touchmove
            if (this.config.preventScrollHandler) {
                document.removeEventListener('touchmove', this.config.preventScrollHandler);
                this.config.preventScrollHandler = null;
            }
            
            // Восстанавливаем позицию скролла
            if (this.config.scrollPosition !== undefined) {
                window.scrollTo(0, this.config.scrollPosition);
                this.config.scrollPosition = undefined;
            }
        },

        /**
         * Скрыть адресную строку на iOS
         */
        hideIOSAddressBar: function () {
            // Проверяем, что это iOS устройство
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            
            if (isIOS) {
                // Сохраняем текущую позицию скролла
                const scrollY = window.scrollY || window.pageYOffset;
                
                // Принудительно скрываем адресную строку через небольшой скролл
                setTimeout(() => {
                    // Метод 1: Используем scrollTo для скрытия адресной строки
                    window.scrollTo(0, scrollY + 1);
                    setTimeout(() => {
                        window.scrollTo(0, scrollY);
                    }, 10);
                    
                    // Метод 2: Альтернативный способ через visualViewport (если доступен)
                    if (window.visualViewport) {
                        window.scrollTo(0, window.visualViewport.height);
                        setTimeout(() => {
                            window.scrollTo(0, scrollY);
                        }, 10);
                    }
                }, 100);
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
         * Выполнить вращение (можно вызвать извне)
         */
        spin: function () {
            // Реализация вращения без iframe
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

            // Удаляем стили
            const iconStyles = document.getElementById('lucky-wheel-icon-styles');
            if (iconStyles) {
                iconStyles.remove();
            }
            const modalStyles = document.getElementById('lucky-wheel-modal-styles');
            if (modalStyles) {
                modalStyles.remove();
            }

            this.config.modal = null;
            this.config.floatingIcon = null;
            this.config.isModalOpen = false;
            this.unlockBodyScroll();
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

