<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$pdo = database();

$categories = ['الزواج', 'الأملاك', 'التركات', 'مختلفة'];
$countStatement = $pdo->query('SELECT category, COUNT(*) AS total FROM contracts GROUP BY category');
$categoryCounts = array_fill_keys($categories, 0);
foreach ($countStatement->fetchAll() as $row) {
    if (array_key_exists($row['category'], $categoryCounts)) {
        $categoryCounts[$row['category']] = (int) $row['total'];
    }
}

$contractCount = (int) $pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn();
$personCount = (int) $pdo->query('SELECT COUNT(*) FROM persons')->fetchColumn();
$userCount = $user['role'] === 'judge'
    ? (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn()
    : null;
$recentStatement = $pdo->query(
    'SELECT id, contract_number, category, act_type, contract_date
     FROM contracts ORDER BY created_at DESC, id DESC LIMIT 5'
);
$recentContracts = $recentStatement->fetchAll();
$flash = take_flash();

$pageTitle = 'لوحة التحكم';
$activePage = 'dashboard';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header">
  <div>
    <h2>لوحة التحكم</h2>
    <div class="page-subtitle">إحصاءات مباشرة من قاعدة بيانات الأرشيف</div>
  </div>
  <div class="header-actions">
    <a class="btn btn-primary" href="/pages/add-contract.php">+ إضافة عقد جديد</a>
  </div>
</div>

<?php if ($flash !== null): ?>
  <div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>

<section class="stats-grid">
  <article class="summary-card dashboard-card"><div class="stat-item"><div><small>مجموع العقود</small><strong><?= $contractCount ?></strong></div><div class="metric-icon">📚</div></div><div class="change">إجمالي السجلات</div></article>
  <article class="summary-card dashboard-card"><div class="stat-item"><div><small>عقود الزواج</small><strong><?= $categoryCounts['الزواج'] ?></strong></div><div class="metric-icon">💍</div></div><div class="change">حسب التصنيف</div></article>
  <article class="summary-card dashboard-card"><div class="stat-item"><div><small>عقود الأملاك</small><strong><?= $categoryCounts['الأملاك'] ?></strong></div><div class="metric-icon">🏠</div></div><div class="change">حسب التصنيف</div></article>
  <article class="summary-card dashboard-card"><div class="stat-item"><div><small>عقود التركات</small><strong><?= $categoryCounts['التركات'] ?></strong></div><div class="metric-icon">⚖️</div></div><div class="change">حسب التصنيف</div></article>
  <article class="summary-card dashboard-card"><div class="stat-item"><div><small>عقود مختلفة</small><strong><?= $categoryCounts['مختلفة'] ?></strong></div><div class="metric-icon">📁</div></div><div class="change">حسب التصنيف</div></article>
  <article class="summary-card dashboard-card"><div class="stat-item"><div><small>عدد الأشخاص</small><strong><?= $personCount ?></strong></div><div class="metric-icon">👥</div></div><div class="change">أطراف مرتبطة بالعقود</div></article>
  <?php if ($userCount !== null): ?>
    <article class="summary-card dashboard-card"><div class="stat-item"><div><small>المستخدمون النشطون</small><strong><?= $userCount ?></strong></div><div class="metric-icon">👤</div></div><div class="change">حسابات مفعلة</div></article>
  <?php endif; ?>
</section>

<form class="quick-search" action="/pages/contracts.php" method="get">
  <div>
    <label for="dashboardSearch">البحث السريع</label>
    <div class="search-box"><span>🔎</span><input id="dashboardSearch" name="q" type="search" placeholder="رقم العقد أو اسم الطرف أو المرجع" /></div>
  </div>
  <div>
    <label for="dashboardCategory">الفئة</label>
    <select id="dashboardCategory" name="category">
      <option value="">كل التصنيفات</option>
      <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?= e($category) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div><label>&nbsp;</label><a class="btn btn-secondary" href="/pages/contracts.php">فتح الأرشيف</a></div>
  <button class="btn btn-primary" type="submit">بحث</button>
</form>

<section class="panel">
  <div class="panel-header"><h3>آخر العقود المسجلة</h3><a class="btn btn-secondary" href="/pages/contracts.php">عرض الكل</a></div>
  <div class="panel-body">
    <?php if ($recentContracts === []): ?>
      <div class="empty-state">لا توجد عقود في قاعدة البيانات بعد. ابدأ بإضافة أول عقد.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>رقم العقد</th><th>نوع العقد</th><th>التصنيف</th><th>التاريخ</th><th>الإجراء</th></tr></thead>
          <tbody>
            <?php foreach ($recentContracts as $contract): ?>
              <tr>
                <td><?= e($contract['contract_number']) ?></td><td><?= e($contract['act_type']) ?></td><td><?= e($contract['category']) ?></td>
                <td><?= e($contract['contract_date']) ?></td><td><a class="action-btn primary" href="/pages/contract-details.php?id=<?= (int) $contract['id'] ?>">عرض</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
