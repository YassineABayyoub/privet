<?php
declare(strict_types=1);

function database(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('The PHP pdo_mysql extension is required.');
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'archive_contrats';
    $user = getenv('DB_USER');
    $password = getenv('DB_PASS');

    if ($user === false || $user === '') {
        throw new RuntimeException('DB_USER environment variable must be set.');
    }
    $password = $password === false ? '' : $password;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $name
    );

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
