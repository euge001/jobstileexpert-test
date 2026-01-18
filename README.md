
# jobstileexpert-test

## Запуск

```bash
docker compose up -d --build
# или (если v1)
docker-compose up -d --build
```

## Переменные окружения
- APP_PORT=8081 — порт приложения
- MYSQL_ROOT_PASSWORD, MYSQL_DATABASE — для MySQL

## Миграции
```bash
docker compose exec app php bin/console doctrine:migrations:migrate
```

## Индексация заказов в Manticore
```bash
docker compose exec app php bin/console app:manticore:index-orders
```

## REST API

### Получить цену
`GET /api/price?factory=...&collection=...&article=...`

### Получить заказ
`GET /api/orders/{id}`

### Агрегация заказов
`GET /api/orders/aggregate?group_by=day|month|year&page=1&page_size=10`

### Поиск
`GET /api/search?q=77.77`

## SOAP API

`POST /soap`

Пример запроса:
```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	<soap:Body>
		<CreateOrder>
			<amount>123.45</amount>
			<created_at>2026-01-18T12:00:00</created_at>
		</CreateOrder>
	</soap:Body>
</soap:Envelope>
```

## Поиск (Manticore)
После индексации заказов:
```bash
curl "http://localhost:8081/api/search?q=77.77"
```

## Примечания
- Для поиска требуется предварительная индексация заказов.
- Все сервисы запускаются через Docker Compose.
