<?php

require_once dirname(__FILE__) . '/includes/auth.php';

$pageTitle = 'Dashboard';
$currentNav = 'dashboard';

$courseCount = 0;
$roomCount = 0;
$upcomingCount = 0;
$userCount = 0;
$upcomingBookings = array();

try {
    $courseCount = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    $roomCount = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    $upcomingCount = (int) $pdo->query(
        "SELECT COUNT(*)
         FROM bookings
         WHERE booking_date > CURDATE()
            OR (booking_date = CURDATE() AND end_time >= CURTIME())"
    )->fetchColumn();

    if (isAdmin()) {
        $userCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'employee'"
        )->fetchColumn();
    }

    $stmt = $pdo->query(
        "SELECT
            b.booking_date,
            b.start_time,
            b.end_time,
            r.room_name,
            c.course_name,
            u.username
         FROM bookings b
         INNER JOIN rooms r ON r.room_id = b.room_id
         INNER JOIN courses c ON c.course_id = b.course_id
         INNER JOIN users u ON u.user_id = b.created_by
         WHERE b.booking_date > CURDATE()
            OR (b.booking_date = CURDATE() AND b.end_time >= CURTIME())
         ORDER BY b.booking_date ASC, b.start_time ASC
         LIMIT 8"
    );
    $upcomingBookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard-Abfrage fehlgeschlagen: ' . $e->getMessage());
    setFlash('error', 'Die Übersicht konnte nicht vollständig geladen werden.');
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="muted">
            Willkommen, <?php echo e($_SESSION['username']); ?>
            (<?php echo e(roleLabel($_SESSION['role'])); ?>).
        </p>
    </div>
</div>

<div class="card-grid">
    <a class="card card-link" href="<?php echo e(BASE_URL); ?>/courses/index.php">
        <h2>Kurse</h2>
        <p class="stat"><?php echo (int) $courseCount; ?></p>
        <p>Kursprofile ansehen und verwalten</p>
    </a>

    <a class="card card-link" href="<?php echo e(BASE_URL); ?>/rooms/index.php">
        <h2>Räume</h2>
        <p class="stat"><?php echo (int) $roomCount; ?></p>
        <p>Räume und Ausstattung ansehen</p>
    </a>

    <a class="card card-link" href="<?php echo e(BASE_URL); ?>/bookings/index.php">
        <h2>Buchungen</h2>
        <p class="stat"><?php echo (int) $upcomingCount; ?></p>
        <p>Kommende Raumbuchungen</p>
    </a>

    <?php if (isAdmin()): ?>
        <a class="card card-link" href="<?php echo e(BASE_URL); ?>/users/index.php">
            <h2>Mitarbeiter</h2>
            <p class="stat"><?php echo (int) $userCount; ?></p>
            <p>Benutzer anlegen und verwalten</p>
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2>Kommende Buchungen</h2>
        <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/bookings/create.php">Raum buchen</a>
    </div>

    <?php if (count($upcomingBookings) === 0): ?>
        <p class="muted">Es sind keine zukünftigen Buchungen vorhanden.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Zeit</th>
                        <th>Raum</th>
                        <th>Kurs</th>
                        <th>Ersteller</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingBookings as $booking): ?>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (isAdmin()): ?>
    <div class="card">
        <h2>Administrator</h2>
        <p class="muted">Zusätzliche Verwaltungsbereiche:</p>
        <p class="action-row">
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/users/index.php">Mitarbeiterverwaltung</a>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/software/index.php">Softwareverwaltung</a>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/rooms/create.php">Neuen Raum anlegen</a>
        </p>
    </div>
<?php endif; ?>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
