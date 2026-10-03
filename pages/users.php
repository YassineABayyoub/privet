<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_judge();
$pdo = database();
$errors = [];
$flash = take_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $role = (string) ($_POST['role'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3-80 حرفاً/رقماً أو . _ -';
        }
        if ($displayName === '' || utf8_length($displayName) > 160) {
            $errors[] = 'أدخل اسماً ظاهراً صالحاً.';
        }
        if (!in_array($role, ['judge', 'clerk'], true)) {
            $errors[] = 'الدور المختار غير صالح.';
        }
        if (strlen($password) < 12) {
            $errors[] = 'كلمة المرور يجب ألا تقل عن 12 حرفاً.';
        }
        if ($errors === []) {
            try {
                $create = $pdo->prepare('INSERT INTO users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, :role)');
                $create->execute([
                    'username' => $username,
                    'display_name' => $displayName,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                ]);
                set_flash('success', 'تم إنشاء حساب المستخدم.');
                redirect('/pages/users.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'اسم المستخدم مستخدم مسبقاً.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'toggle') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$targetId || $targetId < 1) {
            $errors[] = 'معرف المستخدم غير صالح.';
        } elseif ($targetId === $user['id']) {
            $errors[] = 'لا يمكنك تعطيل حسابك الحالي.';
        } else {
            $pdo->beginTransaction();
            $targetStatement = $pdo->prepare('SELECT id, role, is_active FROM users WHERE id = :id FOR UPDATE');
            $targetStatement->execute(['id' => $targetId]);
            $target = $targetStatement->fetch();
            if ($target === false) {
                $pdo->rollBack();
                $errors[] = 'المستخدم غير موجود.';
            } else {
                $newStatus = !(bool) $target['is_active'];
                $activeJudges = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'judge' AND is_active = 1")->fetchColumn();
                if (!$newStatus && $target['role'] === 'judge' && $activeJudges <= 1) {
                    $pdo->rollBack();
                    $errors[] = 'يجب الاحتفاظ بحساب مسؤول واحد نشط على الأقل.';
                } else {
                    $update = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
                    $update->execute(['is_active' => $newStatus ? 1 : 0, 'id' => $targetId]);
                    $pdo->commit();
                    set_flash('success', $newStatus ? 'تم تفعيل الحساب.' : 'تم تعطيل الحساب.');
                    redirect('/pages/users.php');
                }
            }
        }
    } elseif ($action === 'delete') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$targetId || $targetId < 1) {
            $errors[] = 'معرف المستخدم غير صالح.';
        } elseif ($targetId === $user['id']) {
            $errors[] = 'لا يمكنك حذف حسابك الحالي.';
        } else {
            $pdo->beginTransaction();
            try {
                $targetStatement = $pdo->prepare('SELECT id, username, role, is_active FROM users WHERE id = :id FOR UPDATE');
                $targetStatement->execute(['id' => $targetId]);
                $target = $targetStatement->fetch();
                if ($target === false) {
                    $pdo->rollBack();
                    $errors[] = 'المستخدم غير موجود.';
                } else {
                    $references = $pdo->prepare(
                        'SELECT
                            (SELECT COUNT(*) FROM contracts WHERE created_by = :contract_creator) AS contract_count,
                            (SELECT COUNT(*) FROM contract_documents WHERE uploaded_by = :document_uploader) AS document_count'
                    );
                    $references->execute([
                        'contract_creator' => $targetId,
                        'document_uploader' => $targetId,
                    ]);
                    $referenceCounts = $references->fetch();
                    if ((int) $referenceCounts['contract_count'] > 0 || (int) $referenceCounts['document_count'] > 0) {
                        $pdo->rollBack();
                        $errors[] = 'لا يمكن حذف هذا الحساب لأنه مرتبط بعقود أو وثائق محفوظة. يمكنك تعطيله بدلاً من ذلك.';
                    } else {
                        $activeJudges = (int) $pdo->query(
                            "SELECT COUNT(*) FROM users WHERE role = 'judge' AND is_active = 1"
                        )->fetchColumn();
                        if ($target['role'] === 'judge' && (bool) $target['is_active'] && $activeJudges <= 1) {
                            $pdo->rollBack();
                            $errors[] = 'لا يمكن حذف آخر حساب مسؤول نشط.';
                        } else {
                            $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
                            $delete->execute(['id' => $targetId]);
                            $pdo->commit();
                            set_flash('success', 'تم حذف حساب المستخدم نهائياً.');
                            redirect('/pages/users.php');
                        }
                    }
                }
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($exception->getCode() === '23000') {
                    $errors[] = 'لا يمكن حذف هذا الحساب لأنه مرتبط بسجلات محفوظة. يمكنك تعطيله بدلاً من ذلك.';
                } else {
                    throw $exception;
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }
    } else {
        $errors[] = 'إجراء غير معروف.';
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$list = $pdo->prepare(
    'SELECT id, username, display_name, role, is_active, created_at FROM users
     WHERE (:q = \'\' OR username LIKE :username_pattern OR display_name LIKE :display_pattern)
     ORDER BY role, display_name'
);
$list->execute([
    'q' => $q,
    'username_pattern' => '%' . $q . '%',
    'display_pattern' => '%' . $q . '%',
]);
$users = $list->fetchAll();
$pageTitle = 'المستخدمون';
$activePage = 'users';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header"><div><h2>المستخدمون</h2><div class="page-subtitle">إنشاء الحسابات وتفعيلها أو تعطيلها أو حذفها نهائياً إذا لم تكن مرتبطة بسجلات محفوظة</div></div></div>
<?php if ($flash !== null): ?><div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($errors !== []): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="form-card">
  <h3>إضافة مستخدم</h3>
  <form method="post" action="/pages/users.php" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" /><input type="hidden" name="action" value="create" />
    <div class="field"><label for="displayName">الاسم</label><input id="displayName" name="display_name" maxlength="160" required /></div>
    <div class="field"><label for="username">اسم المستخدم</label><input id="username" name="username" pattern="[a-zA-Z0-9._\-]{3,80}" required /></div>
    <div class="field"><label for="role">الدور</label><select id="role" name="role"><option value="clerk">الكاتبة</option><option value="judge">العدل</option></select></div>
    <div class="field"><label for="password">كلمة المرور (12 حرفاً على الأقل)</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required /></div>
    <div class="field"><label>&nbsp;</label><button class="btn btn-primary" type="submit">إنشاء الحساب</button></div>
  </form>
</section>
<section class="form-section">
  <div class="page-tools"><h3>الحسابات المسجلة (<?= count($users) ?>)</h3><form method="get" action="/pages/users.php" class="search-box"><span>🔎</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="بحث عن مستخدم" /></form></div>
  <div class="table-wrap"><table class="data-table"><thead><tr><th>الاسم</th><th>اسم المستخدم</th><th>الدور</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
  <?php foreach ($users as $account): ?><tr><td><?= e($account['display_name']) ?><?= (int) $account['id'] === $user['id'] ? ' (أنت)' : '' ?></td><td><?= e($account['username']) ?></td><td><?= $account['role'] === 'judge' ? 'العدل' : 'الكاتبة' ?></td><td><span class="badge <?= $account['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $account['is_active'] ? 'نشط' : 'معطل' ?></span></td><td><?php if ((int) $account['id'] !== $user['id']): ?><form method="post" action="/pages/users.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" /><input type="hidden" name="action" value="toggle" /><input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>" /><button class="action-btn <?= $account['is_active'] ? 'danger' : 'success' ?>" type="submit"><?= $account['is_active'] ? 'تعطيل' : 'إعادة تفعيل' ?></button></form><form method="post" action="/pages/users.php" onsubmit="return confirm('سيتم حذف هذا الحساب نهائياً. هل تريد المتابعة؟');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" /><input type="hidden" name="action" value="delete" /><input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>" /><button class="action-btn danger" type="submit">حذف نهائياً</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
