<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_judge();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}
verify_csrf();
$contractId = filter_input(INPUT_POST, 'contract_id', FILTER_VALIDATE_INT);
if (!$contractId || $contractId < 1) {
    set_flash('error', 'رقم العقد غير صالح.');
    redirect('/pages/contracts.php');
}

$pdo = database();
$documentQuery = $pdo->prepare('SELECT stored_name FROM contract_documents WHERE contract_id = :id');
$documentQuery->execute(['id' => $contractId]);
$storedNames = $documentQuery->fetchAll(PDO::FETCH_COLUMN);
$delete = $pdo->prepare('DELETE FROM contracts WHERE id = :id');
$delete->execute(['id' => $contractId]);
if ($delete->rowCount() === 0) {
    set_flash('error', 'العقد غير موجود أو سبق حذفه.');
    redirect('/pages/contracts.php');
}

$storageDirectory = document_storage_directory();
foreach ($storedNames as $storedName) {
    $path = $storageDirectory . DIRECTORY_SEPARATOR . basename((string) $storedName);
    if (is_file($path) && !unlink($path)) {
        error_log('Could not remove deleted contract document: ' . basename($path));
    }
}
set_flash('success', 'تم حذف العقد.');
redirect('/pages/contracts.php');
