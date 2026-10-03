<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_judge();
$pdo = database();
$categoryRows = $pdo->query(
    'SELECT category, COUNT(*) AS total FROM contracts GROUP BY category ORDER BY category'
)->fetchAll();
$monthlyRows = $pdo->query(
    'SELECT DATE_FORMAT(contract_date, \'%Y-%m\') AS month, COUNT(*) AS total
     FROM contracts WHERE contract_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY DATE_FORMAT(contract_date, \'%Y-%m\') ORDER BY month'
)->fetchAll();
$pageTitle = 'التقارير';
$activePage = 'reports';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header"><div><h2>التقارير</h2><div class="page-subtitle">ملخصات محسوبة من بيانات العقود الموجودة في قاعدة البيانات</div></div><a class="btn btn-secondary" href="/pages/contracts.php">فتح الأرشيف</a></div>
<div class="two-col">
  <section class="panel"><div class="panel-header"><h3>العقود حسب التصنيف</h3></div><div class="panel-body">
    <?php if ($categoryRows === []): ?><div class="empty-state">لا توجد بيانات كافية لإعداد التقرير.</div><?php else: ?>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>التصنيف</th><th>عدد العقود</th></tr></thead><tbody><?php foreach ($categoryRows as $row): ?><tr><td><?= e($row['category']) ?></td><td><?= (int) $row['total'] ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </div></section>
  <section class="panel"><div class="panel-header"><h3>العقود حسب الشهر (آخر 12 شهراً)</h3></div><div class="panel-body">
    <?php if ($monthlyRows === []): ?><div class="empty-state">لا توجد عقود ضمن الفترة المحددة.</div><?php else: ?>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>الشهر</th><th>عدد العقود</th></tr></thead><tbody><?php foreach ($monthlyRows as $row): ?><tr><td><?= e($row['month']) ?></td><td><?= (int) $row['total'] ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </div></section>
</div>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
