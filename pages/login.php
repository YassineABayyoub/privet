<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (current_user() !== null) {
    redirect('/pages/dashboard.php');
}

$error = '';
$flash = take_flash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $statement = database()->prepare(
            'SELECT id, username, display_name, password_hash, role, is_active FROM users WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $account = $statement->fetch();

        if (
            $account === false
            || !(bool) $account['is_active']
            || !password_verify($password, $account['password_hash'])
        ) {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $account['id'],
                'username' => $account['username'],
                'display_name' => $account['display_name'],
                'role' => $account['role'],
            ];
            unset($_SESSION['csrf_token']);
            redirect('/pages/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تسجيل الدخول | أرشيف العقود العدلية</title>
    <link rel="stylesheet" href="/css/style.css" />
    <style>
      body { min-height: 100vh; display: grid; place-items: center; }
      .login-shell { width: min(100% - 2rem, 460px); padding: 2rem; background: var(--surface); border: 1px solid var(--border); border-radius: 24px; box-shadow: var(--shadow); }
      .login-brand { text-align: center; margin-bottom: 1.5rem; }
      .login-brand img { margin: 0 auto 0.8rem; }
      .login-brand h1 { margin: 0; color: var(--primary-strong); font-size: 1.8rem; }
      .login-form { display: flex; flex-direction: column; gap: 1rem; }
      .login-form label { display: block; color: var(--primary-strong); font-weight: 700; margin-bottom: 0.4rem; }
      .login-error { margin-bottom: 1rem; }
    </style>
  </head>
  <body>
    <main class="login-shell">
      <div class="login-brand">
        <img src="/assets/images/logo.svg" alt="" />
        <h1>أرشيف العقود العدلية</h1>
        <p class="page-subtitle">تسجيل الدخول إلى نظام الأرشيف</p>
      </div>
        <?php if ($flash !== null): ?><div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="notice error login-error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>
      <form class="login-form" method="post" action="/pages/login.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
        <div>
          <label for="username">اسم المستخدم</label>
          <input id="username" name="username" type="text" autocomplete="username" required autofocus />
        </div>
        <div>
          <label for="password">كلمة المرور</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required />
        </div>
        <button class="btn btn-primary" type="submit">تسجيل الدخول</button>
      </form>
      <p class="login-footer">الدخول محمي بحساب مستخدم مسجل في قاعدة البيانات.</p>
    </main>
    <script src="/js/main.js"></script>
  </body>
</html>
