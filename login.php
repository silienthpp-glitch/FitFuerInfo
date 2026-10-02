<?php

require_once dirname(__FILE__) . '/includes/init.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isValidCsrf()) {
        $error = 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.';
    } else {
        $username = postValue('username', '');
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $error = 'Bitte Benutzername und Passwort eingeben.';
        } elseif (loginRateLimited($pdo, $username)) {
            http_response_code(429);
            header('Retry-After: 900');
            $error = 'Zu viele Anmeldeversuche. Bitte versuchen Sie es später erneut.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT user_id, username, password_hash, role, active
                 FROM users
                 WHERE username = ?
                 LIMIT 1'
            );
            $stmt->execute(array($username));
            $user = $stmt->fetch();

            if (
                $user
                && (int) $user['active'] === 1
                && userHasPasswordSet($user)
                && password_verify($password, $user['password_hash'])
            ) {
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['auth_stamp'] = hash('sha256', $user['password_hash']);
                $_SESSION['created_at'] = time();
                $_SESSION['last_seen'] = time();

                redirect('/dashboard.php');
            }

            recordLoginFailure($pdo, $username);
            $error = 'Benutzername oder Passwort ist nicht korrekt.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung | FitFuerInfo</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css?v=20261002-2">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>FitFuerInfo</h1>
        <p class="auth-subtitle">Raum- und Kursverwaltung</p>
        <h2>Anmeldung</h2>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(BASE_URL); ?>/login.php">
            <?php echo csrfField(); ?>

            <label for="username">Benutzername</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?php echo e($username); ?>"
                required
                autocomplete="username"
            >

            <label for="password">Passwort</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit" class="btn btn-primary">Anmelden</button>
        </form>

        <p class="hint" style="margin-top: 18px;">
            Noch kein Passwort? Verwenden Sie den Aktivierungslink, den Sie vom Administrator erhalten haben.
        </p>
    </div>
</body>
</html>
