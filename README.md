
# jobstileexpert-test

Тестовое задание на Symfony с Docker Compose, REST API, SOAP API и полнотекстовым поиском через Manticore Search.

## 🚀 Быстрый запуск

```bash
git clone <repository>
cd jobstileexpert-test
docker-compose up -d --build
```

После запуска контейнеров выполните миграции и индексацию:

```bash
# Применить миграции базы данных
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Загрузить тестовые данные (18 заказов)
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction

# Проиндексировать заказы в Manticore Search
docker-compose exec app php bin/console app:manticore:index-orders
```

## 🌐 Доступные сервисы

- **Приложение**: http://localhost:8081
- **MySQL**: localhost:3307 
- **Manticore Search**: localhost:9306 (MySQL protocol), localhost:9308 (HTTP API)

## 📡 REST API

### Получение заказа по ID
```bash
GET /api/orders/{id}

# Пример:
curl "http://localhost:8081/api/orders/1"
```

### Агрегация заказов с пагинацией
```bash
GET /api/orders/aggregate?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD&group_by=day|week|month|year&page=1&per_page=10

# Пример:
curl "http://localhost:8081/api/orders/aggregate?start_date=2024-01-01&end_date=2024-12-31&group_by=month"
```

### Поиск заказов через Manticore Search
```bash
GET /api/orders/search?query=search_term

# Пример:
curl "http://localhost:8081/api/orders/search?query=150"
```

### Получение цены товара
```bash
GET /api/price?factory=название&collection=коллекция&article=артикул

# Пример:
curl "http://localhost:8081/api/price?factory=Factory1&collection=Collection1&article=Article1"
```

### Общий поиск
```bash
GET /api/search?q=search_term

# Пример:
curl "http://localhost:8081/api/search?q=77.77"
```

## 🧼 SOAP API

```bash
POST /soap
Content-Type: text/xml; charset=utf-8
```

Пример SOAP запроса для создания заказа:
```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateOrder>
            <amount>123.45</amount>
            <created_at>2025-01-18T12:00:00</created_at>
        </CreateOrder>
    </soap:Body>
</soap:Envelope>
```

## 🧪 Тестирование

Запуск всех тестов:
```bash
docker-compose exec app php bin/phpunit
```

Запуск конкретного теста:
```bash
docker-compose exec app php bin/phpunit tests/Controller/OrderControllerTest.php
```

## ⚙️ Переменные окружения

Основные переменные в файле `.env`:

- `APP_PORT=8081` — порт приложения
- `MYSQL_ROOT_PASSWORD=testpass` — пароль root для MySQL
- `MYSQL_DATABASE=jobstileexpert-test` — имя базы данных
- `MANTICORE_HOST=manticore` — хост Manticore Search
- `MANTICORE_PORT=9308` — порт HTTP API Manticore

## 📊 Структура данных

### Таблица orders
- `id` (int, PK, auto_increment)
- `created_at` (datetime)
- `amount` (decimal 10,2)

### Тестовые данные
Автоматически загружается 18 заказов с различными датами (2024-2025) и суммами для демонстрации агрегации и поиска.

## 🔍 Manticore Search

### Индексация
```bash
# Создать/обновить индекс заказов
docker-compose exec app php bin/console app:manticore:index-orders
```

### Конфигурация индекса
- **Имя индекса**: `orders`
- **Поля**: `id`, `amount`, `created_at`
- **Полнотекстовый поиск** по всем полям

## 🐳 Docker Services

- **app** (Symfony 7.4 + PHP 8.3-fpm)
- **mysql** (MySQL 8.0)
- **manticore** (Manticore Search latest)

## 📝 Команды управления

```bash
# Просмотр логов
docker-compose logs -f app

# Выполнение команд в контейнере
docker-compose exec app bash

# Перезапуск сервисов
docker-compose restart

# Остановка и удаление
docker-compose down
```

## ✅ Проверка работы

После запуска и настройки все эндпоинты должны возвращать корректные данные:

1. **Агрегация**: возвращает группированные данные по периодам
2. **Поиск**: находит заказы через Manticore Search  
3. **SOAP**: принимает и обрабатывает XML запросы
4. **Получение по ID**: возвращает конкретный заказ

Все API покрыты автотестами и готовы к использованию "из коробки".
