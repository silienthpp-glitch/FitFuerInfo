<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Aktivierungscode';
$currentNav = 'users';

$activation = takeOneTimeActivation();

if (!$activation) {
    setFlash('error', 'Der Aktivierungscode wird nur einmal angezeigt. Bitte bei Bedarf einen neuen Code erzeugen.');
    redirect('/users/index.php');
}

$plainToken = $activation['token'];
$activationLink = absoluteUrl(activationUrl($plainToken));

require_once APP_ROOT . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Aktivierungscode</h1>
        <p class="muted">
            Geben Sie diesen Link nur an
            <strong><?php echo e($activation['username']); ?></strong>
            weiter. Der Code wird nicht gespeichert und nur dieses eine Mal angezeigt.
        </p>
    </div>
</div>

<div class="card">
    <p>Der Mitarbeiter legt das Passwort selbst unter dieser Adresse fest:</p>
    <p class="token-box"><?php echo e($activationLink); ?></p>
    <p class="hint">Der Code ist 7 Tage gültig und nach der Passwortvergabe ungültig.</p>

    <div class="form-actions">
        <a class="btn btn-primary" href="<?php echo e(BASE_URL); ?>/users/index.php">Zur Mitarbeiterliste</a>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
