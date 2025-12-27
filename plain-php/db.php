<?php

declare(strict_types=1);

function load_env(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    $vars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = isset($parts[1]) ? trim($parts[1]) : '';
        $value = trim($value, "\"'");

        if ($key !== '') {
            $vars[$key] = $value;
        }
    }

    return $vars;
}

$env = load_env(__DIR__ . '/.env');
$dbPath = $env['DB_DATABASE'] ?? (__DIR__ . '/database/database.sqlite');

$isAbsolute = preg_match('/^[A-Za-z]:[\\\\\\/]/', $dbPath) === 1
    || (strlen($dbPath) > 0 && $dbPath[0] === '/');
if (!$isAbsolute) {
    $dbPath = __DIR__ . DIRECTORY_SEPARATOR . $dbPath;
}

$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        status TEXT NOT NULL DEFAULT 'new',
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )"
);

return $pdo;
