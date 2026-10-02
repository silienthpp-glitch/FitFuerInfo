<?php

require_once dirname(__FILE__) . '/../includes/auth.php';

$pageTitle = 'Räume';
$currentNav = 'rooms';

$rooms = array();

try {
    $stmt = $pdo->query(
        "SELECT
            r.room_id,
            r.room_name,
            r.computer_count,
            r.description,
            GROUP_CONCAT(DISTINCT s.software_name ORDER BY s.software_name SEPARATOR ', ') AS software_names,
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) ORDER BY u.last_name SEPARATOR ', ') AS editor_names
         FROM rooms r
         LEFT JOIN room_software rs ON rs.room_id = r.room_id
         LEFT JOIN software s ON s.software_id = rs.software_id
         LEFT JOIN room_editors re ON re.room_id = r.room_id
         LEFT JOIN users u ON u.user_id = re.user_id
         GROUP BY r.room_id, r.room_name, r.computer_count, r.description
         ORDER BY r.room_name"
    );
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Raumliste fehlgeschlagen: ' . $e->getMessage());
    setFlash('error', 'Die Raumliste konnte nicht geladen werden.');
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Räume</h1>
        <p class="muted">Alle Mitarbeiter können Räume ansehen. Neue Räume legt nur der Administrator an.</p>
    </div>
    <?php if (isAdmin()): ?>
        <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/rooms/create.php">Raum anlegen</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Raum</th>
                    <th>Arbeitsplätze</th>
                    <th>Software</th>
                    <th>Bearbeiter</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rooms) === 0): ?>
                    <tr>
                        <td colspan="5">Noch keine Räume vorhanden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($room['room_name']); ?></strong><br>
                            <span class="muted"><?php echo e($room['description']); ?></span>
                        </td>
                        <td><?php echo (int) $room['computer_count']; ?></td>
                        <td><?php echo e($room['software_names'] ? $room['software_names'] : 'keine'); ?></td>
                        <td><?php echo e($room['editor_names'] ? $room['editor_names'] : 'nur Admin'); ?></td>
                        <td>
                            <?php if (canEditRoom($pdo, $room['room_id'])): ?>
                                <a class="btn btn-small btn-secondary" href="<?php echo e(BASE_URL); ?>/rooms/edit.php?id=<?php echo (int) $room['room_id']; ?>">
                                    Bearbeiten
                                </a>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                                <a class="btn btn-small btn-danger" href="<?php echo e(BASE_URL); ?>/rooms/delete.php?id=<?php echo (int) $room['room_id']; ?>">
                                    Löschen
                                </a>
                            <?php endif; ?>
                            <?php if (!canEditRoom($pdo, $room['room_id']) && !isAdmin()): ?>
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
