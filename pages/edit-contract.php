<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_judge();
$pdo = database();
$contractId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$contractId || $contractId < 1) {
    http_response_code(400);
    exit('رقم العقد غير صالح.');
}
$load = $pdo->prepare('SELECT * FROM contracts WHERE id = :id');
$load->execute(['id' => $contractId]);
$contract = $load->fetch();
if ($contract === false) {
    http_response_code(404);
    exit('العقد غير موجود.');
}
$specificData = $contract['specific_data'] ? json_decode($contract['specific_data'], true) : [];
$specificDataFr = $contract['specific_data_fr'] ? json_decode($contract['specific_data_fr'], true) : [];
if (!is_array($specificData)) {
    $specificData = [];
}
if (!is_array($specificDataFr)) {
    $specificDataFr = [];
}
$formProperties = contract_properties_for_form($specificData['الأملاك'] ?? [], $specificDataFr['الأملاك'] ?? []);
$categoryTypes = contract_types();
$partyLoad = $pdo->prepare(
    'SELECT p.first_name AS nom, p.first_name_fr AS nom_fr, p.last_name AS prenom, p.last_name_fr AS prenom_fr,
            p.identity_number AS cin, cp.party_role AS role, cp.party_role_fr AS role_fr
     FROM contract_parties cp JOIN persons p ON p.id = cp.person_id WHERE cp.contract_id = :id
     ORDER BY CASE cp.party_role WHEN \'الزوج\' THEN 0 WHEN \'الزوجة\' THEN 1 ELSE 2 END, p.id'
);
$partyLoad->execute(['id' => $contractId]);
$storedPeople = $partyLoad->fetchAll();
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
    $formProperties = $_POST['properties'] ?? [];
    if (!is_array($formProperties)) {
        $formProperties = [];
    }
    [$properties, $propertiesFr, $propertyErrors] = normalize_contract_properties($formProperties);
    if ($category !== 'الأملاك') {
        $properties = [];
        $propertiesFr = [];
        $propertyErrors = [];
    }
    $errors = array_merge($errors, $propertyErrors);
    $peopleInput = $_POST['personnes'] ?? [];
    $people = [];

    if ($number === '' || $numberFr === '' || utf8_length($number) > 80 || utf8_length($numberFr) > 80) {
        $errors[] = 'رقم العقد مطلوب بالعربية والفرنسية ولا يتجاوز 80 حرفاً.';
    }
    if (!isset($categoryTypes[$category]) || !in_array($actType, $categoryTypes[$category] ?? [], true)) {
        $errors[] = 'التصنيف أو نوع العقد غير صالح.';
    }
    if (!is_valid_date($contractDate)) {
        $errors[] = 'تاريخ العقد غير صالح.';
    }
    if ($registeredDate !== '' && !is_valid_date($registeredDate)) {
        $errors[] = 'تاريخ التسجيل غير صالح.';
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
        $errors[] = 'الملاحظات طويلة جداً.';
    }
    if (!is_array($peopleInput)) {
        $errors[] = 'قائمة الأطراف غير صالحة.';
    } else {
        foreach ($peopleInput as $personInput) {
            if (!is_array($personInput)) {
                continue;
            }
            $person = [
                'nom' => request_text($personInput['nom'] ?? null),
                'nom_fr' => request_text($personInput['nom_fr'] ?? null),
                'prenom' => request_text($personInput['prenom'] ?? null),
                'prenom_fr' => request_text($personInput['prenom_fr'] ?? null),
                'cin' => request_text($personInput['cin'] ?? null),
                'role' => request_text($personInput['role'] ?? null),
                'role_fr' => request_text($personInput['role_fr'] ?? null),
            ];
            if (implode('', $person) === '') {
                continue;
            }
            if ($person['nom'] === '' || $person['nom_fr'] === '' || $person['prenom'] === ''
                || $person['prenom_fr'] === '' || $person['role'] === '' || $person['role_fr'] === '') {
                $errors[] = 'أدخل الاسم والنسب والدور لكل طرف بالعربية والفرنسية.';
                continue;
            }
            if (utf8_length($person['nom']) > 120 || utf8_length($person['nom_fr']) > 120
                || utf8_length($person['prenom']) > 160 || utf8_length($person['prenom_fr']) > 160
                || utf8_length($person['cin']) > 80 || utf8_length($person['role']) > 100 || utf8_length($person['role_fr']) > 100) {
                $errors[] = 'بيانات أحد الأطراف تتجاوز الحد المسموح.';
                continue;
            }
            if ($category === 'الزواج' && $actType === 'عقد الزواج'
                && !in_array($person['role'], ['الزوج', 'الزوجة', 'شاهد'], true)) {
                $errors[] = 'الأطراف المسموحة في عقد الزواج هي الزوج والزوجة والشاهد فقط.';
                continue;
            }
            $people[] = $person;
        }
    }
    if ($category === 'الزواج' && $actType === 'عقد الزواج') {
        $roleCounts = array_count_values(array_column($people, 'role'));
        if (($roleCounts['الزوج'] ?? 0) !== 1 || ($roleCounts['الزوجة'] ?? 0) !== 1) {
            $errors[] = 'يجب إدخال بيانات الزوج والزوجة مرة واحدة لكل عقد زواج.';
        }
    } elseif ($people === []) {
        $errors[] = 'أضف طرفاً واحداً على الأقل.';
    }

    if ($errors === []) {
        try {
            $specificPayload = $specificData;
            unset($specificPayload['الأملاك'], $specificPayload['ملاحظات إضافية']);
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
            $pdo->beginTransaction();
            $update = $pdo->prepare(
                'UPDATE contracts SET contract_number = :number, contract_number_fr = :number_fr,
                    contract_date = :contract_date, registered_date = :registered_date,
                    category = :category, act_type = :act_type, archive_number = :archive_number,
                    archive_number_fr = :archive_number_fr, reference_text = :reference_text,
                    reference_text_fr = :reference_text_fr, notes = :notes, notes_fr = :notes_fr,
                    specific_data = :specific_data, specific_data_fr = :specific_data_fr WHERE id = :id'
            );
            $update->execute([
                'number' => $number,
                'number_fr' => $numberFr,
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
                'id' => $contractId,
            ]);
            $oldPersonQuery = $pdo->prepare('SELECT DISTINCT person_id FROM contract_parties WHERE contract_id = :id');
            $oldPersonQuery->execute(['id' => $contractId]);
            $oldPersonIds = $oldPersonQuery->fetchAll(PDO::FETCH_COLUMN);
            $pdo->prepare('DELETE FROM contract_parties WHERE contract_id = :id')->execute(['id' => $contractId]);
            $findPerson = $pdo->prepare('SELECT id FROM persons WHERE identity_number = :identity_number LIMIT 1');
            $insertPerson = $pdo->prepare('INSERT INTO persons (first_name, first_name_fr, last_name, last_name_fr, identity_number) VALUES (:first_name, :first_name_fr, :last_name, :last_name_fr, :identity_number)');
            $updatePerson = $pdo->prepare('UPDATE persons SET first_name = :first_name, first_name_fr = :first_name_fr, last_name = :last_name, last_name_fr = :last_name_fr WHERE id = :id');
            $insertParty = $pdo->prepare('INSERT INTO contract_parties (contract_id, person_id, party_role, party_role_fr) VALUES (:contract_id, :person_id, :party_role, :party_role_fr)');
            $seen = [];
            foreach ($people as $person) {
                $personId = null;
                if ($person['cin'] !== '') {
                    $findPerson->execute(['identity_number' => $person['cin']]);
                    $existing = $findPerson->fetchColumn();
                    if ($existing !== false) {
                        $personId = (int) $existing;
                    }
                }
                if ($personId === null) {
                    $insertPerson->execute([
                        'first_name' => $person['nom'],
                        'first_name_fr' => $person['nom_fr'],
                        'last_name' => $person['prenom'],
                        'last_name_fr' => $person['prenom_fr'],
                        'identity_number' => $person['cin'] === '' ? null : $person['cin'],
                    ]);
                    $personId = (int) $pdo->lastInsertId();
                } else {
                    $updatePerson->execute([
                        'first_name' => $person['nom'],
                        'first_name_fr' => $person['nom_fr'],
                        'last_name' => $person['prenom'],
                        'last_name_fr' => $person['prenom_fr'],
                        'id' => $personId,
                    ]);
                }
                $key = $personId . ':' . $person['role'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $insertParty->execute([
                    'contract_id' => $contractId,
                    'person_id' => $personId,
                    'party_role' => $person['role'],
                    'party_role_fr' => $person['role_fr'],
                ]);
            }
            // Remove people who were removed from this contract and have no other contract.
            $removeOrphan = $pdo->prepare(
                'DELETE FROM persons WHERE id = :id
                 AND NOT EXISTS (SELECT 1 FROM contract_parties WHERE person_id = :person_id)'
            );
            foreach ($oldPersonIds as $oldPersonId) {
                $removeOrphan->execute(['id' => $oldPersonId, 'person_id' => $oldPersonId]);
            }
            $pdo->commit();
            set_flash('success', 'تم تحديث العقد والأطراف.');
            redirect('/pages/contract-details.php?id=' . $contractId);
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                $errors[] = 'رقم العقد أو رقم الأرشيف مستخدم لعقد آخر.';
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
    $contract = array_merge($contract, [
        'contract_number' => $number,
        'contract_number_fr' => $numberFr,
        'contract_date' => $contractDate,
        'registered_date' => $registeredDate,
        'category' => $category,
        'act_type' => $actType,
        'archive_number' => $archiveNumber,
        'archive_number_fr' => $archiveNumberFr,
        'reference_text' => $reference,
        'reference_text_fr' => $referenceFr,
        'notes' => $notes,
        'notes_fr' => $notesFr,
        'specific_data' => ['ملاحظات إضافية' => $specificNotes],
        'specific_data_fr' => ['ملاحظات إضافية' => $specificNotesFr],
    ]);
    $storedPeople = [];
    if (is_array($peopleInput)) {
        foreach ($peopleInput as $personInput) {
            if (!is_array($personInput)) {
                continue;
            }
            $storedPeople[] = [
                'nom' => request_text($personInput['nom'] ?? null),
                'nom_fr' => request_text($personInput['nom_fr'] ?? null),
                'prenom' => request_text($personInput['prenom'] ?? null),
                'prenom_fr' => request_text($personInput['prenom_fr'] ?? null),
                'cin' => request_text($personInput['cin'] ?? null),
                'role' => request_text($personInput['role'] ?? null),
                'role_fr' => request_text($personInput['role_fr'] ?? null),
            ];
        }
    }
}

$pageTitle = 'تعديل العقد';
$activePage = 'contracts';
require __DIR__ . '/../includes/layout-start.php';
?>
<div class="page-header"><div><h2>تعديل العقد <?= e($contract['contract_number']) ?></h2><div class="page-subtitle">تحديث المعلومات العامة والأطراف المرتبطة</div></div><a class="btn btn-secondary" href="/pages/contract-details.php?id=<?= $contractId ?>">إلغاء</a></div>
<?php if ($errors !== []): ?><div class="notice error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="form-card contract-form" method="post" action="/pages/edit-contract.php?id=<?= $contractId ?>">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
  <section class="form-section"><h3>معلومات العقد</h3><div class="form-grid">
    <div class="field"><label for="category">التصنيف</label><select id="category" name="categorie" required><?php foreach (array_keys($categoryTypes) as $item): ?><option value="<?= e($item) ?>" <?= $contract['category'] === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="type">نوع العقد</label><select id="type" name="type_acte" data-selected-type="<?= e($contract['act_type']) ?>"></select></div>
    <div class="field bilingual-field"><label for="number">رقم العقد — العربية</label><input id="number" name="numero" dir="auto" maxlength="80" value="<?= e($contract['contract_number']) ?>" required /><label for="numberFr">Numéro du contrat — Français</label><input id="numberFr" name="numero_fr" dir="auto" maxlength="80" value="<?= e((string) ($contract['contract_number_fr'] ?? '')) ?>" required /></div>
    <div class="field"><label for="date">تاريخ العقد</label><input id="date" name="date" type="date" value="<?= e($contract['contract_date']) ?>" required /></div>
    <div class="field"><label for="registered">تاريخ التسجيل</label><input id="registered" name="date_enregistrement" type="date" value="<?= e($contract['registered_date']) ?>" /></div>
    <div class="field bilingual-field"><label for="archive">رقم الأرشيف — العربية</label><input id="archive" name="numero_archive" dir="auto" maxlength="100" value="<?= e($contract['archive_number']) ?>" required /><label for="archiveFr">Numéro d’archive — Français</label><input id="archiveFr" name="numero_archive_fr" dir="auto" maxlength="100" value="<?= e((string) ($contract['archive_number_fr'] ?? '')) ?>" required /></div>
    <div class="field bilingual-field"><label for="reference">المرجع — العربية</label><input id="reference" name="reference" dir="auto" maxlength="255" value="<?= e($contract['reference_text']) ?>" required /><label for="referenceFr">Référence — Français</label><input id="referenceFr" name="reference_fr" dir="auto" maxlength="255" value="<?= e((string) ($contract['reference_text_fr'] ?? '')) ?>" required /></div>
    <div class="field full bilingual-field"><label for="notes">ملاحظات — العربية</label><textarea id="notes" name="notes" dir="auto" required><?= e($contract['notes']) ?></textarea><label for="notesFr">Notes — Français</label><textarea id="notesFr" name="notes_fr" dir="auto" required><?= e((string) ($contract['notes_fr'] ?? '')) ?></textarea></div>
  </div></section>
  <section class="form-section" data-properties-section hidden><h3>معلومات الأملاك وخصائصها</h3>
    <div data-properties-fields>
      <p class="page-subtitle">اختر نوع كل ملك لإظهار خصائصه. يمكنك إضافة أكثر من ملك.</p>
      <div id="propertiesContainer" data-initial-properties="<?= e(json_encode($formProperties, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) ?>"></div>
      <div class="form-actions"><button type="button" class="btn btn-secondary" data-add-property>+ إضافة ملك</button></div>
    </div>
  </section>
  <section class="form-section">
    <div class="field full bilingual-field" style="margin-top:1rem"><label for="specificNotes">ملاحظات إضافية — العربية</label><textarea id="specificNotes" name="specific_notes" required><?= e((string) ($specificData['ملاحظات إضافية'] ?? '')) ?></textarea><label for="specificNotesFr">Notes supplémentaires — Français</label><textarea id="specificNotesFr" name="specific_notes_fr" required><?= e((string) ($specificDataFr['ملاحظات إضافية'] ?? '')) ?></textarea></div>
  </section>
  <section class="form-section"><h3>الأطراف</h3><p class="page-subtitle" data-party-help>يمكن ربط الشخص نفسه بعدة عقود باستخدام رقم تعريفه إن توفر.</p><p class="page-subtitle" data-marriage-help hidden>أدخل الزوج والزوجة؛ ويمكن إضافة الشاهد اختيارياً.</p><div id="personsContainer">
    <?php foreach ($storedPeople as $index => $person): ?><div class="person-block"><div class="person-block-header"><h4>الشخص <?= $index + 1 ?></h4><button class="action-btn danger remove-person" type="button">حذف</button></div><div class="person-grid">
      <div class="field bilingual-field"><label>الدور — العربية</label><input name="personnes[<?= $index ?>][role]" dir="auto" value="<?= e($person['role']) ?>" required /><label>Rôle — Français</label><input name="personnes[<?= $index ?>][role_fr]" dir="auto" value="<?= e((string) ($person['role_fr'] ?? '')) ?>" required /></div>
      <div class="field bilingual-field"><label>الاسم — العربية</label><input name="personnes[<?= $index ?>][nom]" dir="auto" value="<?= e($person['nom']) ?>" required /><label>Prénom — Français</label><input name="personnes[<?= $index ?>][nom_fr]" dir="auto" value="<?= e((string) ($person['nom_fr'] ?? '')) ?>" required /></div>
      <div class="field bilingual-field"><label>النسب — العربية</label><input name="personnes[<?= $index ?>][prenom]" dir="auto" value="<?= e($person['prenom']) ?>" required /><label>Nom de famille — Français</label><input name="personnes[<?= $index ?>][prenom_fr]" dir="auto" value="<?= e((string) ($person['prenom_fr'] ?? '')) ?>" required /></div>
      <div class="field"><label>رقم البطاقة / Numéro de pièce d’identité</label><input name="personnes[<?= $index ?>][cin]" dir="auto" value="<?= e($person['cin']) ?>" /></div>
    </div></div><?php endforeach; ?>
  </div><div class="form-actions"><button class="btn btn-secondary" type="button" data-add-person>+ إضافة شخص</button></div></section>
  <div class="form-actions"><button class="btn btn-primary" type="submit">حفظ التعديلات</button></div>
</form>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
