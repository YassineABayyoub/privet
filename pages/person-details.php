<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$personId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$personId || $personId < 1) {
    http_response_code(400);
    exit('معرف الشخص غير صالح.');
}
$statement = database()->prepare('SELECT id, first_name, first_name_fr, last_name, last_name_fr, identity_number, created_at FROM persons WHERE id = :id');
$statement->execute(['id' => $personId]);
$person = $statement->fetch();
if ($person === false) {
    http_response_code(404);
    exit('الشخص غير موجود.');
}
$contractsStatement = database()->prepare(
    'SELECT c.id, c.contract_number, c.contract_number_fr, c.act_type, c.category, c.contract_date,
            cp.party_role, cp.party_role_fr
     FROM contract_parties cp JOIN contracts c ON c.id = cp.contract_id
     WHERE cp.person_id = :person_id ORDER BY c.contract_date DESC, c.id DESC'
);
$contractsStatement->execute(['person_id' => $personId]);
$contracts = $contractsStatement->fetchAll();
$pageTitle = 'تفاصيل الشخص';
$activePage = 'persons';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header"><div><h2><?= bilingual_person_name($person['first_name'], $person['last_name'], $person['first_name_fr'], $person['last_name_fr']) ?></h2><div class="page-subtitle">تفاصيل الطرف وسجل العقود المرتبط به</div></div><a class="btn btn-secondary" href="/pages/persons.php">رجوع إلى الأشخاص</a></div>
<section class="detail-card">
  <div class="key-value"><div class="label">الاسم</div><div><?= bilingual_value($person['first_name'], $person['first_name_fr']) ?></div></div>
  <div class="key-value"><div class="label">النسب</div><div><?= bilingual_value($person['last_name'], $person['last_name_fr']) ?></div></div>
  <div class="key-value"><div class="label">رقم البطاقة</div><div><?= e($person['identity_number'] ?: '—') ?></div></div>
  <div class="key-value"><div class="label">عدد العقود</div><div><?= count($contracts) ?></div></div>
</section>
<section class="form-section"><h3>العقود المرتبطة</h3>
  <?php if ($contracts === []): ?><div class="detail-card">لا توجد عقود مرتبطة بهذا الشخص.</div><?php else: ?>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>رقم العقد</th><th>النوع</th><th>التصنيف</th><th>التاريخ</th><th>الدور</th><th>الإجراء</th></tr></thead><tbody><?php foreach ($contracts as $contract): ?><tr><td><?= bilingual_value($contract['contract_number'], $contract['contract_number_fr']) ?></td><td><?= e($contract['act_type']) ?></td><td><?= e($contract['category']) ?></td><td><?= e($contract['contract_date']) ?></td><td><?= bilingual_value($contract['party_role'], $contract['party_role_fr']) ?></td><td><a class="action-btn primary" href="/pages/contract-details.php?id=<?= (int) $contract['id'] ?>">عرض العقد</a></td></tr><?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
