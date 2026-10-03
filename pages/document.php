<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId || $documentId < 1) {
    http_response_code(400);
    exit('معرف الوثيقة غير صالح.');
}

$statement = database()->prepare(
    'SELECT original_name, stored_name, mime_type, file_size FROM contract_documents WHERE id = :id LIMIT 1'
);
$statement->execute(['id' => $documentId]);
$document = $statement->fetch();
if ($document === false) {
    http_response_code(404);
    exit('الوثيقة غير موجودة.');
}

$storageDirectory = document_storage_directory();
$filePath = realpath($storageDirectory . DIRECTORY_SEPARATOR . basename($document['stored_name']));
if ($filePath === false || dirname($filePath) !== $storageDirectory || !is_file($filePath)) {
    error_log('Contract document record points to a missing or invalid file: ' . $documentId);
    http_response_code(404);
    exit('ملف الوثيقة غير متاح.');
}

$download = ($_GET['download'] ?? '') === '1';
$disposition = $download ? 'attachment' : 'inline';
$safeName = rawurlencode($document['original_name']);
header('Content-Type: ' . $document['mime_type']);
header('Content-Length: ' . (string) filesize($filePath));
header("Content-Disposition: {$disposition}; filename*=UTF-8''{$safeName}");
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: sandbox');
header('Cache-Control: private, no-store');
readfile($filePath);
