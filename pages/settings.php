<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$errors = [];
$flash = take_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $changeUsername = $username !== $user['username'];
    if ($displayName === '' || utf8_length($displayName) > 160) {
        $errors[] = 'أدخل اسماً ظاهراً صالحاً لا يتجاوز 160 حرفاً.';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)) {
        $errors[] = 'اسم المستخدم يجب أن يكون من 3 إلى 80 حرفاً/رقماً، أو . _ -';
    }
    $changePassword = $newPassword !== '' || $confirmation !== '';
    if ($changeUsername || $changePassword) {
        $statement = database()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $statement->execute(['id' => $user['id']]);
        $hash = $statement->fetchColumn();
        if ($currentPassword === '') {
            $errors[] = 'أدخل كلمة المرور الحالية لتغيير اسم المستخدم أو كلمة المرور.';
        } elseif (!is_string($hash) || !password_verify($currentPassword, $hash)) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة.';
        }
    }
    if ($changePassword) {
        if (strlen($newPassword) < 12) {
            $errors[] = 'كلمة المرور الجديدة يجب ألا تقل عن 12 حرفاً.';
        }
        if ($newPassword !== $confirmation) {
            $errors[] = 'تأكيد كلمة المرور غير مطابق.';
        }
    }
    if ($errors === []) {
        $sql = 'UPDATE users SET username = :username, display_name = :display_name';
        $values = ['username' => $username, 'display_name' => $displayName, 'id' => $user['id']];
        if ($changePassword) {
            $sql .= ', password_hash = :password_hash';
            $values['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        try {
            $statement = database()->prepare($sql);
            $statement->execute($values);
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['display_name'] = $displayName;
            set_flash('success', 'تم تحديث إعدادات الحساب.');
            redirect('/pages/settings.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'اسم المستخدم مستخدم من حساب آخر.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'الإعدادات';
$activePage = 'settings';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header"><div><h2>الإعدادات</h2><div class="page-subtitle">تحديث بيانات حسابك وكلمة المرور</div></div></div>
<?php if ($flash !== null): ?><div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($errors !== []): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="form-card">
  <form method="post" action="/pages/settings.php" class="contract-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
    <div class="form-grid">
      <div class="field half"><label for="displayName">الاسم الظاهر</label><input id="displayName" name="display_name" maxlength="160" value="<?= e((string) ($_POST['display_name'] ?? $user['display_name'])) ?>" required /></div>
      <div class="field half"><label for="username">اسم المستخدم</label><input id="username" name="username" pattern="[a-zA-Z0-9._\-]{3,80}" maxlength="80" autocomplete="username" value="<?= e((string) ($_POST['username'] ?? $user['username'])) ?>" required /></div>
    </div>
    <p class="small-muted">لتغيير اسم المستخدم، أدخل كلمة المرور الحالية.</p>
    <div class="form-section"><h3>تغيير كلمة المرور (اختياري)</h3>
      <div class="form-grid">
        <div class="field"><label for="currentPassword">كلمة المرور الحالية</label><input id="currentPassword" name="current_password" type="password" autocomplete="current-password" /></div>
        <div class="field"><label for="newPassword">كلمة المرور الجديدة</label><input id="newPassword" name="new_password" type="password" minlength="12" autocomplete="new-password" /></div>
        <div class="field"><label for="passwordConfirmation">تأكيد كلمة المرور</label><input id="passwordConfirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" /></div>
      </div>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit">حفظ التغييرات</button></div>
  </form>
</section>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
