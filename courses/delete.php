<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Kurs löschen';
$currentNav = 'courses';

$courseId = getInt('id');
if ($courseId <= 0) {
    $courseId = postInt('id');
}

$stmt = $pdo->prepare(
    'SELECT course_id, course_name, active
     FROM courses
     WHERE course_id = ?
     LIMIT 1'
);
$stmt->execute(array($courseId));
$course = $stmt->fetch();

if (!$course) {
    setFlash('error', 'Der Kurs wurde nicht gefunden.');
    redirect('/courses/index.php');
}

if (!canEditCourse($pdo, $courseId)) {
    setFlash('error', 'Sie dürfen diesen Kurs nicht löschen.');
    redirect('/courses/index.php');
}

if (courseHasFutureBookings($pdo, $courseId)) {
    setFlash('error', 'Der Kurs kann nicht gelöscht oder deaktiviert werden, weil noch zukünftige Raumbuchungen existieren.');
    redirect('/courses/index.php');
}

// Ohne Zukunftsbuchungen darf gelöscht werden.
// Vergangene Buchungen bleiben wegen des Fremdschlüssels erhalten:
// der Kurs wird dann deaktiviert statt physisch gelöscht.
$hasPastBookings = courseHasAnyBookings($pdo, $courseId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/courses/delete.php?id=' . $courseId);

    if (courseHasFutureBookings($pdo, $courseId)) {
        setFlash('error', 'Der Kurs kann nicht gelöscht oder deaktiviert werden, weil noch zukünftige Raumbuchungen existieren.');
        redirect('/courses/index.php');
    }

    try {
        if ($hasPastBookings) {
            $stmt = $pdo->prepare(
                'UPDATE courses
                 SET active = 0
                 WHERE course_id = ?'
            );
            $stmt->execute(array($courseId));
            setFlash('success', 'Der Kurs hat vergangene Buchungen und bleibt deshalb in der Historie erhalten. Er wurde deaktiviert.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM courses WHERE course_id = ?');
            $stmt->execute(array($courseId));
            setFlash('success', 'Der Kurs wurde gelöscht.');
        }

        redirect('/courses/index.php');
    } catch (PDOException $e) {
        error_log('Kurs löschen fehlgeschlagen: ' . $e->getMessage());
        setFlash('error', 'Der Kurs konnte nicht gelöscht werden.');
        redirect('/courses/index.php');
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?php echo $hasPastBookings ? 'Kurs deaktivieren' : 'Kurs löschen'; ?></h1>
        <p class="muted">
            <?php if ($hasPastBookings): ?>
                Zukünftige Buchungen sind nicht vorhanden. Vergangene Buchungen müssen wegen der Datenbankbeziehungen erhalten bleiben.
            <?php else: ?>
                Diese Aktion kann nicht rückgängig gemacht werden.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="card">
    <?php if ($hasPastBookings): ?>
        <p>
            Der Kurs
            <strong><?php echo e($course['course_name']); ?></strong>
            hat noch vergangene Raumbuchungen.
            Ein endgültiges Löschen ist deshalb nicht möglich.
            Stattdessen wird der Kurs deaktiviert und erscheint nicht mehr für neue Buchungen.
        </p>
    <?php else: ?>
        <p>
            Soll der Kurs
            <strong><?php echo e($course['course_name']); ?></strong>
            wirklich gelöscht werden?
        </p>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/courses/delete.php?id=<?php echo (int) $courseId; ?>">
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $courseId; ?>">
        <div class="form-actions">
            <?php if ($hasPastBookings): ?>
                <button type="submit" class="btn btn-danger">Kurs deaktivieren</button>
            <?php else: ?>
                <button type="submit" class="btn btn-danger">Endgültig löschen</button>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/courses/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
