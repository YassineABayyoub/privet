<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'أرشيف العقود العدلية';
$activePage = $activePage ?? '';
$user = $user ?? require_auth();
$roleLabel = $user['role'] === 'judge' ? 'العدل' : 'الكاتبة';
$avatarLetter = preg_match('/^./us', $user['display_name'], $matches) === 1 ? $matches[0] : '؟';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($pageTitle) ?> | أرشيف العقود العدلية</title>
    <link rel="stylesheet" href="/css/style.css" />
    <link rel="stylesheet" href="/css/layout.css" />
    <link rel="stylesheet" href="/css/dashboard.css" />
    <link rel="stylesheet" href="/css/forms.css" />
    <link rel="stylesheet" href="/css/tables.css" />
    <link rel="stylesheet" href="/css/responsive.css" />
  </head>
  <body data-role="<?= e($user['role']) ?>">
    <div class="app-shell">
      <aside class="sidebar">
        <div class="brand">
          <div class="brand-mark"><img src="/assets/icons/app-icon.svg" alt="" /></div>
          <div class="brand-text">
            <h1>أرشيف العقود</h1>
            <small>منصة العدلية</small>
          </div>
        </div>

        <nav class="nav" aria-label="التنقل الرئيسي">
          <a class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="/pages/dashboard.php"><span class="icon">🏠</span>الرئيسية</a>
          <a class="nav-item <?= $activePage === 'contracts' ? 'active' : '' ?>" href="/pages/contracts.php"><span class="icon">📄</span>العقود</a>
          <a class="nav-item <?= $activePage === 'add-contract' ? 'active' : '' ?>" href="/pages/add-contract.php"><span class="icon">➕</span>إضافة عقد</a>
          <a class="nav-item <?= in_array($activePage, ['persons', 'person-details'], true) ? 'active' : '' ?>" href="/pages/persons.php"><span class="icon">👥</span>الأشخاص</a>
          <?php if ($user['role'] === 'judge'): ?>
            <a class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>" href="/pages/users.php"><span class="icon">👤</span>المستخدمون</a>
            <a class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>" href="/pages/reports.php"><span class="icon">📊</span>التقارير</a>
            <a class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>" href="/pages/settings.php"><span class="icon">⚙️</span>الإعدادات</a>
          <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
          <div class="role-shortcut">
            <strong>المستخدم الحالي</strong>
            <span><?= e($roleLabel) ?></span>
          </div>
        </div>
      </aside>

      <div class="content">
        <header class="topbar">
          <div class="topbar-left">
            <button class="mobile-toggle" type="button" aria-label="فتح القائمة">☰</button>
            <span class="breadcrumb"><?= e($pageTitle) ?></span>
          </div>
          <div class="topbar-right">
            <div class="user-chip">
              <div class="avatar"><?= e($avatarLetter) ?></div>
              <div>
                <strong><?= e($user['display_name']) ?></strong><br />
                <small><?= e($roleLabel) ?></small>
              </div>
            </div>
            <button class="language-toggle" type="button" data-language-toggle>Français</button>
            <form action="/logout.php" method="post">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
              <button class="logout-link" type="submit">تسجيل الخروج</button>
            </form>
          </div>
        </header>

        <main class="page-body">
