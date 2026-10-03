<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$contractId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$contractId || $contractId < 1) {
    http_response_code(400);
    exit('رقم العقد غير صالح.');
}

$statement = database()->prepare(
    'SELECT c.*, u.display_name AS creator_name
     FROM contracts c JOIN users u ON u.id = c.created_by
     WHERE c.id = :id LIMIT 1'
);
$statement->execute(['id' => $contractId]);
$contract = $statement->fetch();
if ($contract === false) {
    http_response_code(404);
    exit('العقد غير موجود.');
}

$partyStatement = database()->prepare(
    'SELECT p.first_name, p.last_name, p.identity_number, cp.party_role
     FROM contract_parties cp JOIN persons p ON p.id = cp.person_id
     WHERE cp.contract_id = :id ORDER BY p.last_name, p.first_name'
);
$partyStatement->execute(['id' => $contractId]);
$parties = $partyStatement->fetchAll();
$documentStatement = database()->prepare(
    'SELECT id, original_name, mime_type, file_size, created_at FROM contract_documents WHERE contract_id = :id ORDER BY id'
);
$documentStatement->execute(['id' => $contractId]);
$documents = $documentStatement->fetchAll();
$specificData = $contract['specific_data'] ? json_decode($contract['specific_data'], true) : [];
$pageTitle = 'تفاصيل العقد';
$activePage = 'contracts';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header">
  <div><h2>تفاصيل العقد</h2><div class="page-subtitle"><?= e($contract['act_type']) ?> — <?= e($contract['contract_number']) ?></div></div>
  <div class="header-actions">
    <a class="btn btn-secondary" href="/pages/contracts.php">رجوع</a>
    <button class="btn btn-secondary" type="button" onclick="window.print()">طباعة</button>
    <?php if ($user['role'] === 'judge'): ?>
      <a class="btn btn-primary" href="/pages/edit-contract.php?id=<?= $contractId ?>">تعديل</a>
      <form method="post" action="/pages/delete-contract.php" onsubmit="return confirm('هل تريد حذف هذا العقد؟');">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="contract_id" value="<?= $contractId ?>" />
        <button class="btn btn-danger" type="submit">حذف</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<div class="two-col">
  <section class="detail-card">
    <h3>المعلومات العامة</h3>
    <div class="key-value"><div class="label">التصنيف</div><div><?= e($contract['category']) ?></div></div>
    <div class="key-value"><div class="label">نوع العقد</div><div><?= e($contract['act_type']) ?></div></div>
    <div class="key-value"><div class="label">رقم العقد</div><div><?= e($contract['contract_number']) ?></div></div>
    <div class="key-value"><div class="label">تاريخ العقد</div><div><?= e($contract['contract_date']) ?></div></div>
    <div class="key-value"><div class="label">تاريخ التسجيل</div><div><?= e($contract['registered_date'] ?: '—') ?></div></div>
    <div class="key-value"><div class="label">رقم الأرشيف</div><div><?= e($contract['archive_number'] ?: '—') ?></div></div>
    <div class="key-value"><div class="label">المرجع</div><div><?= e($contract['reference_text'] ?: '—') ?></div></div>
    <div class="key-value"><div class="label">ملاحظات</div><div><?= nl2br(e($contract['notes'] ?: '—')) ?></div></div>
    <div class="key-value"><div class="label">أُدخل بواسطة</div><div><?= e($contract['creator_name']) ?></div></div>
  </section>
  <section class="detail-card">
    <h3>الأطراف</h3>
    <?php if ($parties === []): ?><div class="empty-state">لا توجد أطراف مرتبطة.</div><?php else: ?>
      <ul class="list-compact">
        <?php foreach ($parties as $party): ?><li><span><?= e($party['first_name'] . ' ' . $party['last_name']) ?><?php if ($party['identity_number']): ?><br /><small><?= e($party['identity_number']) ?></small><?php endif; ?></span><span class="badge badge-primary"><?= e($party['party_role']) ?></span></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
<section class="form-section">
  <h3>الوثائق المرفقة</h3>
  <?php if ($documents === []): ?><div class="detail-card">لا توجد وثائق مرفقة.</div><?php else: ?>
    <ul class="list-compact">
      <?php foreach ($documents as $document): ?>
        <li><span><?= e($document['original_name']) ?><br /><small><?= e($document['mime_type']) ?> · <?= number_format(((int) $document['file_size']) / 1024, 1) ?> KB</small></span><a class="action-btn primary" href="/pages/document.php?id=<?= (int) $document['id'] ?>" target="_blank" rel="noopener noreferrer">عرض / تحميل</a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php if (is_array($specificData) && $specificData !== []): ?>
  <section class="form-section"><h3>معلومات إضافية خاصة بالعقد</h3><div class="detail-card">
    <?php foreach ($specificData as $label => $value): ?>
      <?php if ($label === 'الأملاك' && is_array($value)): ?>
        <div class="property-details-list">
          <?php foreach ($value as $index => $property): if (!is_array($property)) { continue; } ?>
            <article class="property-detail-card">
              <h4><?= e((string) ($property['النوع'] ?? 'ملك')) ?> <?= $index + 1 ?></h4>
              <?php if (is_array($property['الخصائص'] ?? null)): ?>
                <?php foreach ($property['الخصائص'] as $propertyLabel => $propertyValue): ?>
                  <?php if (is_scalar($propertyValue)): ?>
                    <div class="key-value"><div class="label"><?= e((string) $propertyLabel) ?></div><div><?= nl2br(e((string) $propertyValue)) ?></div></div>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php elseif (is_scalar($value)): ?>
        <div class="key-value"><div class="label"><?= e((string) $label) ?></div><div><?= nl2br(e((string) $value)) ?></div></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div></section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
