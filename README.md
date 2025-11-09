# LuckyWheel - Laravel Project

Проект на Laravel 12 с набором инструментов для разработки и администрирования.

## Установленные пакеты

### Основные пакеты

#### 1. **Filament 4** - Админ-панель
- **Версия:** 4.2.0
- **Описание:** Современная админ-панель для Laravel на основе Livewire
- **Доступ:** `/admin`
- **Документация:** https://filamentphp.com/docs

#### 2. **Laravel Debugbar** - Отладка
- **Версия:** 3.16.0
- **Описание:** Панель отладки для Laravel с информацией о запросах, запросах к БД, логах и т.д.
- **Доступ:** Автоматически отображается внизу страницы в режиме разработки
- **Документация:** https://github.com/barryvdh/laravel-debugbar

#### 3. **Laravel Log Viewer** - Просмотр логов
- **Версия:** 2.5.0
- **Описание:** Веб-интерфейс для просмотра логов Laravel
- **Доступ:** `/logs`
- **Документация:** https://github.com/rap2hpoutre/laravel-log-viewer

#### 4. **L5-Swagger** - Swagger/OpenAPI документация
- **Версия:** 9.0.1
- **Описание:** Генерация и отображение Swagger документации для API
- **Доступ:** `/api/documentation` (после настройки)
- **Документация:** https://github.com/DarkaOnLine/L5-Swagger

#### 5. **Laravel Telescope** - Мониторинг приложения
- **Версия:** 5.15.0
- **Описание:** Мощный инструмент для отладки и мониторинга Laravel приложений
- **Доступ:** `/telescope`
- **Документация:** https://laravel.com/docs/telescope

## Быстрый старт

### Установка зависимостей
```bash
composer install
```

### Настройка окружения
```bash
cp .env.example .env
php artisan key:generate
```

### Запуск миграций
```bash
php artisan migrate
```

### Создание пользователя для Filament
```bash
php artisan make:filament-user
```

### Генерация Swagger документации
```bash
php artisan l5-swagger:generate
```

## Доступ к инструментам

| Инструмент | URL | Описание |
|------------|-----|----------|
| Filament Admin | `/admin` | Админ-панель приложения |
| Telescope | `/telescope` | Мониторинг запросов, запросов к БД, очередей и т.д. |
| Log Viewer | `/logs` | Просмотр логов приложения |
| Swagger API Docs | `/api/documentation` | Документация API (после настройки) |
| Debugbar | Автоматически | Панель отладки внизу страницы (только в dev режиме) |

## Рекомендуемые дополнительные пакеты

Для дальнейшей разработки рекомендуется установить:

```bash
# Управление ролями и правами
composer require spatie/laravel-permission

# Работа с изображениями
composer require intervention/image

# Импорт/экспорт Excel
composer require maatwebsite/excel

# Автодополнение для IDE
composer require --dev barryvdh/laravel-ide-helper

# Статический анализ кода
composer require --dev nunomaduro/larastan

# Логирование активности
composer require spatie/laravel-activitylog

# Резервное копирование
composer require spatie/laravel-backup

# Мониторинг очередей (если используется Redis)
composer require laravel/horizon
```

## Версии

- **Laravel:** 12.37.0
- **PHP:** 8.3.16
- **Filament:** 4.2.0

## Полезные команды

```bash
# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Генерация IDE помощника
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta

# Запуск очередей
php artisan queue:work

# Запуск Telescope watcher (для мониторинга в реальном времени)
php artisan telescope:prune
```

## Примечания

- Debugbar работает только в режиме разработки (`APP_DEBUG=true`)
- Telescope рекомендуется использовать только в режиме разработки
- Для работы Swagger необходимо настроить аннотации в контроллерах API
- Log Viewer доступен по умолчанию, но рекомендуется ограничить доступ в production

## Лицензия

Проект создан для разработки.






