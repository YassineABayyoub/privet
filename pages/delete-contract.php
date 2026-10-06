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
$personQuery = $pdo->prepare('SELECT DISTINCT person_id FROM contract_parties WHERE contract_id = :id');
$personQuery->execute(['id' => $contractId]);
$personIds = $personQuery->fetchAll(PDO::FETCH_COLUMN);

$pdo->beginTransaction();
try {
    $delete = $pdo->prepare('DELETE FROM contracts WHERE id = :id');
    $delete->execute(['id' => $contractId]);
    if ($delete->rowCount() === 0) {
        $pdo->rollBack();
        set_flash('error', 'العقد غير موجود أو سبق حذفه.');
        redirect('/pages/contracts.php');
    }

    // Remove people who were only linked to this contract and now have no contract left.
    $removeOrphan = $pdo->prepare(
        'DELETE FROM persons WHERE id = :id
         AND NOT EXISTS (SELECT 1 FROM contract_parties WHERE person_id = :person_id)'
    );
    foreach ($personIds as $personId) {
        $removeOrphan->execute(['id' => $personId, 'person_id' => $personId]);
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
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
