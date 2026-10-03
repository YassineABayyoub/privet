<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = trim((string) readline('Admin username: '));
$displayName = trim((string) readline('Admin display name: '));
$password = (string) readline('Admin password (minimum 12 characters): ');
$passwordLength = preg_match_all('/./us', $password);

if ($username === '' || $displayName === '' || $passwordLength === false || $passwordLength < 12) {
    fwrite(STDERR, "Username and display name are required; password must contain at least 12 characters.\n");
    exit(1);
}

$statement = database()->prepare(
    'INSERT INTO users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, \'judge\')'
);
$statement->execute([
    'username' => $username,
    'display_name' => $displayName,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

fwrite(STDOUT, "Administrator account created.\n");
