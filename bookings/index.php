<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Raumbuchungen';
$currentNav = 'bookings';

$filterDate = getValue('booking_date', '');
$filterRoomId = getInt('room_id');

if ($filterDate !== '' && !isValidDate($filterDate)) {
    $filterDate = '';
}

$rooms = $pdo->query(
    'SELECT room_id, room_name FROM rooms ORDER BY room_name'
)->fetchAll();

$sql = "SELECT
            b.booking_id,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.created_by,
            r.room_name,
            c.course_name,
            u.username
        FROM bookings b
        INNER JOIN rooms r ON r.room_id = b.room_id
        INNER JOIN courses c ON c.course_id = b.course_id
        INNER JOIN users u ON u.user_id = b.created_by
        WHERE 1 = 1";
$params = array();

if ($filterDate !== '') {
    $sql .= ' AND b.booking_date = ?';
    $params[] = $filterDate;
} else {
    $sql .= ' AND (b.booking_date > CURDATE() OR (b.booking_date = CURDATE() AND b.end_time >= CURTIME()))';
}

if ($filterRoomId > 0) {
    $sql .= ' AND b.room_id = ?';
    $params[] = $filterRoomId;
}

$sql .= ' ORDER BY b.booking_date ASC, b.start_time ASC, r.room_name ASC';

$bookings = array();

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Buchungsliste fehlgeschlagen: ' . $e->getMessage());
    setFlash('error', 'Die Buchungsliste konnte nicht geladen werden.');
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Raumbuchungen</h1>
        <p class="muted">Ohne Datumsfilter werden aktuelle und zukünftige Buchungen angezeigt.</p>
    </div>
    <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/bookings/create.php">Raum buchen</a>
</div>

<div class="card">
    <form method="get" action="<?php echo e(BASE_URL); ?>/bookings/index.php" class="filter-bar">
        <div>
            <label for="booking_date">Datum</label>
            <input type="date" id="booking_date" name="booking_date" value="<?php echo e($filterDate); ?>">
        </div>
        <div>
            <label for="room_id">Raum</label>
            <select id="room_id" name="room_id">
                <option value="0">alle Räume</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo (int) $room['room_id']; ?>"<?php echo $filterRoomId === (int) $room['room_id'] ? ' selected' : ''; ?>>
                        <?php echo e($room['room_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary">Filtern</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/bookings/index.php">Zurücksetzen</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Zeit</th>
                    <th>Raum</th>
                    <th>Kurs</th>
                    <th>Ersteller</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) === 0): ?>
                    <tr>
                        <td colspan="6">Keine Buchungen für die aktuelle Auswahl.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo e(formatDateDe($booking['booking_date'])); ?></td>
                        <td>
                            <?php echo e(formatTimeDe($booking['start_time'])); ?>
                            –
                            <?php echo e(formatTimeDe($booking['end_time'])); ?>
                        </td>
                        <td><?php echo e($booking['room_name']); ?></td>
                        <td><?php echo e($booking['course_name']); ?></td>
                        <td><?php echo e($booking['username']); ?></td>
                        <td>
                            <?php if (canDeleteBooking($booking)): ?>
                                <a class="btn btn-small btn-danger" href="<?php echo e(BASE_URL); ?>/bookings/delete.php?id=<?php echo (int) $booking['booking_id']; ?>">
                                    Löschen
                                </a>
                            <?php else: ?>
                                <span class="muted">nur Ansicht</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
