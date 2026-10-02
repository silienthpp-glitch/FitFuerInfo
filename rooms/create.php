<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Raum anlegen';
$currentNav = 'rooms';

$error = '';
$roomName = '';
$computerCount = '';
$description = '';
$selectedSoftware = array();
$selectedEditors = array();
$softwareList = fetchSoftwareList($pdo);
$employees = fetchAllEmployees($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $roomName = postValue('room_name', '');
        $computerCount = postInt('computer_count');
        $description = postValue('description', '');
        $selectedSoftware = postIntArray('software_ids');
        $selectedEditors = postIntArray('editor_ids');

        if ($roomName === '') {
            $error = 'Bitte einen Raumnamen angeben.';
        } elseif ($computerCount < 1) {
            $error = 'Die Anzahl der Rechnerarbeitsplätze muss mindestens 1 betragen.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO rooms (room_name, computer_count, description)
                     VALUES (?, ?, ?)'
                );
                $stmt->execute(array($roomName, $computerCount, $description));
                $roomId = (int) $pdo->lastInsertId();

                replaceRoomSoftware($pdo, $roomId, $selectedSoftware);
                replaceRoomEditors($pdo, $roomId, $selectedEditors);

                $pdo->commit();
                setFlash('success', 'Der Raum wurde angelegt.');
                redirect('/rooms/index.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Raum anlegen fehlgeschlagen: ' . $e->getMessage());
                $error = 'Der Raum konnte nicht angelegt werden. Möglicherweise existiert der Name bereits.';
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Raum anlegen</h1>
        <p class="muted">Neue Räume dürfen nur vom Administrator erstellt werden.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/rooms/create.php">
        <?php echo csrfField(); ?>

        <label for="room_name">Raumname</label>
        <input type="text" id="room_name" name="room_name" value="<?php echo e($roomName); ?>" required>

        <label for="computer_count">Anzahl Rechnerarbeitsplätze</label>
        <input type="number" id="computer_count" name="computer_count" min="1" value="<?php echo e($computerCount); ?>" required>

        <label for="description">Beschreibung</label>
        <textarea id="description" name="description"><?php echo e($description); ?></textarea>

        <label>Installierte Software</label>
        <?php if (count($softwareList) === 0): ?>
            <p class="hint">Es ist noch keine Software vorhanden.</p>
        <?php else: ?>
            <div class="checkbox-list">
                <?php foreach ($softwareList as $software): ?>
                    <label>
                        <input
                            type="checkbox"
                            name="software_ids[]"
                            value="<?php echo (int) $software['software_id']; ?>"
                            <?php echo in_array((int) $software['software_id'], $selectedSoftware, true) ? 'checked' : ''; ?>
                        >
                        <?php echo e($software['software_name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label>Raum-Bearbeiter</label>
        <p class="hint">Diese Mitarbeiter dürfen den Raum später bearbeiten. Der Administrator darf das immer.</p>
        <div class="checkbox-list">
            <?php foreach ($employees as $employee): ?>
                <?php if ($employee['role'] === 'admin') { continue; } ?>
                <label>
                    <input
                        type="checkbox"
                        name="editor_ids[]"
                        value="<?php echo (int) $employee['user_id']; ?>"
                        <?php echo in_array((int) $employee['user_id'], $selectedEditors, true) ? 'checked' : ''; ?>
                    >
                    <?php echo e($employee['last_name'] . ', ' . $employee['first_name']); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/rooms/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
