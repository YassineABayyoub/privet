<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$pdo = database();
$categories = contract_types();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $number = trim((string) ($_POST['numero'] ?? ''));
    $contractDate = trim((string) ($_POST['date'] ?? ''));
    $registeredDate = trim((string) ($_POST['date_enregistrement'] ?? ''));
    $category = trim((string) ($_POST['categorie'] ?? ''));
    $actType = trim((string) ($_POST['type_acte'] ?? ''));
    $archiveNumber = trim((string) ($_POST['numero_archive'] ?? ''));
    $reference = trim((string) ($_POST['reference'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $specificNotes = trim((string) ($_POST['specific_notes'] ?? ''));
    [$properties, $propertyErrors] = normalize_contract_properties($_POST['properties'] ?? []);
    if ($category !== 'الأملاك') {
        $properties = [];
        $propertyErrors = [];
    }
    $errors = array_merge($errors, $propertyErrors);
    $submittedPeople = $_POST['personnes'] ?? [];
    $people = [];

    if ($number === '' || utf8_length($number) > 80) {
        $errors[] = 'رقم العقد مطلوب ويجب ألا يتجاوز 80 حرفاً.';
    }
    if (utf8_length($archiveNumber) > 100 || utf8_length($reference) > 255) {
        $errors[] = 'رقم الأرشيف أو المرجع يتجاوز الحد المسموح.';
    }
    if (strlen($notes) > 60000 || strlen($specificNotes) > 60000) {
        $errors[] = 'النص المدخل طويل جداً.';
    }
    if (!isset($categories[$category]) || !in_array($actType, $categories[$category] ?? [], true)) {
        $errors[] = 'اختر تصنيفاً ونوع عقد صحيحين.';
    }
    foreach (['تاريخ العقد' => $contractDate, 'تاريخ التسجيل' => $registeredDate] as $label => $date) {
        if ($date !== '' && !is_valid_date($date)) {
            $errors[] = $label . ' غير صالح.';
        }
    }
    if ($contractDate === '') {
        $errors[] = 'تاريخ العقد مطلوب.';
    }

    if (is_array($submittedPeople)) {
        foreach ($submittedPeople as $person) {
            if (!is_array($person)) {
                continue;
            }

            $firstName = trim((string) ($person['nom'] ?? ''));
            $lastName = trim((string) ($person['prenom'] ?? ''));
            $identityNumber = trim((string) ($person['cin'] ?? ''));
            $partyRole = trim((string) ($person['role'] ?? ''));

            if ($firstName === '' && $lastName === '' && $identityNumber === '' && $partyRole === '') {
                continue;
            }
            if ($firstName === '' || $lastName === '' || $partyRole === '') {
                $errors[] = 'لكل طرف تمت إضافته، أدخل الاسم والنسب والدور.';
                continue;
            }
            if (utf8_length($firstName) > 120 || utf8_length($lastName) > 160 || utf8_length($identityNumber) > 80 || utf8_length($partyRole) > 100) {
                $errors[] = 'بيانات أحد الأطراف تتجاوز الحد المسموح.';
                continue;
            }

            $people[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'identity_number' => $identityNumber,
                'party_role' => $partyRole,
            ];
        }
    } else {
        $errors[] = 'قائمة الأطراف غير صالحة.';
    }

    $isMarriageContract = $category === 'الزواج' && $actType === 'عقد الزواج';
    if ($isMarriageContract) {
        $roleCounts = array_count_values(array_column($people, 'party_role'));
        foreach ($people as $person) {
            if (!in_array($person['party_role'], ['الزوج', 'الزوجة', 'شاهد'], true)) {
                $errors[] = 'الأطراف المسموحة في عقد الزواج هي الزوج والزوجة والشاهد فقط.';
                break;
            }
        }
        if (($roleCounts['الزوج'] ?? 0) !== 1 || ($roleCounts['الزوجة'] ?? 0) !== 1) {
            $errors[] = 'يجب إدخال بيانات الزوج والزوجة مرة واحدة لكل عقد زواج.';
        }
    } elseif ($people === []) {
        $errors[] = 'أضف طرفاً واحداً على الأقل مع الاسم والنسب والدور.';
    }

    $documents = [];
    $upload = $_FILES['documents'] ?? null;
    if (is_array($upload) && is_array($upload['name'] ?? null)) {
        if (!class_exists(\finfo::class)) {
            throw new RuntimeException('The PHP fileinfo extension is required for document uploads.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $allowedMimeTypes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        $uploadCount = count($upload['name']);
        if ($uploadCount > 10) {
            $errors[] = 'يمكن إرفاق 10 ملفات كحد أقصى بالعقد الواحد.';
        }
        for ($index = 0; $index < min($uploadCount, 10); $index++) {
            $uploadError = $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errors[] = 'تعذر استلام أحد الملفات. تحقق من حدود رفع الملفات في PHP.';
                continue;
            }
            $temporaryPath = (string) ($upload['tmp_name'][$index] ?? '');
            $originalName = basename((string) ($upload['name'][$index] ?? ''));
            $size = (int) ($upload['size'][$index] ?? 0);
            $mimeType = $temporaryPath !== '' ? $finfo->file($temporaryPath) : false;
            if ($size < 1 || $size > 10 * 1024 * 1024) {
                $errors[] = 'حجم كل ملف يجب ألا يتجاوز 10 ميغابايت.';
                continue;
            }
            if (!is_string($mimeType) || !isset($allowedMimeTypes[$mimeType])) {
                $errors[] = 'نوع ملف غير مدعوم. الأنواع المقبولة: PDF وJPG وPNG.';
                continue;
            }
            if (utf8_length($originalName) > 255) {
                $errors[] = 'اسم أحد الملفات طويل جداً.';
                continue;
            }
            $documents[] = [
                'tmp_name' => $temporaryPath,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'extension' => $allowedMimeTypes[$mimeType],
                'size' => $size,
                'stored_name' => '',
                'storage_path' => '',
            ];
        }
    } elseif ($upload !== null) {
        $errors[] = 'بيانات الملفات المرفوعة غير صالحة.';
    }

    if ($errors === []) {
        $specificPayload = [];
        if ($properties !== []) {
            $specificPayload['الأملاك'] = $properties;
        }
        if ($specificNotes !== '') {
            $specificPayload['ملاحظات إضافية'] = $specificNotes;
        }
        $specificData = $specificPayload === [] ? null : json_encode(
            $specificPayload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        try {
            if ($documents !== []) {
                $storageDirectory = document_storage_directory();
                foreach ($documents as &$document) {
                    $document['stored_name'] = bin2hex(random_bytes(16)) . '.' . $document['extension'];
                    $document['storage_path'] = $storageDirectory . DIRECTORY_SEPARATOR . $document['stored_name'];
                    if (!move_uploaded_file($document['tmp_name'], $document['storage_path'])) {
                        throw new RuntimeException('Could not store an uploaded document.');
                    }
                }
                unset($document);
            }

            $pdo->beginTransaction();
            $insertContract = $pdo->prepare(
                'INSERT INTO contracts
                  (contract_number, contract_date, registered_date, category, act_type, archive_number, reference_text, notes, specific_data, created_by)
                 VALUES
                  (:contract_number, :contract_date, :registered_date, :category, :act_type, :archive_number, :reference_text, :notes, :specific_data, :created_by)'
            );
            $insertContract->execute([
                'contract_number' => $number,
                'contract_date' => $contractDate,
                'registered_date' => $registeredDate === '' ? null : $registeredDate,
                'category' => $category,
                'act_type' => $actType,
                'archive_number' => $archiveNumber === '' ? null : $archiveNumber,
                'reference_text' => $reference === '' ? null : $reference,
                'notes' => $notes === '' ? null : $notes,
                'specific_data' => $specificData,
                'created_by' => $user['id'],
            ]);
            $contractId = (int) $pdo->lastInsertId();

            $findPerson = $pdo->prepare('SELECT id FROM persons WHERE identity_number = :identity_number LIMIT 1');
            $insertPerson = $pdo->prepare(
                'INSERT INTO persons (first_name, last_name, identity_number) VALUES (:first_name, :last_name, :identity_number)'
            );
            $insertParty = $pdo->prepare(
                'INSERT INTO contract_parties (contract_id, person_id, party_role) VALUES (:contract_id, :person_id, :party_role)'
            );
            $insertDocument = $pdo->prepare(
                'INSERT INTO contract_documents (contract_id, original_name, stored_name, mime_type, file_size, uploaded_by)
                 VALUES (:contract_id, :original_name, :stored_name, :mime_type, :file_size, :uploaded_by)'
            );

            $linkedParties = [];
            foreach ($people as $person) {
                $personId = null;
                if ($person['identity_number'] !== '') {
                    $findPerson->execute(['identity_number' => $person['identity_number']]);
                    $existingPersonId = $findPerson->fetchColumn();
                    if ($existingPersonId !== false) {
                        $personId = (int) $existingPersonId;
                    }
                }

                if ($personId === null) {
                    $insertPerson->execute([
                        'first_name' => $person['first_name'],
                        'last_name' => $person['last_name'],
                        'identity_number' => $person['identity_number'] === '' ? null : $person['identity_number'],
                    ]);
                    $personId = (int) $pdo->lastInsertId();
                }

                $partyKey = $personId . ':' . $person['party_role'];
                if (isset($linkedParties[$partyKey])) {
                    continue;
                }
                $linkedParties[$partyKey] = true;
                $insertParty->execute([
                    'contract_id' => $contractId,
                    'person_id' => $personId,
                    'party_role' => $person['party_role'],
                ]);
            }

            foreach ($documents as $document) {
                $insertDocument->execute([
                    'contract_id' => $contractId,
                    'original_name' => $document['original_name'],
                    'stored_name' => $document['stored_name'],
                    'mime_type' => $document['mime_type'],
                    'file_size' => $document['size'],
                    'uploaded_by' => $user['id'],
                ]);
            }

            $pdo->commit();
            set_flash('success', 'تم حفظ العقد والأطراف في قاعدة البيانات.');
            redirect('/pages/contracts.php');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($documents as $document) {
                if ($document['storage_path'] !== '' && is_file($document['storage_path'])) {
                    unlink($document['storage_path']);
                }
            }
            if ($exception->getCode() === '23000') {
                $errors[] = 'رقم العقد أو رقم الأرشيف مستخدم مسبقاً.';
            } else {
                throw $exception;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($documents as $document) {
                if ($document['storage_path'] !== '' && is_file($document['storage_path'])) {
                    unlink($document['storage_path']);
                }
            }
            throw $exception;
        }
    }
}

$old = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
$selectedCategory = (string) ($old['categorie'] ?? 'الأملاك');
$selectedType = (string) ($old['type_acte'] ?? '');
$formPeople = $old['personnes'] ?? [['role' => '', 'nom' => '', 'prenom' => '', 'cin' => '']];
if (!is_array($formPeople) || $formPeople === []) {
    $formPeople = [['role' => '', 'nom' => '', 'prenom' => '', 'cin' => '']];
}
$formProperties = $old['properties'] ?? [];
if (!is_array($formProperties)) {
    $formProperties = [];
}
$pageTitle = 'إضافة عقد جديد';
$activePage = 'add-contract';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header">
  <div><h2>إضافة عقد جديد</h2><div class="page-subtitle">سيتم حفظ بيانات العقد والأطراف في قاعدة البيانات بعد الإرسال.</div></div>
  <div class="header-actions"><a class="btn btn-secondary" href="/pages/contracts.php">عودة إلى القائمة</a></div>
</div>
<?php if ($errors !== []): ?>
  <div class="notice error" role="alert"><strong>تعذر حفظ العقد:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form class="form-card contract-form" method="post" enctype="multipart/form-data" action="/pages/add-contract.php">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
  <section class="form-section">
    <h3>اختيار التصنيف والنوع</h3>
    <div class="form-grid">
      <div class="field half">
        <label for="contractCategory">التصنيف</label>
        <select id="contractCategory" name="categorie" required>
          <?php foreach (array_keys($categories) as $item): ?><option value="<?= e($item) ?>" <?= $selectedCategory === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field half">
        <label for="contractType">نوع العقد</label>
        <select id="contractType" name="type_acte" data-selected-type="<?= e($selectedType) ?>" required></select>
      </div>
    </div>
  </section>

  <section class="form-section">
    <h3>معلومات العقد</h3>
    <div class="form-grid">
      <div class="field"><label for="numero">رقم العقد</label><input id="numero" type="text" name="numero" value="<?= e((string) ($old['numero'] ?? '')) ?>" maxlength="80" required /></div>
      <div class="field"><label for="date">تاريخ العقد</label><input id="date" type="date" name="date" value="<?= e((string) ($old['date'] ?? '')) ?>" required /></div>
      <div class="field"><label for="dateEnregistrement">تاريخ التسجيل</label><input id="dateEnregistrement" type="date" name="date_enregistrement" value="<?= e((string) ($old['date_enregistrement'] ?? '')) ?>" /></div>
      <div class="field"><label for="numeroArchive">رقم الأرشيف</label><input id="numeroArchive" type="text" name="numero_archive" value="<?= e((string) ($old['numero_archive'] ?? '')) ?>" maxlength="100" /></div>
      <div class="field"><label for="reference">المرجع</label><input id="reference" type="text" name="reference" value="<?= e((string) ($old['reference'] ?? '')) ?>" maxlength="255" /></div>
      <div class="field full"><label for="notes">ملاحظات عامة (اختياري)</label><textarea id="notes" name="notes"><?= e((string) ($old['notes'] ?? '')) ?></textarea></div>
    </div>
  </section>

  <section class="form-section">
    <h3>الأطراف</h3>
    <p class="page-subtitle" data-party-help>يمكن ربط الشخص نفسه بعدة عقود باستخدام رقم تعريفه إن توفر.</p>
    <p class="page-subtitle" data-marriage-help hidden>أدخل الزوج والزوجة؛ ويمكن إضافة الشاهد اختيارياً.</p>
    <div id="personsContainer">
      <?php foreach (array_values($formPeople) as $index => $person): if (!is_array($person)) { continue; } ?>
        <div class="person-block">
          <div class="person-block-header"><h4>الشخص <?= $index + 1 ?></h4><button type="button" class="action-btn danger remove-person">حذف</button></div>
          <div class="person-grid">
            <div class="field"><label>الدور</label><input type="text" name="personnes[<?= $index ?>][role]" maxlength="100" value="<?= e((string) ($person['role'] ?? '')) ?>" placeholder="مثال: الطرف" /></div>
            <div class="field"><label>الاسم</label><input type="text" name="personnes[<?= $index ?>][nom]" maxlength="120" value="<?= e((string) ($person['nom'] ?? '')) ?>" /></div>
            <div class="field"><label>النسب</label><input type="text" name="personnes[<?= $index ?>][prenom]" maxlength="160" value="<?= e((string) ($person['prenom'] ?? '')) ?>" /></div>
            <div class="field"><label>رقم البطاقة (اختياري)</label><input type="text" name="personnes[<?= $index ?>][cin]" maxlength="80" value="<?= e((string) ($person['cin'] ?? '')) ?>" /></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="form-actions"><button type="button" class="btn btn-secondary" data-add-person>+ إضافة شخص</button></div>
  </section>

  <section class="form-section" data-properties-section hidden>
    <h3>معلومات الأملاك وخصائصها</h3>
    <div data-properties-fields>
      <p class="page-subtitle">اختر نوع كل ملك لإظهار خصائصه. يمكنك إضافة أكثر من ملك.</p>
      <div id="propertiesContainer" data-initial-properties="<?= e(json_encode($formProperties, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) ?>"></div>
      <div class="form-actions"><button type="button" class="btn btn-secondary" data-add-property>+ إضافة ملك</button></div>
    </div>
  </section>
  <section class="form-section">
    <div class="field full" style="margin-top:1rem">
      <label for="specificNotes">ملاحظات إضافية (اختياري)</label>
      <textarea id="specificNotes" name="specific_notes" placeholder="معلومات عامة إضافية، دون افتراض حقول قانونية غير معتمدة."><?= e((string) ($old['specific_notes'] ?? '')) ?></textarea>
    </div>
  </section>

  <section class="form-section">
    <h3>الوثائق الممسوحة ضوئياً</h3>
    <div class="file-upload">
      <label class="file-upload-label" for="documentUpload">اختر مستندات PDF أو JPG أو PNG (10 ميغابايت كحد أقصى لكل ملف)</label>
      <input id="documentUpload" type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" />
      <div id="uploadList" class="upload-list" aria-live="polite"></div>
    </div>
  </section>
  <div class="form-actions"><a class="btn btn-secondary" href="/pages/contracts.php">إلغاء</a><button type="submit" class="btn btn-success">حفظ العقد</button></div>
</form>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
