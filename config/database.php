<?php
declare(strict_types=1);

function database(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Try MySQL first if pdo_mysql is available and environment looks configured.
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'archive_contrats';
    $user = getenv('DB_USER');
    $password = getenv('DB_PASS');

    if (extension_loaded('pdo_mysql') && $user !== false && $user !== '') {
        $password = $password === false ? '' : $password;
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Log and fall back to SQLite for local development
            error_log('MySQL connection failed: ' . $e->getMessage());
        }
    }

    // A configured MySQL server that cannot be reached must be an error: silently switching to a
    // different (SQLite) database would split the archive across two stores.
    if ($user !== false && $user !== '') {
        throw new RuntimeException('Could not connect to MySQL; check DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASS.');
    }

    // The SQLite fallback is development-only (several pages use MySQL-specific SQL) and opt-in.
    if (getenv('DB_ALLOW_SQLITE') !== '1') {
        throw new RuntimeException('No database configured: set DB_USER/DB_PASS (MySQL), or DB_ALLOW_SQLITE=1 for development only.');
    }
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('No suitable PDO driver available: pdo_mysql or pdo_sqlite required.');
    }

    $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dataDir) && !mkdir($dataDir, 0700, true) && !is_dir($dataDir)) {
        throw new RuntimeException('Could not create data directory for SQLite fallback.');
    }
    $sqliteFile = $dataDir . DIRECTORY_SEPARATOR . 'archive_contrats.sqlite';
    $dsn = 'sqlite:' . $sqliteFile;

    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Ensure minimal schema exists for local development (compatible with the app's expectations).
    $pdo->beginTransaction();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(80) NOT NULL UNIQUE,
            display_name VARCHAR(160) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(10) NOT NULL DEFAULT 'clerk',
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_number VARCHAR(80) NOT NULL UNIQUE,
            contract_number_fr VARCHAR(80),
            contract_date DATE NOT NULL,
            registered_date DATE,
            category VARCHAR(80) NOT NULL,
            act_type VARCHAR(120) NOT NULL,
            archive_number VARCHAR(100) UNIQUE,
            archive_number_fr VARCHAR(100),
            reference_text VARCHAR(255),
            reference_text_fr VARCHAR(255),
            notes TEXT,
            notes_fr TEXT,
            specific_data TEXT,
            specific_data_fr TEXT,
            created_by INTEGER NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS persons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(120) NOT NULL,
            first_name_fr VARCHAR(120),
            last_name VARCHAR(160) NOT NULL,
            last_name_fr VARCHAR(160),
            identity_number VARCHAR(80) UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contract_parties (
            contract_id INTEGER NOT NULL,
            person_id INTEGER NOT NULL,
            party_role VARCHAR(100) NOT NULL,
            party_role_fr VARCHAR(100),
            PRIMARY KEY (contract_id, person_id, party_role)
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contract_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(80) NOT NULL UNIQUE,
            mime_type VARCHAR(80) NOT NULL,
            file_size INTEGER NOT NULL,
            uploaded_by INTEGER NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(80) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL
        );");

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $pdo;
}
