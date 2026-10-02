<?php

require_once dirname(__FILE__) . '/includes/init.php';

$message = '';
$error = '';
$adminExists = false;

try {
    $stmt = $pdo->prepare(
        "SELECT user_id
         FROM users
         WHERE role = 'admin'
         LIMIT 1"
    );
    $stmt->execute();
    $adminExists = (bool) $stmt->fetch();
} catch (PDOException $e) {
    error_log('setup_admin Prüfung fehlgeschlagen: ' . $e->getMessage());
    $error = 'Die Datenbank ist noch nicht eingerichtet. Bitte zuerst sql/database.sql importieren.';
}

$username = '';
$firstName = '';
$lastName = '';

if ($adminExists) {
    $error = 'Es existiert bereits ein Systemverwalter.';
} elseif ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $username = postValue('username', '');
        $firstName = postValue('first_name', '');
        $lastName = postValue('last_name', '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $passwordError = validatePassword($password);

        if ($username === '' || $firstName === '' || $lastName === '' || $password === '') {
            $error = 'Bitte alle Felder ausfüllen.';
        } elseif ($passwordError !== '') {
            $error = $passwordError;
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users
                    (username, first_name, last_name, password_hash, role, active)
                 VALUES
                    (?, ?, ?, ?, 'admin', 1)"
            );

            try {
                $stmt->execute(
                    array($username, $firstName, $lastName, $passwordHash)
                );
                $message = 'Systemverwalter wurde erfolgreich angelegt. Bitte setup_admin.php jetzt löschen oder umbenennen.';
            } catch (PDOException $e) {
                error_log('setup_admin Insert fehlgeschlagen: ' . $e->getMessage());
                $error = 'Der Systemverwalter konnte nicht angelegt werden. Möglicherweise ist der Benutzername bereits vergeben.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einrichtung | FitFuerInfo</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>FitFuerInfo</h1>
        <p class="auth-subtitle">Erstmalige Einrichtung</p>
        <h2>Systemverwalter anlegen</h2>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
            <p><a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/login.php">Zum Login</a></p>
        <?php elseif (!$adminExists && strpos($error, 'Datenbank') === false): ?>
            <p class="hint">
                Das Passwort muss mindestens 4 Zeichen lang sein und
                mindestens einen Kleinbuchstaben sowie eine Zahl enthalten.
            </p>

            <form method="post" action="<?php echo e(BASE_URL); ?>/setup_admin.php">
                <?php echo csrfField(); ?>

                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" value="<?php echo e($username); ?>" required>

                <label for="first_name">Vorname</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo e($firstName); ?>" required>

                <label for="last_name">Nachname</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo e($lastName); ?>" required>

                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="btn btn-primary">Systemverwalter anlegen</button>
            </form>
        <?php elseif ($adminExists): ?>
            <p><a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/login.php">Zum Login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
