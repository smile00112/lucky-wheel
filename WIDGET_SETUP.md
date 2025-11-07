# Быстрый старт: Настройка виджета

## Шаги для запуска виджета

### 1. Создайте активное колесо

В административной панели Filament:
1. Создайте новое колесо
2. Установите `is_active = true`
3. Запомните `slug` колеса
4. Добавьте призы с вероятностями

### 2. Проверьте доступность файлов

Убедитесь, что файл виджета доступен:
- URL: `https://yourdomain.com/js/lucky-wheel-widget.js`
- Проверьте в браузере: откройте этот URL

### 3. Настройте CORS (если нужно)

Если виджет встраивается на другом домене, убедитесь, что:
- Middleware `WidgetCors` настроен правильно
- CORS заголовки отправляются корректно

### 4. Протестируйте виджет

Откройте файл `public/widget-example.html` и:
1. Замените `'your-wheel-slug'` на реальный slug
2. Замените `'http://localhost'` на ваш домен
3. Откройте файл в браузере

### 5. Встройте на целевой сайт

Добавьте код на целевую страницу:

```html
<div id="lucky-wheel-container"></div>

<script src="https://yourdomain.com/js/lucky-wheel-widget.js"></script>
<script>
  LuckyWheel.init({
    slug: 'your-wheel-slug',
    apiUrl: 'https://yourdomain.com/api/widget',
    container: '#lucky-wheel-container'
  });
</script>
```

## Структура файлов

```
app/
  Http/
    Controllers/
      WidgetController.php          # Контроллер API виджета
    Middleware/
      WidgetCors.php                # CORS middleware
  Models/
    Prize.php                       # Модель с методами для лимитов
    Guest.php                       # Модель гостя
    Wheel.php                       # Модель колеса
    Spin.php                        # Модель вращения

routes/
  api.php                           # API маршруты
  web.php                           # Веб-маршруты (включая embed)

resources/
  views/
    widget/
      wheel.blade.php              # Шаблон виджета для iframe

public/
  js/
    lucky-wheel-widget.js          # JavaScript виджет-скрипт
  widget-example.html              # Пример использования
```

## API Endpoints

### Веб-маршруты
- `GET /widget/embed/{slug}` - Страница виджета для iframe

### API маршруты
- `GET /api/widget/wheel/{slug}` - Данные колеса (JSON)
- `POST /api/widget/guest` - Создание/получение гостя
- `POST /api/widget/spin` - Выполнение вращения
- `GET /api/widget/guest/{guestId}/spins` - История вращений

## Важные моменты

1. **Гости идентифицируются через localStorage** - ID гостя хранится в `localStorage` под ключом `lucky_wheel_guest_id`

2. **Лимиты контролируются на сервере**:
   - Лимит вращений на колесо (`spins_limit`)
   - Лимит призов (`quantity_limit`)
   - Дневной лимит призов (`quantity_day_limit`)
   - Лимит призов для гостя (`quantity_guest_limit`)

3. **Временные ограничения**:
   - `starts_at` - начало активности колеса
   - `ends_at` - окончание активности колеса

4. **Выбор приза**:
   - Учитываются все лимиты
   - Используются вероятности призов
   - Нормализация вероятностей для доступных призов

## Отладка

### Консоль браузера
Откройте Developer Tools (F12) и проверьте:
- Ошибки загрузки скрипта
- Ошибки CORS
- Ошибки API запросов

### Сетевые запросы
В Developer Tools → Network проверьте:
- Запросы к `/api/widget/*`
- Статус ответов (200, 404, 403, 500)
- Содержимое ответов

### Логи сервера
Проверьте `storage/logs/laravel.log` на наличие ошибок.

## Примеры ошибок

### "Wheel not found"
- Проверьте slug колеса
- Убедитесь, что колесо активно (`is_active = true`)
- Проверьте временные ограничения

### "Spin limit reached"
- Пользователь исчерпал лимит вращений
- Это нормальное поведение

### "CORS error"
- Проверьте настройки CORS middleware
- Убедитесь, что `WidgetCors` применен к маршрутам
- Проверьте заголовки ответа

### "Guest not found"
- Проверьте создание гостя через API
- Убедитесь, что `guest_id` передается в iframe

## Безопасность

1. **Валидация данных** - Все данные валидируются на сервере
2. **Лимиты** - Все лимиты проверяются на сервере
3. **CORS** - Настроен для безопасного взаимодействия
4. **XSS защита** - Данные экранируются в Blade шаблонах

## Поддержка

При возникновении проблем:
1. Проверьте логи сервера
2. Проверьте консоль браузера
3. Проверьте сетевые запросы
4. Убедитесь, что все файлы на месте

