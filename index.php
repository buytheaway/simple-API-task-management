<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/db.php';

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    $json = json_encode($data, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{"error":"Failed to encode response"}';
        http_response_code(500);
    }
    echo $json;
    exit;
}

function error_response(string $message, int $status = 400, array $fields = []): void
{
    $payload = ['error' => $message];
    if ($fields !== []) {
        $payload['fields'] = $fields;
    }
    json_response($payload, $status);
}

function method_not_allowed(array $allowed): void
{
    header('Allow: ' . implode(', ', $allowed));
    error_response('Method not allowed', 405);
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        error_response('Invalid JSON body', 400);
    }

    return $data;
}

function fetch_task(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, title, description, status, created_at, updated_at FROM tasks WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();

    return $task !== false ? $task : null;
}

$allowedStatuses = ['new', 'in_progress', 'done'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = $path === null ? '/' : $path;
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

if ($path === '/tasks') {
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT id, title, description, status, created_at, updated_at FROM tasks ORDER BY id DESC'
        );
        $tasks = $stmt->fetchAll();
        json_response($tasks);
    }

    if ($method === 'POST') {
        $payload = read_json_body();
        $errors = [];

        $title = trim((string)($payload['title'] ?? ''));
        $description = $payload['description'] ?? null;
        $status = array_key_exists('status', $payload) ? trim((string)$payload['status']) : 'new';

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        }
        if ($status === '') {
            $errors['status'] = 'Status is required.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $errors['status'] = 'Status must be one of: ' . implode(', ', $allowedStatuses) . '.';
        }

        if ($errors !== []) {
            error_response('Validation failed', 422, $errors);
        }

        $now = gmdate('c');
        $stmt = $pdo->prepare(
            'INSERT INTO tasks (title, description, status, created_at, updated_at)
             VALUES (:title, :description, :status, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status' => $status,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $task = fetch_task($pdo, (int)$pdo->lastInsertId());
        json_response($task, 201);
    }

    method_not_allowed(['GET', 'POST']);
}

if (preg_match('#^/tasks/(\d+)$#', $path, $matches)) {
    $id = (int)$matches[1];

    if ($method === 'GET') {
        $task = fetch_task($pdo, $id);
        if ($task === null) {
            error_response('Task not found', 404);
        }
        json_response($task);
    }

    if ($method === 'PUT') {
        $task = fetch_task($pdo, $id);
        if ($task === null) {
            error_response('Task not found', 404);
        }

        $payload = read_json_body();
        $errors = [];
        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('title', $payload)) {
            $title = trim((string)$payload['title']);
            if ($title === '') {
                $errors['title'] = 'Title is required.';
            } else {
                $fields[] = 'title = :title';
                $params[':title'] = $title;
            }
        }

        if (array_key_exists('description', $payload)) {
            $fields[] = 'description = :description';
            $params[':description'] = $payload['description'];
        }

        if (array_key_exists('status', $payload)) {
            $status = trim((string)$payload['status']);
            if ($status === '') {
                $errors['status'] = 'Status is required.';
            } elseif (!in_array($status, $allowedStatuses, true)) {
                $errors['status'] = 'Status must be one of: ' . implode(', ', $allowedStatuses) . '.';
            } else {
                $fields[] = 'status = :status';
                $params[':status'] = $status;
            }
        }

        if ($errors !== []) {
            error_response('Validation failed', 422, $errors);
        }

        if ($fields === []) {
            error_response('No fields to update', 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params[':updated_at'] = gmdate('c');

        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $task = fetch_task($pdo, $id);
        json_response($task);
    }

    if ($method === 'DELETE') {
        $task = fetch_task($pdo, $id);
        if ($task === null) {
            error_response('Task not found', 404);
        }

        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $id]);
        http_response_code(204);
        exit;
    }

    method_not_allowed(['GET', 'PUT', 'DELETE']);
}

error_response('Not found', 404);
