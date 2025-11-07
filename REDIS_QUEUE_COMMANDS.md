# Команды Redis CLI для работы с очередями Laravel

## Как узнать сколько осталось времени до выполнения job

### 1. Найти ключи очередей

Сначала нужно найти ключи, где хранятся задачи. Laravel использует следующие паттерны:

```bash
# Показать все ключи очередей
KEYS *queues*

# Или с учетом префикса (замените YOUR_PREFIX на ваш префикс из config/database.php)
KEYS YOUR_PREFIX*queues*
```

### 2. Отложенные задачи (Delayed Jobs)

Отложенные задачи хранятся в **sorted set** с ключом вида:
```
{prefix}queues:{queue_name}:delayed
```

#### Посмотреть все отложенные задачи с временем выполнения:

```bash
# Получить все задачи с их timestamp (score)
ZRANGE {prefix}queues:{queue_name}:delayed 0 -1 WITHSCORES

# Пример (если префикс "luckywheel-database-" и очередь "default"):
ZRANGE luckywheel-database-queues:default:delayed 0 -1 WITHSCORES
```

#### Вычислить время до выполнения:

```bash
# 1. Получить текущий Unix timestamp
TIME

# 2. Получить timestamp задачи (score из sorted set)
ZSCORE {prefix}queues:{queue_name}:delayed {job_id}

# 3. Вычислить разницу (в секундах)
# Вычтите текущее время из timestamp задачи
```

#### Пример полной команды для одной задачи:

```bash
# Получить timestamp задачи
ZSCORE luckywheel-database-queues:default:delayed "job_payload_string"

# Получить текущее время
TIME

# Разница = timestamp_задачи - текущее_время (в секундах)
```

### 3. Обычные задачи в очереди

Обычные задачи хранятся в списке:
```
{prefix}queues:{queue_name}
```

```bash
# Посмотреть длину очереди
LLEN {prefix}queues:{queue_name}

# Посмотреть задачи (без удаления)
LRANGE {prefix}queues:{queue_name} 0 -1
```

### 4. Резервные задачи (Reserved Jobs)

Зарезервированные задачи хранятся в sorted set:
```
{prefix}queues:{queue_name}:reserved
```

```bash
# Посмотреть зарезервированные задачи
ZRANGE {prefix}queues:{queue_name}:reserved 0 -1 WITHSCORES
```

### 5. Полезные команды для мониторинга

```bash
# Подсчитать количество отложенных задач
ZCARD {prefix}queues:{queue_name}:delayed

# Получить ближайшую задачу (с минимальным score = ближайшее время)
ZRANGE {prefix}queues:{queue_name}:delayed 0 0 WITHSCORES

# Получить все задачи, которые должны выполниться в ближайшие 5 минут
# (замените CURRENT_TIME на текущий Unix timestamp + 300 секунд)
ZRANGEBYSCORE {prefix}queues:{queue_name}:delayed CURRENT_TIME (CURRENT_TIME+300) WITHSCORES
```

### 6. Скрипт для автоматического расчета

Создайте Lua скрипт или используйте команды:

```bash
# В Redis CLI можно использовать:
EVAL "local now = redis.call('TIME')[1]; local job_time = redis.call('ZSCORE', KEYS[1], ARGV[1]); if job_time then return job_time - now; else return nil; end" 1 {prefix}queues:{queue_name}:delayed {job_id}
```

### 7. Определение префикса

Префикс обычно формируется из `APP_NAME` в `.env` файле:
- Формат: `{app-name}-database-`
- Пример: если `APP_NAME=LuckyWheel`, то префикс будет `luckywheel-database-`

Проверить можно командой:
```bash
KEYS *queues*delayed
```

## Примеры использования

### Пример 1: Найти все отложенные задачи с временем

```bash
# 1. Подключиться к Redis
redis-cli

# 2. Найти ключи очередей
KEYS *queues*delayed

# 3. Посмотреть задачи с временем
ZRANGE luckywheel-database-queues:default:delayed 0 -1 WITHSCORES

# 4. Получить текущее время
TIME

# 5. Вычислить: timestamp_задачи - текущее_время = секунды до выполнения
```

### Пример 2: Найти ближайшую задачу

```bash
# Получить ближайшую задачу (первую в sorted set)
ZRANGE luckywheel-database-queues:default:delayed 0 0 WITHSCORES
```

### Пример 3: Задачи, которые выполнятся в течение часа

```bash
# Получить текущий timestamp
TIME
# Предположим, получили: 1700000000

# Получить задачи на следующий час (3600 секунд)
ZRANGEBYSCORE luckywheel-database-queues:default:delayed 1700000000 1700003600 WITHSCORES
```

## Как посмотреть содержимое задачи (что это за задача)

### Просмотр содержимого отложенной задачи:

```bash
# 1. Получить все задачи с их данными
ZRANGE queues:whatsapp-mailing:delayed 0 -1 WITHSCORES

# 2. Получить конкретную задачу (первая задача)
ZRANGE queues:whatsapp-mailing:delayed 0 0

# 3. Payload задачи - это сериализованный JSON, который содержит:
#    - displayName: имя класса Job
#    - job: полное имя класса
#    - data: данные задачи
#    - uuid: уникальный идентификатор
```

### Пример для вашей задачи:

```bash
# Посмотреть все задачи в очереди whatsapp-mailing
ZRANGE queues:whatsapp-mailing:delayed 0 -1 WITHSCORES

# Посмотреть первую задачу (ближайшую)
ZRANGE queues:whatsapp-mailing:delayed 0 0

# Получить количество задач
ZCARD queues:whatsapp-mailing:delayed
```

### Расшифровка payload:

Payload задачи в Laravel - это JSON строка, которая содержит:
- `displayName` - отображаемое имя задачи
- `job` - полное имя класса Job (например, `App\Jobs\SendWhatsAppMessage`)
- `data` - данные задачи (параметры, которые передаются в Job)
- `uuid` - уникальный идентификатор задачи
- `maxTries`, `maxExceptions`, `timeout` - настройки выполнения

### Пример команды для просмотра:

```bash
# В Redis CLI
ZRANGE queues:whatsapp-mailing:delayed 0 0

# Вы получите строку вида:
# "{\"uuid\":\"...\",\"displayName\":\"App\\\\Jobs\\\\SendWhatsAppMessage\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{...}}"

# Эту строку можно скопировать и распарсить в JSON viewer или использовать jq:
# echo 'PAYLOAD_STRING' | jq .
```

### Альтернативный способ через Laravel:

```bash
# Использовать Laravel Tinker для просмотра задач
php artisan tinker

# В Tinker:
use Illuminate\Support\Facades\Redis;
$jobs = Redis::zrange('queues:whatsapp-mailing:delayed', 0, -1, 'WITHSCORES');
foreach($jobs as $payload => $score) {
    $data = json_decode($payload, true);
    echo "Job: " . ($data['displayName'] ?? 'Unknown') . "\n";
    echo "Time: " . date('Y-m-d H:i:s', $score) . "\n";
    echo "---\n";
}
```

## Примечания

- **Score в sorted set** - это Unix timestamp (секунды с 1 января 1970)
- **Разница** между score и текущим временем = секунды до выполнения
- Если разница отрицательная, задача уже должна была выполниться
- Laravel автоматически перемещает задачи из `delayed` в основную очередь когда наступает время
- **Payload задачи** содержит сериализованный JSON с информацией о классе Job и его данных

