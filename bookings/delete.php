<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Buchung löschen';
$currentNav = 'bookings';

$bookingId = getInt('id');
if ($bookingId <= 0) {
    $bookingId = postInt('id');
}

$stmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.created_by,
        b.booking_date,
        b.start_time,
        b.end_time,
        r.room_name,
        c.course_name
     FROM bookings b
     INNER JOIN rooms r ON r.room_id = b.room_id
     INNER JOIN courses c ON c.course_id = b.course_id
     WHERE b.booking_id = ?
     LIMIT 1"
);
$stmt->execute(array($bookingId));
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('error', 'Die Buchung wurde nicht gefunden.');
    redirect('/bookings/index.php');
}

if (!canDeleteBooking($booking)) {
    setFlash('error', 'Sie dürfen diese Buchung nicht löschen.');
    redirect('/bookings/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/bookings/delete.php?id=' . $bookingId);

    if (!canDeleteBooking($booking)) {
        setFlash('error', 'Sie dürfen diese Buchung nicht löschen.');
        redirect('/bookings/index.php');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM bookings WHERE booking_id = ?');
        $stmt->execute(array($bookingId));
        setFlash('success', 'Die Buchung wurde gelöscht.');
        redirect('/bookings/index.php');
    } catch (PDOException $e) {
        error_log('Buchung löschen fehlgeschlagen: ' . $e->getMessage());
        setFlash('error', 'Die Buchung konnte nicht gelöscht werden.');
        redirect('/bookings/index.php');
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Buchung löschen</h1>
        <p class="muted">Nur der Ersteller und der Administrator dürfen eine Buchung löschen.</p>
    </div>
</div>

<div class="card">
    <p>
        Soll die Buchung für
        <strong><?php echo e($booking['course_name']); ?></strong>
        in Raum
        <strong><?php echo e($booking['room_name']); ?></strong>
        am
        <strong><?php echo e(formatDateDe($booking['booking_date'])); ?></strong>
        von
        <?php echo e(formatTimeDe($booking['start_time'])); ?>
        bis
        <?php echo e(formatTimeDe($booking['end_time'])); ?>
        wirklich gelöscht werden?
    </p>

    <form
        method="post"
        class="js-confirm"
        data-confirm="Buchung wirklich löschen?"
        action="<?php echo e(BASE_URL); ?>/bookings/delete.php?id=<?php echo (int) $bookingId; ?>"
    >
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $bookingId; ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Endgültig löschen</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/bookings/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
