<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Raum bearbeiten';
$currentNav = 'rooms';

$roomId = getInt('id');
$error = '';

$stmt = $pdo->prepare(
    'SELECT room_id, room_name, computer_count, description
     FROM rooms
     WHERE room_id = ?
     LIMIT 1'
);
$stmt->execute(array($roomId));
$room = $stmt->fetch();

if (!$room) {
    setFlash('error', 'Der Raum wurde nicht gefunden.');
    redirect('/rooms/index.php');
}

if (!canEditRoom($pdo, $roomId)) {
    setFlash('error', 'Sie dürfen diesen Raum nicht bearbeiten.');
    redirect('/rooms/index.php');
}

$roomName = $room['room_name'];
$computerCount = (int) $room['computer_count'];
$description = $room['description'];
$selectedSoftware = fetchRoomSoftwareIds($pdo, $roomId);
$selectedEditors = fetchRoomEditorIds($pdo, $roomId);
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

        if (isAdmin()) {
            $selectedEditors = postIntArray('editor_ids');
        }

        if ($roomName === '') {
            $error = 'Bitte einen Raumnamen angeben.';
        } elseif ($computerCount < 1) {
            $error = 'Die Anzahl der Rechnerarbeitsplätze muss mindestens 1 betragen.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'UPDATE rooms
                     SET room_name = ?, computer_count = ?, description = ?
                     WHERE room_id = ?'
                );
                $stmt->execute(array($roomName, $computerCount, $description, $roomId));

                replaceRoomSoftware($pdo, $roomId, $selectedSoftware);

                if (isAdmin()) {
                    replaceRoomEditors($pdo, $roomId, $selectedEditors);
                }

                $pdo->commit();
                setFlash('success', 'Der Raum wurde gespeichert.');
                redirect('/rooms/index.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Raum speichern fehlgeschlagen: ' . $e->getMessage());
                $error = 'Der Raum konnte nicht gespeichert werden. Möglicherweise existiert der Name bereits.';
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Raum bearbeiten</h1>
        <p class="muted">Administratoren und freigegebene Bearbeiter dürfen Räume ändern.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/rooms/edit.php?id=<?php echo (int) $roomId; ?>">
        <?php echo csrfField(); ?>

        <label for="room_name">Raumname</label>
        <input type="text" id="room_name" name="room_name" value="<?php echo e($roomName); ?>" required>

        <label for="computer_count">Anzahl Rechnerarbeitsplätze</label>
        <input type="number" id="computer_count" name="computer_count" min="1" value="<?php echo (int) $computerCount; ?>" required>

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

        <?php if (isAdmin()): ?>
            <label>Raum-Bearbeiter</label>
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
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/rooms/index.php">Abbrechen</a>
            <?php if (isAdmin()): ?>
                <a class="btn btn-danger" href="<?php echo e(BASE_URL); ?>/rooms/delete.php?id=<?php echo (int) $roomId; ?>">Löschen</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
