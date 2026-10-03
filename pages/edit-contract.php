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
if (!is_array($specificData)) {
    $specificData = [];
}
$formProperties = contract_properties_for_form($specificData['الأملاك'] ?? []);
$categoryTypes = contract_types();
$partyLoad = $pdo->prepare(
    'SELECT p.first_name AS nom, p.last_name AS prenom, p.identity_number AS cin, cp.party_role AS role
     FROM contract_parties cp JOIN persons p ON p.id = cp.person_id WHERE cp.contract_id = :id
     ORDER BY CASE cp.party_role WHEN \'الزوج\' THEN 0 WHEN \'الزوجة\' THEN 1 ELSE 2 END, p.id'
);
$partyLoad->execute(['id' => $contractId]);
$storedPeople = $partyLoad->fetchAll();
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
    $formProperties = $_POST['properties'] ?? [];
    if (!is_array($formProperties)) {
        $formProperties = [];
    }
    [$properties, $propertyErrors] = normalize_contract_properties($formProperties);
    if ($category !== 'الأملاك') {
        $properties = [];
        $propertyErrors = [];
    }
    $errors = array_merge($errors, $propertyErrors);
    $peopleInput = $_POST['personnes'] ?? [];
    $people = [];

    if ($number === '' || utf8_length($number) > 80) {
        $errors[] = 'رقم العقد مطلوب ويجب ألا يتجاوز 80 حرفاً.';
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
    if (utf8_length($archiveNumber) > 100 || utf8_length($reference) > 255) {
        $errors[] = 'رقم الأرشيف أو المرجع يتجاوز الحد المسموح.';
    }
    if (strlen($notes) > 60000 || strlen($specificNotes) > 60000) {
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
                'nom' => trim((string) ($personInput['nom'] ?? '')),
                'prenom' => trim((string) ($personInput['prenom'] ?? '')),
                'cin' => trim((string) ($personInput['cin'] ?? '')),
                'role' => trim((string) ($personInput['role'] ?? '')),
            ];
            if (implode('', $person) === '') {
                continue;
            }
            if ($person['nom'] === '' || $person['prenom'] === '' || $person['role'] === '') {
                $errors[] = 'أدخل الاسم والنسب والدور لكل طرف.';
                continue;
            }
            if (utf8_length($person['nom']) > 120 || utf8_length($person['prenom']) > 160 || utf8_length($person['cin']) > 80 || utf8_length($person['role']) > 100) {
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
            if ($specificNotes !== '') {
                $specificPayload['ملاحظات إضافية'] = $specificNotes;
            }
            $specificData = $specificPayload === [] ? null : json_encode(
                $specificPayload,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            $pdo->beginTransaction();
            $update = $pdo->prepare(
                'UPDATE contracts SET contract_number = :number, contract_date = :contract_date, registered_date = :registered_date,
                    category = :category, act_type = :act_type, archive_number = :archive_number,
                    reference_text = :reference_text, notes = :notes, specific_data = :specific_data WHERE id = :id'
            );
            $update->execute([
                'number' => $number,
                'contract_date' => $contractDate,
                'registered_date' => $registeredDate === '' ? null : $registeredDate,
                'category' => $category,
                'act_type' => $actType,
                'archive_number' => $archiveNumber === '' ? null : $archiveNumber,
                'reference_text' => $reference === '' ? null : $reference,
                'notes' => $notes === '' ? null : $notes,
                'specific_data' => $specificData,
                'id' => $contractId,
            ]);
            $pdo->prepare('DELETE FROM contract_parties WHERE contract_id = :id')->execute(['id' => $contractId]);
            $findPerson = $pdo->prepare('SELECT id FROM persons WHERE identity_number = :identity_number LIMIT 1');
            $insertPerson = $pdo->prepare('INSERT INTO persons (first_name, last_name, identity_number) VALUES (:first_name, :last_name, :identity_number)');
            $insertParty = $pdo->prepare('INSERT INTO contract_parties (contract_id, person_id, party_role) VALUES (:contract_id, :person_id, :party_role)');
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
                        'last_name' => $person['prenom'],
                        'identity_number' => $person['cin'] === '' ? null : $person['cin'],
                    ]);
                    $personId = (int) $pdo->lastInsertId();
                }
                $key = $personId . ':' . $person['role'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $insertParty->execute(['contract_id' => $contractId, 'person_id' => $personId, 'party_role' => $person['role']]);
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
        'contract_date' => $contractDate,
        'registered_date' => $registeredDate,
        'category' => $category,
        'act_type' => $actType,
        'archive_number' => $archiveNumber,
        'reference_text' => $reference,
        'notes' => $notes,
    ]);
    $storedPeople = $people;
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
    <div class="field"><label for="number">رقم العقد</label><input id="number" name="numero" maxlength="80" value="<?= e($contract['contract_number']) ?>" required /></div>
    <div class="field"><label for="date">تاريخ العقد</label><input id="date" name="date" type="date" value="<?= e($contract['contract_date']) ?>" required /></div>
    <div class="field"><label for="registered">تاريخ التسجيل</label><input id="registered" name="date_enregistrement" type="date" value="<?= e($contract['registered_date']) ?>" /></div>
    <div class="field"><label for="archive">رقم الأرشيف</label><input id="archive" name="numero_archive" maxlength="100" value="<?= e($contract['archive_number']) ?>" /></div>
    <div class="field"><label for="reference">المرجع</label><input id="reference" name="reference" maxlength="255" value="<?= e($contract['reference_text']) ?>" /></div>
    <div class="field full"><label for="notes">ملاحظات</label><textarea id="notes" name="notes"><?= e($contract['notes']) ?></textarea></div>
  </div></section>
  <section class="form-section" data-properties-section hidden><h3>معلومات الأملاك وخصائصها</h3>
    <div data-properties-fields>
      <p class="page-subtitle">اختر نوع كل ملك لإظهار خصائصه. يمكنك إضافة أكثر من ملك.</p>
      <div id="propertiesContainer" data-initial-properties="<?= e(json_encode($formProperties, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) ?>"></div>
      <div class="form-actions"><button type="button" class="btn btn-secondary" data-add-property>+ إضافة ملك</button></div>
    </div>
  </section>
  <section class="form-section">
    <div class="field full" style="margin-top:1rem"><label for="specificNotes">ملاحظات إضافية (اختياري)</label><textarea id="specificNotes" name="specific_notes"><?= e((string) ($specificData['ملاحظات إضافية'] ?? '')) ?></textarea></div>
  </section>
  <section class="form-section"><h3>الأطراف</h3><p class="page-subtitle" data-party-help>يمكن ربط الشخص نفسه بعدة عقود باستخدام رقم تعريفه إن توفر.</p><p class="page-subtitle" data-marriage-help hidden>أدخل الزوج والزوجة؛ ويمكن إضافة الشاهد اختيارياً.</p><div id="personsContainer">
    <?php foreach ($storedPeople as $index => $person): ?><div class="person-block"><div class="person-block-header"><h4>الشخص <?= $index + 1 ?></h4><button class="action-btn danger remove-person" type="button">حذف</button></div><div class="person-grid">
      <div class="field"><label>الدور</label><input name="personnes[<?= $index ?>][role]" value="<?= e($person['role']) ?>" required /></div>
      <div class="field"><label>الاسم</label><input name="personnes[<?= $index ?>][nom]" value="<?= e($person['nom']) ?>" required /></div>
      <div class="field"><label>النسب</label><input name="personnes[<?= $index ?>][prenom]" value="<?= e($person['prenom']) ?>" required /></div>
      <div class="field"><label>رقم البطاقة</label><input name="personnes[<?= $index ?>][cin]" value="<?= e($person['cin']) ?>" /></div>
    </div></div><?php endforeach; ?>
  </div><div class="form-actions"><button class="btn btn-secondary" type="button" data-add-person>+ إضافة شخص</button></div></section>
  <div class="form-actions"><button class="btn btn-primary" type="submit">حفظ التعديلات</button></div>
</form>
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
