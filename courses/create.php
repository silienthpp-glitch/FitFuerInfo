<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Kurs anlegen';
$currentNav = 'courses';

$error = '';
$courseName = '';
$description = '';
$maxParticipants = '';
$selectedSoftware = array();
$softwareList = fetchSoftwareList($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $courseName = postValue('course_name', '');
        $description = postValue('description', '');
        $maxParticipants = postInt('max_participants');
        $selectedSoftware = postIntArray('software_ids');

        if ($courseName === '') {
            $error = 'Bitte einen Kursnamen angeben.';
        } elseif ($maxParticipants < 1) {
            $error = 'Die maximale Teilnehmerzahl muss mindestens 1 betragen.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO courses
                        (course_name, description, max_participants, created_by, active)
                     VALUES
                        (?, ?, ?, ?, 1)'
                );
                $stmt->execute(
                    array($courseName, $description, $maxParticipants, currentUserId())
                );

                $courseId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    'INSERT INTO course_owners (course_id, user_id) VALUES (?, ?)'
                );
                $stmt->execute(array($courseId, currentUserId()));

                replaceCourseSoftware($pdo, $courseId, $selectedSoftware);

                $pdo->commit();
                setFlash('success', 'Der Kurs wurde angelegt. Sie sind automatisch Eigentümer.');
                redirect('/courses/index.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Kurs anlegen fehlgeschlagen: ' . $e->getMessage());
                $error = 'Der Kurs konnte nicht angelegt werden.';
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Kurs anlegen</h1>
        <p class="muted">Der Ersteller wird automatisch als Eigentümer gespeichert.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/courses/create.php">
        <?php echo csrfField(); ?>

        <label for="course_name">Kursname</label>
        <input type="text" id="course_name" name="course_name" value="<?php echo e($courseName); ?>" required>

        <label for="description">Beschreibung</label>
        <textarea id="description" name="description"><?php echo e($description); ?></textarea>

        <label for="max_participants">Maximale Teilnehmerzahl</label>
        <input type="number" id="max_participants" name="max_participants" min="1" value="<?php echo e($maxParticipants); ?>" required>

        <label>Benötigte Software</label>
        <?php if (count($softwareList) === 0): ?>
            <p class="hint">Es ist noch keine Software vorhanden. Ein Administrator kann Software anlegen.</p>
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

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/courses/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
