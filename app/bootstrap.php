<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_name('archive_contrats_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    http_response_code(500);
    echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>خطأ</title>';
    echo '<main style="font-family:Segoe UI,Tahoma,sans-serif;max-width:42rem;margin:4rem auto;padding:1rem">';
    echo '<h1>تعذر إكمال الطلب</h1><p>تحقق من إعدادات قاعدة البيانات وسجل أخطاء PHP ثم أعد المحاولة.</p></main></html>';
});

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function utf8_length(string $value): int
{
    $count = preg_match_all('/./us', $value);
    return $count === false ? 0 : $count;
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submitted) || !is_string($known) || !hash_equals($known, $submitted)) {
        http_response_code(403);
        exit('طلب غير صالح. أعد تحميل الصفحة وحاول مجدداً.');
    }
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function require_auth(): array
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    $user = current_user();

    if ($user === null) {
        redirect('/pages/login.php');
    }

    $statement = database()->prepare(
        'SELECT id, username, display_name, role, is_active FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $user['id']]);
    $account = $statement->fetch();

    if ($account === false || !(bool) $account['is_active']) {
        $_SESSION = [];
        session_destroy();
        redirect('/pages/login.php');
    }

    $_SESSION['user'] = [
        'id' => (int) $account['id'],
        'username' => $account['username'],
        'display_name' => $account['display_name'],
        'role' => $account['role'],
    ];

    return $_SESSION['user'];
}

