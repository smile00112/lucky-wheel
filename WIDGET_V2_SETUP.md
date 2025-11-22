# Настройка виджета v2 (модульная версия)

## Быстрый старт

### Вариант 1: Прямое копирование (для тестирования)

```bash
# Создать директории
mkdir -p public/css/widget
mkdir -p public/js/widget

# Копировать CSS
cp resources/css/widget/wheel.css public/css/widget/wheel.css

# Копировать JS модули
cp -r resources/js/widget/* public/js/widget/
```

### Вариант 2: Настройка Vite (рекомендуется)

Добавьте в `vite.config.js`:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/widget/wheel.css',
                'resources/js/widget/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name][extname]';
                    }
                    return 'assets/[name][extname]';
                }
            }
        }
    }
});
```

Затем в `wheel-v2.blade.php` используйте:

```blade
@vite(['resources/css/widget/wheel.css', 'resources/js/widget/app.js'])
```

Вместо:
```blade
<link rel="stylesheet" href="{{ asset('css/widget/wheel.css') }}">
<script type="module" src="{{ asset('js/widget/app.js') }}"></script>
```

## Использование

### Доступ к новой версии

```
/widget/embed-v2/{slug}
```

### Переключение на новую версию по умолчанию

В `app/Http/Controllers/WidgetController.php`:

```php
public function embed(string $slug)
{
    // ... код проверок ...
    return view('widget.wheel-v2', compact('wheel')); // Изменить на v2
}
```

## Структура модулей

```
resources/js/widget/
├── app.js              # Главный файл - инициализация приложения
├── config.js           # Конфигурация (API URL, wheel slug)
├── api.js              # API сервис
├── state.js            # Управление состоянием
├── utils.js            # Утилиты
├── wheel-renderer.js   # Отрисовка колеса
├── wheel-animation.js  # Анимация
├── wheel-controller.js # Контроллер колеса
├── image-loader.js     # Загрузка изображений
├── notification.js     # Уведомления
└── form-handler.js     # Обработка форм
```

## Проверка работоспособности

1. Откройте `/widget/embed-v2/{slug}` в браузере
2. Откройте консоль разработчика (F12)
3. Проверьте:
   - Нет ошибок загрузки модулей
   - Колесо отображается
   - Кнопка "Крутить колесо!" работает
   - Уведомления показываются корректно

## Решение проблем

### Модули не загружаются

**Проблема**: `Failed to load module script`

**Решение**: 
- Убедитесь, что файлы скопированы в `public/js/widget/`
- Проверьте права доступа к файлам
- Проверьте пути в браузере (Network tab)

### Стили не применяются

**Проблема**: Виджет без стилей

**Решение**:
- Проверьте путь к CSS файлу
- Убедитесь, что файл `public/css/widget/wheel.css` существует
- Очистите кэш браузера

### Ошибки в консоли

**Проблема**: JavaScript ошибки

**Решение**:
- Проверьте, что все модули на месте
- Проверьте синтаксис ES6 (должен поддерживаться браузером)
- Используйте современный браузер с поддержкой ES6 modules

## Откат на старую версию

Если нужно вернуться к старой версии:

```php
// В WidgetController::embed()
return view('widget.wheel', compact('wheel')); // Старая версия
```

Или используйте роут:
```
/widget/embed/{slug}  # Старая версия
```

