# Рефакторинг компонента колеса

## Обзор

Произведен рефакторинг компонента колеса с разделением на модули, упрощением кода и улучшением структуры.

## Структура новой версии

### JavaScript модули

```
resources/js/widget/
├── app.js              # Главный инициализационный файл
├── config.js           # Конфигурация (API URL, wheel slug, guest ID)
├── api.js              # API сервис для работы с бэкендом
├── state.js            # Управление состоянием приложения
├── utils.js            # Утилиты (форматирование телефона, копирование и т.д.)
├── wheel-renderer.js   # Отрисовка колеса на canvas
├── wheel-animation.js  # Анимация вращения колеса
├── wheel-controller.js # Основной контроллер колеса
├── image-loader.js      # Загрузка изображений призов
├── notification.js     # Управление уведомлениями о выигрыше
└── form-handler.js     # Обработка формы получения приза
```

### Стили

```
resources/css/widget/
└── wheel.css           # Все стили вынесены в отдельный файл
```

### Шаблоны

```
resources/views/widget/
├── wheel.blade.php     # Старая версия (оригинал)
└── wheel-v2.blade.php  # Новая модульная версия
```

## Преимущества новой версии

### 1. Модульность
- Код разделен на логические модули
- Каждый модуль отвечает за свою область
- Легко тестировать и поддерживать

### 2. Упрощение
- Главный компонент упрощен до минимума
- Сложные функции разбиты на более мелкие
- Улучшена читаемость кода

### 3. Управление состоянием
- Централизованное управление состоянием через `StateManager`
- Подписка на изменения состояния
- Единая точка доступа к данным

### 4. Разделение ответственности
- API запросы → `ApiService`
- Отрисовка → `WheelRenderer`
- Анимация → `WheelAnimation`
- Уведомления → `NotificationManager`
- Формы → `FormHandler`

### 5. Упрощенный Blade шаблон
- Стили вынесены в отдельный CSS файл
- JavaScript вынесен в модули
- Минимальный HTML разметка

## Переключение между версиями

### Использование старой версии (по умолчанию)

```php
// routes/web.php
Route::get('/widget/embed/{slug}', [WidgetController::class, 'embed'])
    ->name('widget.embed.web');
```

Использует шаблон: `resources/views/widget/wheel.blade.php`

### Использование новой версии (v2)

```php
// routes/web.php
Route::get('/widget/embed-v2/{slug}', [WidgetController::class, 'embedV2'])
    ->name('widget.embed.v2');
```

Использует шаблон: `resources/views/widget/wheel-v2.blade.php`

### Переключение в контроллере

Чтобы переключить основную версию на v2, измените метод `embed`:

```php
public function embed(string $slug)
{
    // ... проверки ...
    return view('widget.wheel-v2', compact('wheel')); // вместо 'widget.wheel'
}
```

## Сборка и публикация

### Для разработки

Модули используют ES6 imports, поэтому нужна сборка через Vite или другой бандлер.

### Добавление в vite.config.js

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/widget/app.js', // Добавить виджет
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
            }
        }
    }
});
```

### Альтернатива: прямая публикация

Если не используете сборку, можно скопировать файлы напрямую:

```bash
# Копирование CSS
cp resources/css/widget/wheel.css public/css/widget/wheel.css

# Копирование JS модулей
mkdir -p public/js/widget
cp -r resources/js/widget/* public/js/widget/
```

## Миграция на новую версию

### Шаг 1: Тестирование

1. Откройте `/widget/embed-v2/{slug}` в браузере
2. Проверьте все функции:
   - Вращение колеса
   - Отображение выигрыша
   - Форма получения приза
   - Копирование кода
   - Отправка приза на email

### Шаг 2: Переключение

После успешного тестирования:

1. Измените метод `embed` в `WidgetController`:
   ```php
   return view('widget.wheel-v2', compact('wheel'));
   ```

2. Или переименуйте файлы:
   ```bash
   mv resources/views/widget/wheel.blade.php resources/views/widget/wheel-old.blade.php
   mv resources/views/widget/wheel-v2.blade.php resources/views/widget/wheel.blade.php
   ```

### Шаг 3: Очистка (опционально)

После полной миграции можно удалить старую версию:
- `resources/views/widget/wheel-old.blade.php` (если переименовали)
- Старый inline JavaScript из шаблона (уже вынесен в модули)

## API совместимость

Новая версия полностью совместима со старым API. Все эндпоинты остаются без изменений:
- `POST /api/widget/guest`
- `GET /api/widget/wheel/{slug}`
- `POST /api/widget/spin`
- `GET /api/widget/wheel/{slug}/today-win`
- `POST /api/widget/guest/{id}/claim-prize`
- `POST /api/widget/spin/{id}/send-email`

## Отличия в реализации

### Старая версия
- Монолитный JavaScript файл (~1700 строк)
- Inline стили в Blade шаблоне
- Глобальные переменные
- Смешанная логика

### Новая версия
- Модульная структура (11 файлов)
- Отдельный CSS файл
- ES6 модули
- Разделение ответственности
- Централизованное управление состоянием

## Поддержка

При возникновении проблем:
1. Проверьте консоль браузера на ошибки
2. Убедитесь, что все модули загружаются
3. Проверьте пути к файлам CSS и JS
4. Сравните с рабочей старой версией