function require_judge(): array
{
    $user = require_auth();

    if (($user['role'] ?? '') !== 'judge') {
        http_response_code(403);
        exit('ليست لديك صلاحية الوصول إلى هذه الصفحة.');
    }

    return $user;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function contract_types(): array
{
    return [
        'الزواج' => ['عقد الزواج', 'عقد الطلاق', 'عقد الرجعة'],
        'الأملاك' => ['عقد البيع', 'عقد الهبة', 'عقد الصدقة', 'عقد القسمة'],
        'التركات' => ['الإراثة', 'حصر التركة', 'المخارجة'],
        'مختلفة' => ['الوكالة', 'الإقرار', 'الصلح'],
    ];
}

function property_types(): array
{
    return [
        'سيارة' => [
            'make_model' => 'العلامة والطراز',
            'registration' => 'رقم التسجيل',
            'chassis' => 'رقم الهيكل',
            'color' => 'اللون',
        ],
        'بقعة أرضية' => [
            'area' => 'المساحة',
            'location' => 'الموقع',
            'parcel_reference' => 'رقم الرسم أو القطعة',
            'boundaries' => 'الحدود',
        ],
        'منزل' => [
            'address' => 'العنوان أو الموقع',
            'area' => 'المساحة',
            'floors' => 'عدد الطوابق',
            'title_reference' => 'مرجع الملكية',
        ],
        'محل تجاري' => [
            'location' => 'الموقع',
            'area' => 'المساحة',
            'title_reference' => 'مرجع الملكية',
            'commercial_activity' => 'النشاط التجاري',
        ],
        'أخرى' => [
            'description' => 'الوصف',
        ],
    ];
}

function normalize_contract_properties(mixed $submitted): array
{
    if (!is_array($submitted)) {
        return [[], ['قائمة الأملاك غير صالحة.']];
    }
    if (count($submitted) > 50) {
        return [[], ['الحد الأقصى هو 50 ملكاً في العقد الواحد.']];
    }

    $types = property_types();
    $properties = [];
    $errors = [];

    foreach ($submitted as $item) {
        if (!is_array($item)) {
            $errors[] = 'بيانات أحد الأملاك غير صالحة.';
            continue;
        }
        $rawType = $item['type'] ?? '';
        if (!is_string($rawType)) {
            $errors[] = 'نوع أحد الأملاك غير صالح.';
            continue;
        }
        $type = trim($rawType);
        $characteristics = [];
        $submittedCharacteristics = $item['characteristics'] ?? [];
        if (!is_array($submittedCharacteristics)) {
            $errors[] = 'خصائص أحد الأملاك غير صالحة.';
            continue;
        }
        if ($type === '' && $submittedCharacteristics === [] && empty($item['custom_characteristics'])) {
            continue;
        }
        if (!isset($types[$type])) {
            $errors[] = 'اختر نوعاً صحيحاً لكل ملك تمت إضافته.';
            continue;
        }

        foreach ($types[$type] as $field => $label) {
            $rawValue = $submittedCharacteristics[$field] ?? '';
            if (!is_string($rawValue)) {
                $errors[] = 'إحدى خصائص الأملاك غير صالحة.';
                continue 2;
            }
            $value = trim($rawValue);
            if (utf8_length($value) > 2000) {
                $errors[] = 'إحدى خصائص الأملاك طويلة جداً.';
                continue 2;
            }
            if ($value !== '') {
                $characteristics[$label] = $value;
            }
        }

        $custom = $item['custom_characteristics'] ?? [];
        if (!is_array($custom) || count($custom) > 20) {
            $errors[] = 'الخصائص الإضافية لأحد الأملاك غير صالحة.';
            continue;
        }
        foreach ($custom as $entry) {
            if (!is_array($entry)) {
                $errors[] = 'إحدى الخصائص الإضافية غير صالحة.';
                continue;
            }
            $rawLabel = $entry['label'] ?? '';
            $rawValue = $entry['value'] ?? '';
            if (!is_string($rawLabel) || !is_string($rawValue)) {
                $errors[] = 'اسم أو قيمة إحدى الخصائص الإضافية غير صالح.';
                continue;
            }
            $label = trim($rawLabel);
            $value = trim($rawValue);
            if ($label === '' && $value === '') {
                continue;
            }
            if ($label === '' || $value === '' || utf8_length($label) > 80 || utf8_length($value) > 2000) {
                $errors[] = 'أدخل اسماً وقيمة صالحين لكل خاصية إضافية.';
                continue;
            }
            $characteristics[$label] = $value;
        }

        $properties[] = [
            'النوع' => $type,
            'الخصائص' => $characteristics,
        ];
    }

    return [$properties, $errors];
}

function contract_properties_for_form(mixed $stored): array
{
    if (!is_array($stored)) {
        return [];
    }

    $types = property_types();
    $properties = [];
    foreach ($stored as $item) {
        if (!is_array($item) || !is_string($item['النوع'] ?? null) || !isset($types[$item['النوع']])) {
            continue;
        }
        $storedCharacteristics = is_array($item['الخصائص'] ?? null) ? $item['الخصائص'] : [];
        $characteristics = [];
        foreach ($types[$item['النوع']] as $field => $label) {
            if (isset($storedCharacteristics[$label]) && is_scalar($storedCharacteristics[$label])) {
                $characteristics[$field] = (string) $storedCharacteristics[$label];
                unset($storedCharacteristics[$label]);
            }
        }
        $customCharacteristics = [];
        foreach ($storedCharacteristics as $label => $value) {
            if (is_string($label) && is_scalar($value)) {
                $customCharacteristics[] = ['label' => $label, 'value' => (string) $value];
            }
        }
        $properties[] = [
            'type' => $item['النوع'],
            'characteristics' => $characteristics,
            'custom_characteristics' => $customCharacteristics,
        ];
    }

    return $properties;
}

function is_valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $errors = DateTimeImmutable::getLastErrors();

    return $parsed !== false
        && $parsed->format('Y-m-d') === $date
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function document_storage_directory(): string
{
    $configured = getenv('DOCUMENT_STORAGE_DIR');
    $path = $configured !== false && $configured !== ''
        ? $configured
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'archive-private-documents';

    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
        throw new RuntimeException('Could not create the private document directory.');
    }

    $realPath = realpath($path);
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($realPath === false || ($documentRoot !== false && str_starts_with(
        strtolower($realPath . DIRECTORY_SEPARATOR),
        strtolower(rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
    ))) {
        throw new RuntimeException('Document storage must be outside the public document root.');
    }

    return $realPath;
}
