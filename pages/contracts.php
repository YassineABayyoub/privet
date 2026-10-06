<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$pdo = database();

$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(c.contract_number LIKE :q_number OR c.contract_number_fr LIKE :q_number_fr
      OR c.reference_text LIKE :q_reference OR c.reference_text_fr LIKE :q_reference_fr
      OR c.notes LIKE :q_notes OR c.notes_fr LIKE :q_notes_fr OR EXISTS (
        SELECT 1 FROM contract_parties cp
        JOIN persons p ON p.id = cp.person_id
        WHERE cp.contract_id = c.id
          AND (p.first_name LIKE :q_first OR p.first_name_fr LIKE :q_first_fr
            OR p.last_name LIKE :q_last OR p.last_name_fr LIKE :q_last_fr OR p.identity_number LIKE :q_identity)
    ))';
    $searchPattern = like_pattern($q);
    $params['q_number'] = $searchPattern;
    $params['q_number_fr'] = $searchPattern;
    $params['q_reference'] = $searchPattern;
    $params['q_reference_fr'] = $searchPattern;
    $params['q_notes'] = $searchPattern;
    $params['q_notes_fr'] = $searchPattern;
    $params['q_first'] = $searchPattern;
    $params['q_first_fr'] = $searchPattern;
    $params['q_last'] = $searchPattern;
    $params['q_last_fr'] = $searchPattern;
    $params['q_identity'] = $searchPattern;
}
if ($category !== '' && in_array($category, array_keys(contract_types()), true)) {
    $where[] = 'c.category = :category';
    $params['category'] = $category;
}
if ($type !== '') {
    $where[] = 'c.act_type = :act_type';
    $params['act_type'] = $type;
}

$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
$countStatement = $pdo->prepare('SELECT COUNT(*) FROM contracts c' . $whereSql);
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pageCount = max(1, (int) ceil($total / $perPage));
$page = min($page, $pageCount);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT c.id, c.contract_number, c.contract_number_fr, c.category, c.act_type, c.contract_date,
               c.archive_number, c.archive_number_fr,
               (SELECT GROUP_CONCAT(CONCAT(p.first_name, \' \', p.last_name, \' — \', cp.party_role) SEPARATOR \'، \')
                FROM contract_parties cp JOIN persons p ON p.id = cp.person_id
                WHERE cp.contract_id = c.id) AS parties,
               (SELECT GROUP_CONCAT(CONCAT(COALESCE(NULLIF(p.first_name_fr, \'\'), p.first_name), \' \',
                    COALESCE(NULLIF(p.last_name_fr, \'\'), p.last_name), \' — \',
                    COALESCE(NULLIF(cp.party_role_fr, \'\'), cp.party_role)) SEPARATOR \', \')
                FROM contract_parties cp JOIN persons p ON p.id = cp.person_id
                WHERE cp.contract_id = c.id) AS parties_fr
        FROM contracts c' . $whereSql . '
        ORDER BY c.contract_date DESC, c.id DESC LIMIT :limit OFFSET :offset';
$statement = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$contracts = $statement->fetchAll();
$flash = take_flash();
$pageTitle = 'العقود';
$activePage = 'contracts';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header">
  <div><h2>العقود</h2><div class="page-subtitle">بحث وتصفية السجلات المحفوظة في قاعدة البيانات</div></div>
  <div class="header-actions"><a class="btn btn-primary" href="/pages/add-contract.php">+ إضافة عقد جديد</a></div>
</div>
<?php if ($flash !== null): ?><div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="page-tools" method="get" action="/pages/contracts.php">
  <div class="search-box"><span>🔎</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="رقم العقد، اسم الطرف، رقم البطاقة، المرجع" /></div>
  <div class="inline-filters">
    <select name="category">
      <option value="">كل التصنيفات</option>
      <?php foreach (array_keys(contract_types()) as $item): ?><option value="<?= e($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?>
    </select>
    <select name="type">
      <option value="">كل الأنواع</option>
      <?php foreach (contract_types() as $types): foreach ($types as $item): ?><option value="<?= e($item) ?>" <?= $type === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">بحث</button>
    <a class="btn btn-secondary" href="/pages/contracts.php">إعادة ضبط</a>
  </div>
</form>
<section class="panel">
  <div class="panel-header"><h3>قائمة العقود</h3><span class="badge badge-primary"><?= $total ?> عقد</span></div>
  <div class="panel-body">
    <?php if ($contracts === []): ?>
      <div class="empty-state"><?= $total === 0 ? 'لا توجد عقود مطابقة. أضف عقداً أو غيّر معايير البحث.' : 'لا توجد عقود في هذه الصفحة.' ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>رقم العقد</th><th>نوع العقد</th><th>التصنيف</th><th>التاريخ</th><th>الأطراف</th><th>رقم الأرشيف</th><th>الإجراءات</th></tr></thead>
          <tbody>
            <?php foreach ($contracts as $contract): ?>
              <tr>
                <td><?= bilingual_value($contract['contract_number'], $contract['contract_number_fr']) ?></td>
                <td><?= e($contract['act_type']) ?></td>
                <td><?= e($contract['category']) ?></td>
                <td><?= e($contract['contract_date']) ?></td>
                <td><?= bilingual_value($contract['parties'] ?: '—', $contract['parties_fr'] ?: '—') ?></td>
                <td><?= bilingual_value($contract['archive_number'] ?: '—', $contract['archive_number_fr'] ?: $contract['archive_number'] ?: '—') ?></td>
                <td>
                  <div class="inline-actions">
                    <a class="action-btn primary" href="/pages/contract-details.php?id=<?= (int) $contract['id'] ?>">عرض</a>
                    <?php if ($user['role'] === 'judge'): ?>
                      <a class="action-btn" href="/pages/edit-contract.php?id=<?= (int) $contract['id'] ?>">تعديل</a>
                      <form method="post" action="/pages/delete-contract.php" onsubmit="return confirm('هل تريد حذف هذا العقد؟');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                        <input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>" />
                        <button class="action-btn danger" type="submit">حذف</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination">
        <span>النتائج <?= $total === 0 ? 0 : $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> من <?= $total ?></span>
        <div class="page-numbers">
          <?php if ($page > 1): ?><a class="action-btn" href="?<?= e(http_build_query(['q' => $q, 'category' => $category, 'type' => $type, 'page' => $page - 1])) ?>">السابق</a><?php endif; ?>
          <span class="badge badge-primary"><?= $page ?> / <?= $pageCount ?></span>
          <?php if ($page < $pageCount): ?><a class="action-btn" href="?<?= e(http_build_query(['q' => $q, 'category' => $category, 'type' => $type, 'page' => $page + 1])) ?>">التالي</a><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
