# Simple Task API (PHP + SQLite)

This is a small REST API for managing tasks using plain PHP and SQLite.

## Requirements
- PHP 7.4+ with PDO SQLite enabled, or Docker

## Run with Docker (recommended)
```bash
cp .env.example .env
docker compose up --build
```

API will be available at `http://localhost:8000`.

## Run locally
1. Create `.env` (if needed) and confirm the database path:

```bash
cp .env.example .env
```

2. Start the server:

```bash
php -S localhost:8000 index.php
```

## Endpoints
- `POST /tasks` (body: `title`, `description`, `status`)
- `GET /tasks`
- `GET /tasks/{id}`
- `PUT /tasks/{id}`
- `DELETE /tasks/{id}`

## HTTP status codes
- `POST /tasks` -> `201 Created`
- `GET` -> `200 OK`
- `PUT` -> `200 OK`
- `DELETE` -> `204 No Content`
- validation errors -> `422 Unprocessable Entity`
- not found -> `404 Not Found`
- wrong method -> `405 Method Not Allowed`

## Validation
- `title` is required
- `status` must be one of: `new`, `in_progress`, `done`
- default `status` is `new`

## Error format
```json
{
  "error": "Validation failed",
  "fields": {
    "title": "Title is required."
  }
}
```

## Project structure
- `index.php` - router + handlers
- `db.php` - SQLite connection and table setup
- `database/database.sqlite` - SQLite database file (created on first run)
- `.env` - environment config (see `.env.example`)

## Postman collection
- `postman_collection.json`

## Example requests
Create:
```bash
curl -X POST http://localhost:8000/tasks \
  -H "Content-Type: application/json" \
  -d "{\"title\":\"Buy milk\",\"description\":\"2 liters\",\"status\":\"new\"}"
```

List:
```bash
curl http://localhost:8000/tasks
```

Get one:
```bash
curl http://localhost:8000/tasks/1
```

Update:
```bash
curl -X PUT http://localhost:8000/tasks/1 \
  -H "Content-Type: application/json" \
  -d "{\"status\":\"done\"}"
```

Delete:
```bash
curl -X DELETE http://localhost:8000/tasks/1
```
