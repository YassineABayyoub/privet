<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (current_user() !== null) {
    redirect('/pages/dashboard.php');
}

$username = '';
$errorKey = '';
$language = ($_POST['language'] ?? 'ar') === 'fr' ? 'fr' : 'ar';
$flash = take_flash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorKey = 'required';
    } elseif (login_is_throttled($username)) {
        $errorKey = 'locked';
    } else {
        $statement = database()->prepare(
            'SELECT id, username, display_name, password_hash, role, is_active FROM users WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $account = $statement->fetch();

        // Always run one password_verify so response time does not reveal whether the username exists.
        $passwordMatches = password_verify(
            $password,
            $account === false ? '$2y$10$vfeYGNZqThY/CzvaYbV2CuhtN8UllTHhy5R2NNLHPhOfuBiA8mRwm' : $account['password_hash']
        );

        if ($account === false || !(bool) $account['is_active'] || !$passwordMatches) {
            login_record_failure($username);
            $errorKey = 'invalid';
        } else {
            login_clear_failures($username);
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
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>أرشيف العدول — تسجيل الدخول</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap" rel="stylesheet" />
    <style>
      :root {
        --login-blue: #14284b;
        --login-blue-2: #1c3668;
        --login-brass: #c9a15a;
        --login-bg: #f2f3f0;
        --login-card: #fff;
        --login-text: #1b2430;
        --login-muted: #5b6574;
        --login-line: #cbd0d8;
        --login-error: #a33a2b;
        --login-focus: #1c3668;
      }
      @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) {
          --login-bg: #0e1726;
          --login-card: #16223a;
          --login-text: #edf0f5;
          --login-muted: #9aa6b8;
          --login-line: #34425c;
          --login-error: #f08b7b;
          --login-focus: #c9a15a;
        }
      }
      :root[data-theme="dark"] {
        --login-bg: #0e1726;
        --login-card: #16223a;
        --login-text: #edf0f5;
        --login-muted: #9aa6b8;
        --login-line: #34425c;
        --login-error: #f08b7b;
        --login-focus: #c9a15a;
      }
      html { scroll-padding-top: env(safe-area-inset-top, 0px); }
      *, *::before, *::after { box-sizing: border-box; }
      body.login-page {
        min-height: 100vh;
        min-height: 100svh;
        margin: 0;
        padding: env(safe-area-inset-top, 0px) 0 env(safe-area-inset-bottom, 0px);
        background: var(--login-bg);
        color: var(--login-text);
        font-family: "IBM Plex Sans Arabic", system-ui, "Segoe UI", sans-serif;
        line-height: 1.6;
      }
      .login-wrap { display: grid; grid-template-columns: 1.1fr 1fr; min-height: 100vh; min-height: 100svh; }
      .login-hero {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        overflow: hidden;
        padding: 56px;
        background: var(--login-blue);
        color: #fff;
      }
      .login-pattern { position: absolute; inset: 0; width: 100%; height: 100%; }
      .login-hero-copy { position: relative; max-width: 30rem; }
      .login-hero h1 {
        margin: 0 0 14px;
        color: var(--login-brass);
        font-family: Amiri, "Times New Roman", serif;
        font-size: clamp(2.6rem, 5vw, 4rem);
        line-height: 1.2;
      }
      .login-hero p { max-width: 26rem; margin: 0; color: #d8dfec; font-size: 1.05rem; }
      .login-panel { position: relative; display: flex; align-items: center; justify-content: center; padding: 32px; }
      .login-language {
        position: absolute;
        top: 20px;
        inset-inline-end: 24px;
        padding: 6px 12px;
        border: 1px solid var(--login-line);
        border-radius: 6px;
        background: transparent;
        color: var(--login-text);
        font: inherit;
        font-size: .9rem;
        cursor: pointer;
      }
      .login-card { width: 100%; max-width: 400px; padding: 36px 32px; border: 1px solid var(--login-line); border-radius: 10px; background: var(--login-card); }
      .login-card h2 { margin: 0 0 4px; font-size: 1.5rem; }
      .login-subtitle { margin: 0 0 26px; color: var(--login-muted); font-size: .95rem; }
      .login-card .notice { margin: 0 0 18px; padding: 12px 14px; border: 1px solid var(--login-line); border-radius: 6px; }
      .login-card .notice.error { border-color: var(--login-error); color: var(--login-error); }
      .login-card .notice.success { color: #18794e; }
      .login-form label { display: block; margin-bottom: 6px; font-size: .95rem; font-weight: 500; }
      .login-field { margin-bottom: 18px; }
      .login-input-wrap { position: relative; }
      .login-form input[type="text"], .login-form input[type="password"] {
        display: block;
        width: 100%;
        height: 46px;
        padding: 0 14px;
        border: 1px solid var(--login-line);
        border-radius: 6px;
        background: transparent;
        color: var(--login-text);
        font: inherit;
        transition: none;
      }
      .login-form input[type="text"]:hover, .login-form input[type="password"]:hover { border-color: var(--login-line); }
      .login-form input[type="text"]:focus, .login-form input[type="password"]:focus {
        border-color: var(--login-line);
        box-shadow: none;
      }
      .login-form input:focus-visible, .login-page button:focus-visible {
        outline: 2px solid var(--login-focus);
        outline-offset: 2px;
      }
      .login-form #password { padding-inline-end: 70px; }
      .login-eye {
        position: absolute;
        inset-block: 0;
        inset-inline-end: 0;
        padding: 0 14px;
        border: 0;
        background: transparent;
        color: var(--login-muted);
        font: inherit;
        font-size: .85rem;
        cursor: pointer;
      }
      .login-submit {
        width: 100%;
        height: 48px;
        border: 0;
        border-radius: 6px;
        background: var(--login-blue-2);
        color: #fff;
        font: inherit;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
      }
      .login-submit:hover { background: var(--login-blue); }
      .login-message { min-height: 1.4em; margin: 14px 0 0; color: var(--login-error); font-size: .9rem; }
      .login-note { margin: 22px 0 0; padding-top: 16px; border-top: 1px solid var(--login-line); color: var(--login-muted); font-size: .85rem; }
      @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .login-submit { background: var(--login-brass); color: #14284b; }
      }
      :root[data-theme="dark"] .login-submit { background: var(--login-brass); color: #14284b; }
      @media (max-width: 820px) {
        .login-wrap { grid-template-columns: 1fr; }
        .login-hero { min-height: 210px; padding: 32px 24px; }
        .login-hero h1 { font-size: 2.2rem; }
        .login-hero p { display: none; }
        .login-panel { align-items: flex-start; padding: 24px 16px 40px; }
        .login-card { padding: 28px 22px; }
      }
      @media (prefers-reduced-motion: no-preference) {
        .login-submit { transition: background .15s; }
      }
    </style>
  </head>
  <body class="login-page">
    <div class="login-wrap">
      <section class="login-hero">
        <svg class="login-pattern" aria-hidden="true" focusable="false">
          <defs>
            <pattern id="login-zellige" width="96" height="96" patternUnits="userSpaceOnUse">
              <g fill="none" stroke="#C9A15A" stroke-opacity=".28" stroke-width="1.2">
                <rect x="22" y="22" width="52" height="52" />
                <rect x="22" y="22" width="52" height="52" transform="rotate(45 48 48)" />
                <circle cx="48" cy="48" r="9" />
              </g>
              <g fill="#C9A15A" fill-opacity=".14">
                <circle cx="0" cy="0" r="4" /><circle cx="96" cy="0" r="4" />
                <circle cx="0" cy="96" r="4" /><circle cx="96" cy="96" r="4" />
              </g>
            </pattern>
            <linearGradient id="login-fade" x1="0" y1="0" x2="0" y2="1">
              <stop offset=".35" stop-color="#14284B" stop-opacity="0" />
              <stop offset="1" stop-color="#14284B" stop-opacity=".95" />
            </linearGradient>
          </defs>
          <rect width="100%" height="100%" fill="url(#login-zellige)" />
          <rect width="100%" height="100%" fill="url(#login-fade)" />
        </svg>
        <div class="login-hero-copy">
          <h1 data-i="hero">أرشيف العدول</h1>
          <p data-i="heroSub">العقود والرسوم والوثائق العدلية، محفوظة ومفهرسة في مكان واحد.</p>
        </div>
      </section>
      <main class="login-panel">
        <button class="login-language" id="language-toggle" type="button">Français</button>
        <section class="login-card" aria-labelledby="login-title">
          <h2 id="login-title" data-i="title">تسجيل الدخول</h2>
          <p class="login-subtitle" data-i="subtitle">ادخل إلى أرشيف العقود والوثائق.</p>
          <?php if ($flash !== null): ?>
            <div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
          <?php endif; ?>
          <form class="login-form" id="login-form" method="post" action="/pages/login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
            <input type="hidden" id="login-language" name="language" value="<?= e($language) ?>" />
            <div class="login-field">
              <label for="username" data-i="user">اسم المستخدم</label>
              <input id="username" name="username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= e($username) ?>" required autofocus />
            </div>
            <div class="login-field">
              <label for="password" data-i="pass">كلمة المرور</label>
              <div class="login-input-wrap">
                <input id="password" name="password" type="password" autocomplete="current-password" required />
                <button class="login-eye" id="password-toggle" type="button" aria-controls="password" data-i="show">إظهار</button>
              </div>
            </div>
            <button class="login-submit" type="submit" data-i="submit">دخول</button>
            <p class="login-message" id="login-message" role="alert" aria-live="polite" data-error="<?= e($errorKey) ?>"><?php
              if ($errorKey === 'required') {
                  echo $language === 'fr' ? 'Saisissez votre nom d’utilisateur et votre mot de passe.' : 'يرجى إدخال اسم المستخدم وكلمة المرور.';
              } elseif ($errorKey === 'invalid') {
                  echo $language === 'fr' ? 'Le nom d’utilisateur ou le mot de passe est incorrect.' : 'اسم المستخدم أو كلمة المرور غير صحيحة.';
              } elseif ($errorKey === 'locked') {
                  echo $language === 'fr' ? 'Trop de tentatives échouées. Réessayez dans quelques minutes.' : 'محاولات فاشلة كثيرة. أعد المحاولة بعد بضع دقائق.';
              }
            ?></p>
          </form>
          <p class="login-note" data-i="note">الدخول مخصّص للعدول والموظفين المخوّلين.</p>
        </section>
      </main>
    </div>
    <script>
      const translations = {
        ar: {
          hero: "أرشيف العدول",
          heroSub: "العقود والرسوم والوثائق العدلية، محفوظة ومفهرسة في مكان واحد.",
          title: "تسجيل الدخول",
          subtitle: "ادخل إلى أرشيف العقود والوثائق.",
          user: "اسم المستخدم",
          pass: "كلمة المرور",
          show: "إظهار",
          hide: "إخفاء",
          submit: "دخول",
          note: "الدخول مخصّص للعدول والموظفين المخوّلين.",
          required: "يرجى إدخال اسم المستخدم وكلمة المرور.",
          invalid: "اسم المستخدم أو كلمة المرور غير صحيحة.",
          locked: "محاولات فاشلة كثيرة. أعد المحاولة بعد بضع دقائق.",
          other: "Français",
          titleTag: "أرشيف العدول — تسجيل الدخول"
        },
        fr: {
          hero: "Archives des adouls",
          heroSub: "Contrats, actes et documents adoulaires, conservés et indexés en un seul lieu.",
          title: "Connexion",
          subtitle: "Accédez à l’archive des contrats et documents.",
          user: "Nom d’utilisateur",
          pass: "Mot de passe",
          show: "Afficher",
          hide: "Masquer",
          submit: "Se connecter",
          note: "Accès réservé aux adouls et au personnel autorisé.",
          required: "Saisissez votre nom d’utilisateur et votre mot de passe.",
          invalid: "Le nom d’utilisateur ou le mot de passe est incorrect.",
          locked: "Trop de tentatives échouées. Réessayez dans quelques minutes.",
          other: "العربية",
          titleTag: "Archives des adouls — Connexion"
        }
      };
      const get = (id) => document.getElementById(id);
      let language = get("login-language").value === "fr"
        ? "fr"
        : (localStorage.getItem("archive-language") === "fr" ? "fr" : "ar");

      function applyLanguage() {
        const text = translations[language];
        const root = document.documentElement;
        root.lang = language;
        root.dir = language === "ar" ? "rtl" : "ltr";
        document.title = text.titleTag;
        document.querySelectorAll("[data-i]").forEach((element) => {
          const key = element.dataset.i;
          if (key !== "show") element.textContent = text[key];
        });
        get("password-toggle").textContent =
          get("password").type === "password" ? text.show : text.hide;
        get("language-toggle").textContent = text.other;
        get("login-language").value = language;
        localStorage.setItem("archive-language", language);
        const message = get("login-message");
        if (message.dataset.error) message.textContent = text[message.dataset.error];
      }

      get("language-toggle").addEventListener("click", () => {
        language = language === "ar" ? "fr" : "ar";
        applyLanguage();
      });

      get("password-toggle").addEventListener("click", () => {
        const password = get("password");
        password.type = password.type === "password" ? "text" : "password";
        get("password-toggle").textContent =
          password.type === "password" ? translations[language].show : translations[language].hide;
      });

      get("login-form").addEventListener("submit", (event) => {
        const message = get("login-message");
        if (!get("username").value.trim() || !get("password").value) {
          event.preventDefault();
          message.dataset.error = "required";
          message.textContent = translations[language].required;
          get("username").focus();
          return;
        }
        message.dataset.error = "";
        message.textContent = "";
      });

      applyLanguage();
    </script>
  </body>
</html>
