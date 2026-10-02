<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Mitarbeiter bearbeiten';
$currentNav = 'users';

$userId = getInt('id');
$error = '';

$stmt = $pdo->prepare(
    'SELECT user_id, username, first_name, last_name, role, active, password_hash
     FROM users
     WHERE user_id = ?
     LIMIT 1'
);
$stmt->execute(array($userId));
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Der Benutzer wurde nicht gefunden.');
    redirect('/users/index.php');
}

$firstName = $user['first_name'];
$lastName = $user['last_name'];
$active = (int) $user['active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $action = postValue('action', 'save');

        if ($action === 'reset_code') {
            if ($user['role'] !== 'employee') {
                $error = 'Aktivierungscodes können nur für Mitarbeiter erzeugt werden.';
            } else {
                try {
                    $token = createPasswordToken($pdo, $userId);
                    storeOneTimeActivation($user['username'], $token);
                    setFlash('success', 'Ein neuer Aktivierungscode wurde erzeugt.');
                    redirect('/users/activation.php');
                } catch (PDOException $e) {
                    error_log('Aktivierungscode erzeugen fehlgeschlagen: ' . $e->getMessage());
                    $error = 'Der Aktivierungscode konnte nicht erzeugt werden.';
                }
            }
        } else {
            $firstName = postValue('first_name', '');
            $lastName = postValue('last_name', '');
            $active = postInt('active') === 1 ? 1 : 0;

            if ($firstName === '' || $lastName === '') {
                $error = 'Bitte Vorname und Nachname ausfüllen.';
            }

            if ($error === '' && $active === 0 && (int) $user['user_id'] === currentUserId()) {
                $error = 'Sie können das eigene Konto nicht deaktivieren.';
            }

            if ($error === '' && $active === 0 && $user['role'] === 'admin') {
                $stmt = $pdo->query(
                    "SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1"
                );
                if ((int) $stmt->fetchColumn() <= 1) {
                    $error = 'Der letzte aktive Administrator kann nicht deaktiviert werden.';
                }
            }

            if ($error === '') {
                try {
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET first_name = ?, last_name = ?, active = ?
                         WHERE user_id = ?'
                    );
                    $stmt->execute(array($firstName, $lastName, $active, $userId));
                    setFlash('success', 'Der Mitarbeiter wurde aktualisiert.');
                    redirect('/users/index.php');
                } catch (PDOException $e) {
                    error_log('Mitarbeiter aktualisieren fehlgeschlagen: ' . $e->getMessage());
                    $error = 'Der Mitarbeiter konnte nicht gespeichert werden.';
                }
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Mitarbeiter bearbeiten</h1>
        <p class="muted">Das Passwort wird nie vom Administrator gesetzt. Bei Bedarf kann nur ein neuer Einmalcode erzeugt werden.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/users/edit.php?id=<?php echo (int) $userId; ?>">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="save">

        <label>Benutzername</label>
        <input type="text" value="<?php echo e($user['username']); ?>" disabled>

        <label>Rolle</label>
        <input type="text" value="<?php echo e(roleLabel($user['role'])); ?>" disabled>

        <label>Passwortstatus</label>
        <input
            type="text"
            value="<?php echo userHasPasswordSet($user) ? 'vom Mitarbeiter gesetzt' : 'noch nicht gesetzt'; ?>"
            disabled
        >

        <label for="first_name">Vorname</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo e($firstName); ?>" required>

        <label for="last_name">Nachname</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo e($lastName); ?>" required>

        <label for="active">Status</label>
        <select id="active" name="active">
            <option value="1"<?php echo $active === 1 ? ' selected' : ''; ?>>aktiv</option>
            <option value="0"<?php echo $active === 0 ? ' selected' : ''; ?>>deaktiviert</option>
        </select>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/users/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php if ($user['role'] === 'employee'): ?>
    <div class="card">
        <h2>Passwort-Reset</h2>
        <p class="hint">
            Ein neuer Code macht alle bisherigen Aktivierungscodes ungültig.
            Der Administrator sieht danach nur den Link, nicht das Passwort.
        </p>
        <form
            method="post"
            class="js-confirm"
            data-confirm="Neuen Einmal-Aktivierungscode erzeugen?"
            action="<?php echo e(BASE_URL); ?>/users/edit.php?id=<?php echo (int) $userId; ?>"
        >
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="reset_code">
            <button type="submit" class="btn btn-secondary">Neuen Aktivierungscode erzeugen</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
