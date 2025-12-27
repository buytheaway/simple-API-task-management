# Simple Task API (Laravel + SQLite)

REST API для управления задачами (To-Do List) на Laravel.

## Требования
- Docker (рекомендовано), или PHP 8.4+ и Composer

## Быстрый запуск (2 команды)
```bash
cp .env.example .env
docker compose up --build
```

API будет доступен на `http://localhost:8000`.

## Локальный запуск без Docker
```bash
cp .env.example .env
composer install
php artisan migrate
php artisan serve
```

## Endpoints
- `POST /tasks` (body: `title`, `description`, `status`)
- `GET /tasks`
- `GET /tasks/{id}`
- `PUT /tasks/{id}`
- `DELETE /tasks/{id}`

## HTTP статус-коды
- `POST /tasks` -> `201 Created`
- `GET` -> `200 OK`
- `PUT` -> `200 OK`
- `DELETE` -> `204 No Content`
- validation errors -> `422 Unprocessable Entity`
- not found -> `404 Not Found`
- wrong method -> `405 Method Not Allowed`

## Валидация
- `title` обязателен
- `status` должен быть одним из: `new`, `in_progress`, `done`
- `status` по умолчанию: `new`

## Формат ошибок
```json
{
  "error": "Validation failed",
  "fields": {
    "title": "Title is required."
  }
}
```

## Структура проекта
- `routes/api.php` — маршруты API
- `app/Http/Controllers/TaskController.php` — обработчики CRUD
- `app/Models/Task.php` — модель задачи
- `database/migrations/2025_12_27_000000_create_tasks_table.php` — миграция
- `plain-php/` — версия на чистом PHP (для истории)

## Postman collection
- `postman_collection.json`
