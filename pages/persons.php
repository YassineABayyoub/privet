<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$where = '';
$params = [];
if ($q !== '') {
    $where = ' WHERE p.first_name LIKE :first_name OR p.first_name_fr LIKE :first_name_fr
        OR p.last_name LIKE :last_name OR p.last_name_fr LIKE :last_name_fr OR p.identity_number LIKE :identity_number';
    $pattern = like_pattern($q);
    $params = [
        'first_name' => $pattern,
        'first_name_fr' => $pattern,
        'last_name' => $pattern,
        'last_name_fr' => $pattern,
        'identity_number' => $pattern,
    ];
}

$count = database()->prepare('SELECT COUNT(*) FROM persons p' . $where);
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$query = database()->prepare(
    'SELECT p.id, p.first_name, p.first_name_fr, p.last_name, p.last_name_fr,
            p.identity_number, COUNT(DISTINCT cp.contract_id) AS contract_count
     FROM persons p LEFT JOIN contract_parties cp ON cp.person_id = p.id' . $where . '
     GROUP BY p.id ORDER BY p.last_name, p.first_name LIMIT :limit OFFSET :offset'
);
foreach ($params as $key => $value) {
    $query->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$query->bindValue(':limit', $perPage, PDO::PARAM_INT);
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$persons = $query->fetchAll();
$pageTitle = 'الأشخاص';
$activePage = 'persons';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header"><div><h2>الأشخاص</h2><div class="page-subtitle">قائمة الأشخاص المرتبطين بعقود محفوظة</div></div></div>
<form class="page-tools" method="get" action="/pages/persons.php"><div class="search-box"><span>🔎</span><input name="q" value="<?= e($q) ?>" type="search" placeholder="ابحث بالاسم أو رقم البطاقة" /></div><button class="btn btn-primary" type="submit">بحث</button><a class="btn btn-secondary" href="/pages/persons.php">إعادة ضبط</a></form>
<section class="panel">
  <div class="panel-header"><h3>الأشخاص</h3><span class="badge badge-primary"><?= $total ?> شخص</span></div>
  <div class="panel-body">
  <?php if ($persons === []): ?><div class="empty-state">لا توجد بيانات أشخاص<?= $q !== '' ? ' مطابقة للبحث' : '' ?>.</div><?php else: ?>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>الاسم</th><th>النسب</th><th>رقم البطاقة</th><th>العقود المرتبطة</th><th>الإجراء</th></tr></thead><tbody>
    <?php foreach ($persons as $person): ?><tr><td><?= bilingual_value($person['first_name'], $person['first_name_fr']) ?></td><td><?= bilingual_value($person['last_name'], $person['last_name_fr']) ?></td><td><?= e($person['identity_number'] ?: '—') ?></td><td><?= (int) $person['contract_count'] ?></td><td><a class="action-btn primary" href="/pages/person-details.php?id=<?= (int) $person['id'] ?>">عرض</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"><span>النتائج <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> من <?= $total ?></span><div class="page-numbers"><?php if ($page > 1): ?><a class="action-btn" href="?<?= e(http_build_query(['q' => $q, 'page' => $page - 1])) ?>">السابق</a><?php endif; ?><span class="badge badge-primary"><?= $page ?> / <?= $pages ?></span><?php if ($page < $pages): ?><a class="action-btn" href="?<?= e(http_build_query(['q' => $q, 'page' => $page + 1])) ?>">التالي</a><?php endif; ?></div></div>
  <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
