<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);

    exit(
        '<h2>Database connection failed</h2>' .
        '<p>Please check the database configuration.</p>'
    );
}