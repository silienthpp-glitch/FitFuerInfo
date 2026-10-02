<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Kurse';
$currentNav = 'courses';

$courses = array();

try {
    $stmt = $pdo->query(
        "SELECT
            c.course_id,
            c.course_name,
            c.description,
            c.max_participants,
            c.active,
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) ORDER BY u.last_name SEPARATOR ', ') AS owner_names,
            GROUP_CONCAT(DISTINCT s.software_name ORDER BY s.software_name SEPARATOR ', ') AS software_names
         FROM courses c
         LEFT JOIN course_owners co ON co.course_id = c.course_id
         LEFT JOIN users u ON u.user_id = co.user_id
         LEFT JOIN course_software cs ON cs.course_id = c.course_id
         LEFT JOIN software s ON s.software_id = cs.software_id
         GROUP BY c.course_id, c.course_name, c.description, c.max_participants, c.active
         ORDER BY c.course_name"
    );
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Kursliste fehlgeschlagen: ' . $e->getMessage());
    setFlash('error', 'Die Kursliste konnte nicht geladen werden.');
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Kurse</h1>
        <p class="muted">Alle Mitarbeiter können Kurse ansehen und neue Kurse anlegen.</p>
    </div>
    <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/courses/create.php">Kurs anlegen</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kurs</th>
                    <th>Teilnehmer</th>
                    <th>Software</th>
                    <th>Eigentümer</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($courses) === 0): ?>
                    <tr>
                        <td colspan="6">Noch keine Kurse vorhanden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($course['course_name']); ?></strong><br>
                            <span class="muted"><?php echo e($course['description']); ?></span>
                        </td>
                        <td><?php echo (int) $course['max_participants']; ?></td>
                        <td><?php echo e($course['software_names'] ? $course['software_names'] : 'keine'); ?></td>
                        <td><?php echo e($course['owner_names'] ? $course['owner_names'] : 'keine'); ?></td>
                        <td>
                            <?php if ((int) $course['active'] === 1): ?>
                                <span class="badge badge-active">aktiv</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">deaktiviert</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (canEditCourse($pdo, $course['course_id'])): ?>
                                <a class="btn btn-small btn-secondary" href="<?php echo e(BASE_URL); ?>/courses/edit.php?id=<?php echo (int) $course['course_id']; ?>">
                                    Bearbeiten
                                </a>
                                <a class="btn btn-small btn-danger" href="<?php echo e(BASE_URL); ?>/courses/delete.php?id=<?php echo (int) $course['course_id']; ?>">
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
