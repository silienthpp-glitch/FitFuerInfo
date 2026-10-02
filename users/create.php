<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Mitarbeiter anlegen';
$currentNav = 'users';

$error = '';
$username = '';
$firstName = '';
$lastName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $username = postValue('username', '');
        $firstName = postValue('first_name', '');
        $lastName = postValue('last_name', '');

        if ($username === '' || $firstName === '' || $lastName === '') {
            $error = 'Bitte alle Felder ausfüllen.';
        } else {
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute(array($username));

            if ($stmt->fetch()) {
                $error = 'Dieser Benutzername ist bereits vergeben.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare(
                        "INSERT INTO users
                            (username, first_name, last_name, password_hash, role, active)
                         VALUES
                            (?, ?, ?, NULL, 'employee', 1)"
                    );
                    $stmt->execute(array($username, $firstName, $lastName));
                    $userId = (int) $pdo->lastInsertId();

                    $token = createPasswordToken($pdo, $userId);
                    $pdo->commit();

                    storeOneTimeActivation($username, $token);
                    setFlash('success', 'Der Mitarbeiter wurde angelegt. Bitte den Aktivierungscode einmalig weitergeben.');
                    redirect('/users/activation.php');
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('Mitarbeiter anlegen fehlgeschlagen: ' . $e->getMessage());
                    $error = 'Der Mitarbeiter konnte nicht angelegt werden.';
                }
            }
        }
    }
}

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Mitarbeiter anlegen</h1>
        <p class="muted">Es wird kein Passwort vergeben. Der Mitarbeiter setzt es selbst über einen Einmal-Aktivierungscode.</p>
    </div>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(BASE_URL); ?>/users/create.php">
        <?php echo csrfField(); ?>

        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" value="<?php echo e($username); ?>" required>

        <label for="first_name">Vorname</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo e($firstName); ?>" required>

        <label for="last_name">Nachname</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo e($lastName); ?>" required>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Anlegen und Aktivierungscode erzeugen</button>
            <a class="btn btn-secondary" href="<?php echo e(BASE_URL); ?>/users/index.php">Abbrechen</a>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
