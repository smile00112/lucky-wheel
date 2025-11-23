# Widget JavaScript Modules

Все модули виджета должны быть в этой директории.

## Структура

- `app.js` - главный файл инициализации
- `config.js` - конфигурация
- `api.js` - API сервис
- `state.js` - управление состоянием
- `utils.js` - утилиты
- `wheel-renderer.js` - отрисовка колеса
- `wheel-animation.js` - анимация
- `wheel-controller.js` - контроллер
- `image-loader.js` - загрузка изображений
- `notification.js` - уведомления
- `form-handler.js` - обработка форм

## Обновление

После изменения файлов в `resources/js/widget/` скопируйте их сюда:

```bash
# Windows
xcopy /E /I /Y resources\js\widget\* public\js\widget\

# Linux/Mac
cp -r resources/js/widget/* public/js/widget/
```

Или используйте скрипт `copy-widget-assets.bat`

