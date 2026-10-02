<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Mitarbeiter';
$currentNav = 'users';

$users = array();

try {
    $stmt = $pdo->query(
        'SELECT user_id, username, first_name, last_name, role, active, password_hash, created_at
         FROM users
         ORDER BY last_name, first_name'
    );
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Mitarbeiterliste fehlgeschlagen: ' . $e->getMessage());
    setFlash('error', 'Die Mitarbeiterliste konnte nicht geladen werden.');
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Mitarbeiterverwaltung</h1>
        <p class="muted">Administratoren legen Konten an und deaktivieren sie. Passwörter setzen nur die Mitarbeiter selbst.</p>
    </div>
    <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/users/create.php">Mitarbeiter anlegen</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Benutzername</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th>Passwort</th>
                    <th>Angelegt</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) === 0): ?>
                    <tr>
                        <td colspan="7">Keine Benutzer vorhanden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['last_name'] . ', ' . $user['first_name']); ?></td>
                        <td><?php echo e($user['username']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : ''; ?>">
                                <?php echo e(roleLabel($user['role'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int) $user['active'] === 1): ?>
                                <span class="badge badge-active">aktiv</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">deaktiviert</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (userHasPasswordSet($user)): ?>
                                <span class="badge badge-active">gesetzt</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">offen</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e(formatDateDe($user['created_at'])); ?></td>
                        <td>
                            <a class="btn btn-small btn-secondary" href="<?php echo e(BASE_URL); ?>/users/edit.php?id=<?php echo (int) $user['user_id']; ?>">
                                Bearbeiten
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
