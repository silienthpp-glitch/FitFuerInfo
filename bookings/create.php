<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Raum buchen';
$currentNav = 'bookings';

$error = '';
$errors = array();
$courseId = 0;
$roomId = 0;
$bookingDate = '';
$startTime = '';
$endTime = '';

$courses = $pdo->query(
    "SELECT course_id, course_name, max_participants
     FROM courses
     WHERE active = 1
     ORDER BY course_name"
)->fetchAll();

$rooms = $pdo->query(
    'SELECT room_id, room_name, computer_count
     FROM rooms
     ORDER BY room_name'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $courseId = postInt('course_id');
        $roomId = postInt('room_id');
        $bookingDate = postValue('booking_date', '');
        $startTime = postValue('start_time', '');
        $endTime = postValue('end_time', '');

        $errors = validateBooking($pdo, $roomId, $courseId, $bookingDate, $startTime, $endTime);

        if (count($errors) === 0) {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO bookings
                        (room_id, course_id, created_by, booking_date, start_time, end_time)
                     VALUES
                        (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute(
                    array(
                        $roomId,
                        $courseId,
                        currentUserId(),
                        $bookingDate,
                        normalizeTime($startTime),
                        normalizeTime($endTime)
                    )
                );
                setFlash('success', 'Die Raumbuchung wurde gespeichert.');
                redirect('/bookings/index.php');
            } catch (PDOException $e) {
                error_log('Buchung speichern fehlgeschlagen: ' . $e->getMessage());
                $error = 'Die Buchung konnte nicht gespeichert werden.';
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Raum buchen</h1>
        <p class="muted">Eine Buchung gehört immer zu einem Kurs. Ungeeignete Räume werden mit Begründung abgelehnt.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (count($errors) > 0): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $item): ?>
                <div><?php echo e($item); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (count($courses) === 0 || count($rooms) === 0): ?>
        <p class="hint">
            Für eine Buchung werden mindestens ein aktiver Kurs und ein Raum benötigt.
        </p>
    <?php else: ?>
        <form method="post" action="<?php echo e(BASE_URL); ?>/bookings/create.php">
            <?php echo csrfField(); ?>

            <label for="course_id">Kurs</label>
            <select id="course_id" name="course_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo (int) $course['course_id']; ?>"<?php echo $courseId === (int) $course['course_id'] ? ' selected' : ''; ?>>
                        <?php echo e($course['course_name'] . ' (max. ' . $course['max_participants'] . ' Teilnehmer)'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="room_id">Raum</label>
            <select id="room_id" name="room_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo (int) $room['room_id']; ?>"<?php echo $roomId === (int) $room['room_id'] ? ' selected' : ''; ?>>
                        <?php echo e($room['room_name'] . ' (' . $room['computer_count'] . ' Arbeitsplätze)'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="booking_date">Datum</label>
            <input type="date" id="booking_date" name="booking_date" value="<?php echo e($bookingDate); ?>" required>

            <label for="start_time">Startzeit</label>
            <input type="time" id="start_time" name="start_time" value="<?php echo e($startTime); ?>" required>

            <label for="end_time">Endzeit</label>
            <input type="time" id="end_time" name="end_time" value="<?php echo e($endTime); ?>" required>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Buchung speichern</button>
                <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/bookings/index.php">Abbrechen</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
