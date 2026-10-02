<?php

require_once dirname(__FILE__) . '/includes/init.php';

$error = '';
$message = '';
$tokenRecord = null;
$code = getValue('code', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = postValue('code', '');
    $tokenRecord = findValidPasswordToken($pdo, $code);

    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } elseif (!$tokenRecord) {
        $error = 'Der Aktivierungscode ist ungültig oder bereits verwendet.';
    } else {
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $passwordRepeat = isset($_POST['password_repeat']) ? $_POST['password_repeat'] : '';
        $passwordError = validatePassword($password);

        if ($passwordError !== '') {
            $error = $passwordError;
        } elseif ($password !== $passwordRepeat) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET password_hash = ?
                     WHERE user_id = ?'
                );
                $stmt->execute(
                    array(
                        password_hash($password, PASSWORD_DEFAULT),
                        $tokenRecord['user_id']
                    )
                );

                markPasswordTokenUsed($pdo, $tokenRecord['token_id']);
                invalidatePasswordTokens($pdo, $tokenRecord['user_id']);

                $pdo->commit();
                $message = 'Das Passwort wurde gespeichert. Sie können sich jetzt anmelden.';
                $tokenRecord = null;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('Passwort setzen fehlgeschlagen: ' . $e->getMessage());
                $error = 'Das Passwort konnte nicht gespeichert werden.';
            }
        }
    }
} else {
    $tokenRecord = findValidPasswordToken($pdo, $code);
    if ($code === '' || !$tokenRecord) {
        $error = 'Der Aktivierungscode ist ungültig oder bereits verwendet.';
        $tokenRecord = null;
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort festlegen | FitFuerInfo</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>FitFuerInfo</h1>
        <p class="auth-subtitle">Eigenes Passwort festlegen</p>
        <h2>Passwort vergeben</h2>

        <?php if ($error !== '' && $message === ''): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
            <p>
                <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/login.php">Zum Login</a>
            </p>
        <?php elseif ($tokenRecord): ?>
            <p class="hint">
                Konto: <strong><?php echo e($tokenRecord['username']); ?></strong><br>
                Das Passwort muss mindestens 4 Zeichen lang sein und mindestens einen Kleinbuchstaben sowie eine Zahl enthalten.
            </p>

            <form method="post" action="<?php echo e(BASE_URL); ?>/set_password.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="code" value="<?php echo e($code); ?>">

                <label for="password">Neues Passwort</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">

                <label for="password_repeat">Passwort wiederholen</label>
                <input type="password" id="password_repeat" name="password_repeat" required autocomplete="new-password">

                <button type="submit" class="btn btn-primary">Passwort speichern</button>
            </form>
        <?php else: ?>
            <p>
                <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/login.php">Zum Login</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
