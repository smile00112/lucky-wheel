# Решение проблем с виджетом v2

## Проблема: 404 ошибки при загрузке файлов

### Симптомы
```
GET http://luckywheel.test/js/widget/app.js net::ERR_ABORTED 404 (Not Found)
GET http://luckywheel.test/css/widget/wheel.css net::ERR_ABORTED 404 (Not Found)
```

### Решение

#### Шаг 1: Проверьте наличие файлов

```bash
# Windows PowerShell
dir public\js\widget
dir public\css\widget

# Должны быть файлы:
# public/js/widget/app.js
# public/js/widget/config.js
# public/js/widget/api.js
# ... и другие модули
# public/css/widget/wheel.css
```

#### Шаг 2: Скопируйте файлы вручную

Если файлов нет, выполните:

**Windows:**
```cmd
copy-widget-assets.bat
```

Или вручную:
```cmd
mkdir public\css\widget
mkdir public\js\widget
copy resources\css\widget\wheel.css public\css\widget\wheel.css
xcopy /E /I /Y resources\js\widget\* public\js\widget\
```

**Linux/Mac:**
```bash
mkdir -p public/css/widget public/js/widget
cp resources/css/widget/wheel.css public/css/widget/wheel.css
cp -r resources/js/widget/* public/js/widget/
```

#### Шаг 3: Проверьте доступность файлов

Откройте в браузере напрямую:
- `http://luckywheel.test/css/widget/wheel.css`
- `http://luckywheel.test/js/widget/app.js`

Если файлы не открываются:
1. Проверьте права доступа к файлам
2. Проверьте настройки веб-сервера (Apache/Nginx)
3. Убедитесь, что `public` является корневой директорией

#### Шаг 4: Очистите кэш Laravel

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

#### Шаг 5: Проверьте настройки APP_URL

В файле `.env`:
```
APP_URL=http://luckywheel.test
```

#### Шаг 6: Проверьте веб-сервер

**Для Apache (.htaccess должен быть в public/):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

**Для Nginx:**
```nginx
root /path/to/LuckyWheel/public;
index index.php;
```

## Проблема: Модули ES6 не загружаются

### Симптомы
```
Failed to load module script: Expected a JavaScript or WASM module script
```

### Решение

1. Убедитесь, что все модули скопированы в `public/js/widget/`
2. Проверьте, что браузер поддерживает ES6 modules
3. Проверьте консоль на ошибки CORS

## Проблема: Стили не применяются

### Решение

1. Проверьте путь к CSS в шаблоне:
   ```blade
   <link rel="stylesheet" href="{{ asset('css/widget/wheel.css') }}">
   ```

2. Проверьте, что файл существует: `public/css/widget/wheel.css`

3. Очистите кэш браузера (Ctrl+F5)

## Альтернативное решение: Использование Vite

Если проблемы с прямыми путями, используйте Vite для сборки:

1. Обновите `vite.config.js`:
```javascript
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
    ],
});
```

2. В `wheel-v2.blade.php` замените:
```blade
@vite(['resources/css/widget/wheel.css', 'resources/js/widget/app.js'])
```

3. Запустите сборку:
```bash
npm run build
# или для разработки
npm run dev
```

## Проверка работоспособности

После копирования файлов:

1. Откройте `/widget/embed-v2/test` в браузере
2. Откройте консоль разработчика (F12)
3. Проверьте вкладку Network:
   - `wheel.css` должен загружаться (статус 200)
   - `app.js` должен загружаться (статус 200)
   - Все модули должны загружаться (статус 200)

4. Проверьте вкладку Console:
   - Не должно быть ошибок загрузки модулей
   - Не должно быть ошибок выполнения

## Быстрая проверка

Выполните в терминале проекта:

```bash
# Проверка файлов
ls -la public/js/widget/ | grep app.js
ls -la public/css/widget/ | grep wheel.css

# Проверка содержимого
head -5 public/js/widget/app.js
head -5 public/css/widget/wheel.css
```

Если файлы есть и содержат код - проблема в настройках веб-сервера или кэше.

