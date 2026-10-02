<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Raum löschen';
$currentNav = 'rooms';

$roomId = getInt('id');
if ($roomId <= 0) {
    $roomId = postInt('id');
}

$stmt = $pdo->prepare(
    'SELECT room_id, room_name
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

if (roomHasAnyBookings($pdo, $roomId)) {
    setFlash('error', 'Der Raum kann nicht gelöscht werden, weil noch Buchungen existieren.');
    redirect('/rooms/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/rooms/delete.php?id=' . $roomId);

    try {
        $stmt = $pdo->prepare('DELETE FROM rooms WHERE room_id = ?');
        $stmt->execute(array($roomId));
        setFlash('success', 'Der Raum wurde gelöscht.');
        redirect('/rooms/index.php');
    } catch (PDOException $e) {
        error_log('Raum löschen fehlgeschlagen: ' . $e->getMessage());
        setFlash('error', 'Der Raum konnte nicht gelöscht werden.');
        redirect('/rooms/index.php');
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Raum löschen</h1>
        <p class="muted">Diese Aktion kann nicht rückgängig gemacht werden.</p>
    </div>
</div>

<div class="card">
    <p>
        Soll der Raum
        <strong><?php echo e($room['room_name']); ?></strong>
        wirklich gelöscht werden?
    </p>

    <form method="post" action="<?php echo e(BASE_URL); ?>/rooms/delete.php?id=<?php echo (int) $roomId; ?>">
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $roomId; ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Endgültig löschen</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/rooms/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
