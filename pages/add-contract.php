<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$pdo = database();
$categories = contract_types();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $number = request_text($_POST['numero'] ?? null);
    $numberFr = request_text($_POST['numero_fr'] ?? null);
    $contractDate = request_text($_POST['date'] ?? null);
    $registeredDate = request_text($_POST['date_enregistrement'] ?? null);
    $category = request_text($_POST['categorie'] ?? null);
    $actType = request_text($_POST['type_acte'] ?? null);
    $archiveNumber = request_text($_POST['numero_archive'] ?? null);
    $archiveNumberFr = request_text($_POST['numero_archive_fr'] ?? null);
    $reference = request_text($_POST['reference'] ?? null);
    $referenceFr = request_text($_POST['reference_fr'] ?? null);
    $notes = request_text($_POST['notes'] ?? null);
    $notesFr = request_text($_POST['notes_fr'] ?? null);
    $specificNotes = request_text($_POST['specific_notes'] ?? null);
    $specificNotesFr = request_text($_POST['specific_notes_fr'] ?? null);
    [$properties, $propertiesFr, $propertyErrors] = normalize_contract_properties($_POST['properties'] ?? []);
    if ($category !== 'الأملاك') {
        $properties = [];
        $propertiesFr = [];
        $propertyErrors = [];
    }
    $errors = array_merge($errors, $propertyErrors);
    $submittedPeople = $_POST['personnes'] ?? [];
    $people = [];

    if ($number === '' || $numberFr === '' || utf8_length($number) > 80 || utf8_length($numberFr) > 80) {
        $errors[] = 'رقم العقد مطلوب بالعربية والفرنسية ولا يتجاوز 80 حرفاً.';
    }
    if ($archiveNumber === '' || $archiveNumberFr === '' || $reference === '' || $referenceFr === '') {
        $errors[] = 'أدخل رقم الأرشيف والمرجع بالعربية والفرنسية.';
    } elseif (utf8_length($archiveNumber) > 100 || utf8_length($archiveNumberFr) > 100
        || utf8_length($reference) > 255 || utf8_length($referenceFr) > 255) {
        $errors[] = 'رقم الأرشيف أو المرجع يتجاوز الحد المسموح.';
    }
    if ($notes === '' || $notesFr === '' || $specificNotes === '' || $specificNotesFr === '') {
        $errors[] = 'أدخل الملاحظات بالعربية والفرنسية.';
    }
    if (strlen($notes) > 60000 || strlen($notesFr) > 60000
        || strlen($specificNotes) > 60000 || strlen($specificNotesFr) > 60000) {
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

            $firstName = request_text($person['nom'] ?? null);
            $firstNameFr = request_text($person['nom_fr'] ?? null);
            $lastName = request_text($person['prenom'] ?? null);
            $lastNameFr = request_text($person['prenom_fr'] ?? null);
            $identityNumber = request_text($person['cin'] ?? null);
            $partyRole = request_text($person['role'] ?? null);
            $partyRoleFr = request_text($person['role_fr'] ?? null);

            if ($firstName === '' && $firstNameFr === '' && $lastName === '' && $lastNameFr === '' && $identityNumber === '' && $partyRole === '' && $partyRoleFr === '') {
                continue;
            }
            if ($firstName === '' || $firstNameFr === '' || $lastName === '' || $lastNameFr === '' || $partyRole === '' || $partyRoleFr === '') {
                $errors[] = 'أدخل الاسم والنسب والدور لكل طرف بالعربية والفرنسية.';
                continue;
            }
            if (utf8_length($firstName) > 120 || utf8_length($firstNameFr) > 120
                || utf8_length($lastName) > 160 || utf8_length($lastNameFr) > 160
                || utf8_length($identityNumber) > 80 || utf8_length($partyRole) > 100 || utf8_length($partyRoleFr) > 100) {
                $errors[] = 'بيانات أحد الأطراف تتجاوز الحد المسموح.';
                continue;
            }

            $people[] = [
                'first_name' => $firstName,
                'first_name_fr' => $firstNameFr,
                'last_name' => $lastName,
                'last_name_fr' => $lastNameFr,
                'identity_number' => $identityNumber,
                'party_role' => $partyRole,
                'party_role_fr' => $partyRoleFr,
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
        $finfo = null;
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
            if (!class_exists(\finfo::class)) {
                $errors[] = 'تعذر التحقق من نوع الملف. يجب تفعيل امتداد PHP fileinfo لرفع الوثائق.';
                break;
            }
            if ($finfo === null) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
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
        $specificPayloadFr = [];
        if ($propertiesFr !== []) {
            $specificPayloadFr['الأملاك'] = $propertiesFr;
        }
        $specificPayload['ملاحظات إضافية'] = $specificNotes;
        $specificPayloadFr['ملاحظات إضافية'] = $specificNotesFr;
        $specificData = $specificPayload === [] ? null : json_encode(
            $specificPayload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $specificDataFr = json_encode($specificPayloadFr, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

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
                  (contract_number, contract_number_fr, contract_date, registered_date, category, act_type, archive_number, archive_number_fr, reference_text, reference_text_fr, notes, notes_fr, specific_data, specific_data_fr, created_by)
                 VALUES
                  (:contract_number, :contract_number_fr, :contract_date, :registered_date, :category, :act_type, :archive_number, :archive_number_fr, :reference_text, :reference_text_fr, :notes, :notes_fr, :specific_data, :specific_data_fr, :created_by)'
            );
            $insertContract->execute([
                'contract_number' => $number,
                'contract_number_fr' => $numberFr,
                'contract_date' => $contractDate,
                'registered_date' => $registeredDate === '' ? null : $registeredDate,
                'category' => $category,
                'act_type' => $actType,
                'archive_number' => $archiveNumber,
                'archive_number_fr' => $archiveNumberFr,
                'reference_text' => $reference,
                'reference_text_fr' => $referenceFr,
                'notes' => $notes,
                'notes_fr' => $notesFr,
                'specific_data' => $specificData,
                'specific_data_fr' => $specificDataFr,
                'created_by' => $user['id'],
            ]);
            $contractId = (int) $pdo->lastInsertId();

            $findPerson = $pdo->prepare('SELECT id FROM persons WHERE identity_number = :identity_number LIMIT 1');
            $insertPerson = $pdo->prepare(
                'INSERT INTO persons (first_name, first_name_fr, last_name, last_name_fr, identity_number) VALUES (:first_name, :first_name_fr, :last_name, :last_name_fr, :identity_number)'
            );
            $insertParty = $pdo->prepare(
                'INSERT INTO contract_parties (contract_id, person_id, party_role, party_role_fr) VALUES (:contract_id, :person_id, :party_role, :party_role_fr)'
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
                        // Reuse the existing record as-is. Overwriting its names here would silently
                        // rewrite that person in every other contract (a typo in the ID number would
                        // corrupt someone else's record); corrections go through edit-contract (judge only).
                        $personId = (int) $existingPersonId;
                    }
                }

                if ($personId === null) {
                    $insertPerson->execute([
                        'first_name' => $person['first_name'],
                        'first_name_fr' => $person['first_name_fr'],
                        'last_name' => $person['last_name'],
                        'last_name_fr' => $person['last_name_fr'],
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
                    'party_role_fr' => $person['party_role_fr'],
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
$selectedCategory = request_text($old['categorie'] ?? null) ?: 'الأملاك';
$selectedType = request_text($old['type_acte'] ?? null);
$formPeople = $old['personnes'] ?? [['role' => '', 'role_fr' => '', 'nom' => '', 'nom_fr' => '', 'prenom' => '', 'prenom_fr' => '', 'cin' => '']];
if (!is_array($formPeople) || $formPeople === []) {
    $formPeople = [['role' => '', 'role_fr' => '', 'nom' => '', 'nom_fr' => '', 'prenom' => '', 'prenom_fr' => '', 'cin' => '']];
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
      <div class="field bilingual-field"><label for="numero">رقم العقد — العربية</label><input id="numero" type="text" name="numero" dir="auto" value="<?= e(request_text($old['numero'] ?? null)) ?>" maxlength="80" required /><label for="numeroFr">Numéro du contrat — Français</label><input id="numeroFr" type="text" name="numero_fr" dir="auto" value="<?= e(request_text($old['numero_fr'] ?? null)) ?>" maxlength="80" required /></div>
      <div class="field"><label for="date">تاريخ العقد</label><input id="date" type="date" name="date" value="<?= e(request_text($old['date'] ?? null)) ?>" required /></div>
      <div class="field"><label for="dateEnregistrement">تاريخ التسجيل</label><input id="dateEnregistrement" type="date" name="date_enregistrement" value="<?= e(request_text($old['date_enregistrement'] ?? null)) ?>" /></div>
      <div class="field bilingual-field"><label for="numeroArchive">رقم الأرشيف — العربية</label><input id="numeroArchive" type="text" name="numero_archive" dir="auto" value="<?= e(request_text($old['numero_archive'] ?? null)) ?>" maxlength="100" required /><label for="numeroArchiveFr">Numéro d’archive — Français</label><input id="numeroArchiveFr" type="text" name="numero_archive_fr" dir="auto" value="<?= e(request_text($old['numero_archive_fr'] ?? null)) ?>" maxlength="100" required /></div>
      <div class="field bilingual-field"><label for="reference">المرجع — العربية</label><input id="reference" type="text" name="reference" dir="auto" value="<?= e(request_text($old['reference'] ?? null)) ?>" maxlength="255" required /><label for="referenceFr">Référence — Français</label><input id="referenceFr" type="text" name="reference_fr" dir="auto" value="<?= e(request_text($old['reference_fr'] ?? null)) ?>" maxlength="255" required /></div>
      <div class="field full bilingual-field"><label for="notes">ملاحظات عامة — العربية</label><textarea id="notes" name="notes" dir="auto" required><?= e(request_text($old['notes'] ?? null)) ?></textarea><label for="notesFr">Notes générales — Français</label><textarea id="notesFr" name="notes_fr" dir="auto" required><?= e(request_text($old['notes_fr'] ?? null)) ?></textarea></div>
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
            <div class="field bilingual-field"><label>الدور — العربية</label><input type="text" name="personnes[<?= $index ?>][role]" dir="auto" maxlength="100" value="<?= e(request_text($person['role'] ?? null)) ?>" required /><label>Rôle — Français</label><input type="text" name="personnes[<?= $index ?>][role_fr]" dir="auto" maxlength="100" value="<?= e(request_text($person['role_fr'] ?? null)) ?>" required /></div>
            <div class="field bilingual-field"><label>الاسم — العربية</label><input type="text" name="personnes[<?= $index ?>][nom]" dir="auto" maxlength="120" value="<?= e(request_text($person['nom'] ?? null)) ?>" required /><label>Prénom — Français</label><input type="text" name="personnes[<?= $index ?>][nom_fr]" dir="auto" maxlength="120" value="<?= e(request_text($person['nom_fr'] ?? null)) ?>" required /></div>
            <div class="field bilingual-field"><label>النسب — العربية</label><input type="text" name="personnes[<?= $index ?>][prenom]" dir="auto" maxlength="160" value="<?= e(request_text($person['prenom'] ?? null)) ?>" required /><label>Nom de famille — Français</label><input type="text" name="personnes[<?= $index ?>][prenom_fr]" dir="auto" maxlength="160" value="<?= e(request_text($person['prenom_fr'] ?? null)) ?>" required /></div>
            <div class="field"><label>رقم البطاقة (اختياري) — Numéro de pièce d’identité (facultatif)</label><input type="text" name="personnes[<?= $index ?>][cin]" dir="auto" maxlength="80" value="<?= e(request_text($person['cin'] ?? null)) ?>" /></div>
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
      <label for="specificNotes">ملاحظات إضافية — العربية</label>
      <textarea id="specificNotes" name="specific_notes" dir="auto" required><?= e(request_text($old['specific_notes'] ?? null)) ?></textarea>
      <label for="specificNotesFr">Notes supplémentaires — Français</label>
      <textarea id="specificNotesFr" name="specific_notes_fr" dir="auto" required><?= e(request_text($old['specific_notes_fr'] ?? null)) ?></textarea>
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
