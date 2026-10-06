<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$pdo = database();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount > 0) {
    redirect(current_user() === null ? '/pages/login.php' : '/pages/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)) {
        $errors[] = 'اسم المستخدم يجب أن يتكون من 3 إلى 80 حرفاً لاتينياً أو رقماً أو . _ -';
    }
    if ($displayName === '' || utf8_length($displayName) > 160) {
        $errors[] = 'أدخل اسماً ظاهراً صالحاً لا يتجاوز 160 حرفاً.';
    }
    if (strlen($password) < 12) {
        $errors[] = 'كلمة المرور يجب أن تحتوي على 12 حرفاً على الأقل.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'تأكيد كلمة المرور غير مطابق.';
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();
            if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                $pdo->rollBack();
                redirect('/pages/login.php');
            }

            $statement = $pdo->prepare(
                'INSERT INTO users (username, display_name, password_hash, role) VALUES (:username, :display_name, :password_hash, \'judge\')'
            );
            $statement->execute([
                'username' => $username,
                'display_name' => $displayName,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $pdo->commit();
            set_flash('success', 'تم إنشاء حساب المسؤول. يمكنك تسجيل الدخول الآن.');
            redirect('/pages/login.php');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                $errors[] = 'اسم المستخدم مستخدم مسبقاً.';
            } else {
                throw $exception;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>إعداد المسؤول | أرشيف العقود العدلية</title>
    <link rel="stylesheet" href="/css/style.css" />
    <style>
      body { min-height: 100vh; display: grid; place-items: center; position: relative; }
      .setup-language { position: fixed; top: 1rem; inset-inline-end: 1rem; padding: .55rem .8rem; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); color: var(--primary); font: inherit; cursor: pointer; }
      .login-shell { width: min(100% - 2rem, 480px); padding: 2rem; background: var(--surface); border: 1px solid var(--border); border-radius: 24px; box-shadow: var(--shadow); }
      .login-shell h1 { color: var(--primary-strong); }
      .setup-form { display: grid; gap: 1rem; }
      .setup-form label { display: block; font-weight: 700; margin-bottom: .4rem; }
    </style>
  </head>
  <body>
    <button class="setup-language" type="button" data-language-toggle>Français</button>
    <main class="login-shell">
      <h1>إنشاء حساب المسؤول الأول</h1>
      <p>هذه الخطوة متاحة مرة واحدة فقط قبل إنشاء أي مستخدم.</p>
      <?php if ($errors !== []): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form class="setup-form" method="post" action="/pages/setup.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
        <div><label for="username">اسم المستخدم</label><input id="username" name="username" pattern="[a-zA-Z0-9._\-]{3,80}" maxlength="80" required value="<?= e((string) ($_POST['username'] ?? '')) ?>" /></div>
        <div><label for="display_name">الاسم الظاهر</label><input id="display_name" name="display_name" maxlength="160" required value="<?= e((string) ($_POST['display_name'] ?? '')) ?>" /></div>
        <div><label for="password">كلمة المرور (12 حرفاً على الأقل)</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required /></div>
        <div><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required /></div>
        <button class="btn btn-primary" type="submit">إنشاء حساب المسؤول</button>
      </form>
    </main>
    <script src="/js/i18n.js?v=<?= (int) filemtime(__DIR__ . '/../js/i18n.js') ?>"></script>
  </body>
</html>
