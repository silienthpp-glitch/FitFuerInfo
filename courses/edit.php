<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Kurs bearbeiten';
$currentNav = 'courses';

$courseId = getInt('id');
$error = '';

$stmt = $pdo->prepare(
    'SELECT course_id, course_name, description, max_participants, created_by, active
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
    setFlash('error', 'Sie dürfen diesen Kurs nicht bearbeiten.');
    redirect('/courses/index.php');
}

$courseName = $course['course_name'];
$description = $course['description'];
$maxParticipants = (int) $course['max_participants'];
$active = (int) $course['active'];
$selectedSoftware = fetchCourseSoftwareIds($pdo, $courseId);
$selectedOwners = fetchCourseOwnerIds($pdo, $courseId);
$softwareList = fetchSoftwareList($pdo);
$employees = fetchAllEmployees($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $courseName = postValue('course_name', '');
        $description = postValue('description', '');
        $maxParticipants = postInt('max_participants');
        $active = postInt('active') === 1 ? 1 : 0;
        $selectedSoftware = postIntArray('software_ids');
        $selectedOwners = postIntArray('owner_ids');

        if (!isAdmin()) {
            $selectedOwners = fetchCourseOwnerIds($pdo, $courseId);
        }

        if ($courseName === '') {
            $error = 'Bitte einen Kursnamen angeben.';
        } elseif ($maxParticipants < 1) {
            $error = 'Die maximale Teilnehmerzahl muss mindestens 1 betragen.';
        } elseif (isAdmin() && count($selectedOwners) === 0) {
            $error = 'Ein Kurs muss mindestens einen Eigentümer haben.';
        } elseif ($active === 0 && courseHasFutureBookings($pdo, $courseId)) {
            $error = 'Der Kurs kann nicht deaktiviert werden, weil noch zukünftige Raumbuchungen existieren.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'UPDATE courses
                     SET course_name = ?, description = ?, max_participants = ?, active = ?
                     WHERE course_id = ?'
                );
                $stmt->execute(
                    array($courseName, $description, $maxParticipants, $active, $courseId)
                );

                replaceCourseSoftware($pdo, $courseId, $selectedSoftware);

                if (isAdmin()) {
                    replaceCourseOwners($pdo, $courseId, $selectedOwners);
                }

                $pdo->commit();
                setFlash('success', 'Der Kurs wurde gespeichert.');
                redirect('/courses/index.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Kurs speichern fehlgeschlagen: ' . $e->getMessage());
                $error = 'Der Kurs konnte nicht gespeichert werden.';
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Kurs bearbeiten</h1>
        <p class="muted">Nur Eigentümer und Administratoren dürfen Kurse ändern.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/courses/edit.php?id=<?php echo (int) $courseId; ?>">
        <?php echo csrfField(); ?>

        <label for="course_name">Kursname</label>
        <input type="text" id="course_name" name="course_name" value="<?php echo e($courseName); ?>" required>

        <label for="description">Beschreibung</label>
        <textarea id="description" name="description"><?php echo e($description); ?></textarea>

        <label for="max_participants">Maximale Teilnehmerzahl</label>
        <input type="number" id="max_participants" name="max_participants" min="1" value="<?php echo (int) $maxParticipants; ?>" required>

        <label for="active">Status</label>
        <select id="active" name="active">
            <option value="1"<?php echo $active === 1 ? ' selected' : ''; ?>>aktiv</option>
            <option value="0"<?php echo $active === 0 ? ' selected' : ''; ?>>deaktiviert</option>
        </select>

        <label>Benötigte Software</label>
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
            <label>Eigentümer</label>
            <div class="checkbox-list">
                <?php foreach ($employees as $employee): ?>
                    <label>
                        <input
                            type="checkbox"
                            name="owner_ids[]"
                            value="<?php echo (int) $employee['user_id']; ?>"
                            <?php echo in_array((int) $employee['user_id'], $selectedOwners, true) ? 'checked' : ''; ?>
                        >
                        <?php echo e($employee['last_name'] . ', ' . $employee['first_name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/courses/index.php">Abbrechen</a>
            <a class="btn btn-danger" href="<?php echo e(BASE_URL); ?>/courses/delete.php?id=<?php echo (int) $courseId; ?>">Löschen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
