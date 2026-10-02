<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Software';
$currentNav = 'software';

$error = '';
$softwareName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $action = postValue('action', 'create');

        if ($action === 'delete') {
            $softwareId = postInt('software_id');

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM course_software WHERE software_id = ?'
            );
            $stmt->execute(array($softwareId));
            $courseUsage = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM room_software WHERE software_id = ?'
            );
            $stmt->execute(array($softwareId));
            $roomUsage = (int) $stmt->fetchColumn();

            if ($courseUsage > 0 || $roomUsage > 0) {
                $error = 'Diese Software ist noch Kursen oder Räumen zugeordnet und kann nicht gelöscht werden.';
            } else {
                try {
                    $stmt = $pdo->prepare('DELETE FROM software WHERE software_id = ?');
                    $stmt->execute(array($softwareId));
                    setFlash('success', 'Die Software wurde gelöscht.');
                    redirect('/software/index.php');
                } catch (PDOException $e) {
                    error_log('Software löschen fehlgeschlagen: ' . $e->getMessage());
                    $error = 'Die Software konnte nicht gelöscht werden.';
                }
            }
        } else {
            $softwareName = postValue('software_name', '');

            if ($softwareName === '') {
                $error = 'Bitte einen Softwarenamen angeben.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO software (software_name) VALUES (?)'
                    );
                    $stmt->execute(array($softwareName));
                    setFlash('success', 'Die Software wurde angelegt.');
                    redirect('/software/index.php');
                } catch (PDOException $e) {
                    error_log('Software anlegen fehlgeschlagen: ' . $e->getMessage());
                    $error = 'Die Software konnte nicht angelegt werden. Möglicherweise existiert der Name bereits.';
                }
            }
        }
    }
}

$softwareList = array();

try {
    $softwareList = fetchSoftwareList($pdo);
} catch (PDOException $e) {
    error_log('Softwareliste fehlgeschlagen: ' . $e->getMessage());
    $error = 'Die Softwareliste konnte nicht geladen werden.';
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Softwareverwaltung</h1>
        <p class="muted">Software wird zentral gepflegt und Kursen sowie Räumen zugeordnet.</p>
    </div>
</div>

<div class="card">
    <h2>Neue Software</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/software/index.php">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="create">

        <label for="software_name">Softwarename</label>
        <input type="text" id="software_name" name="software_name" value="<?php echo e($softwareName); ?>" required>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Hinzufügen</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Vorhandene Software</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($softwareList) === 0): ?>
                    <tr>
                        <td colspan="2">Noch keine Software vorhanden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($softwareList as $software): ?>
                    <tr>
                        <td><?php echo e($software['software_name']); ?></td>
                        <td>
                            <form
                                method="post"
                                class="js-confirm"
                                data-confirm="Soll diese Software wirklich gelöscht werden?"
                                action="<?php echo e(BASE_URL); ?>/software/index.php"
                            >
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="software_id" value="<?php echo (int) $software['software_id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
