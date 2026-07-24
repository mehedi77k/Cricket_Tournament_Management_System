<?php

declare(strict_types=1);

$databaseHost = '127.0.0.1';
$databasePort = '3306';
$databaseName = 'cricket_tournament_db';
$databaseUser = 'root';
$databasePassword = '';

$dsn = "mysql:host={$databaseHost};port={$databasePort};dbname={$databaseName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $databaseUser, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    exit(
        '<h2>Database connection failed</h2>' .
        '<p>Check <code>config/database.php</code> and confirm that Laragon MySQL is running.</p>' .
        '<pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>'
    );
}
