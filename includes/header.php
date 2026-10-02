<?php

if (!isset($pageTitle)) {
    $pageTitle = 'FitFuerInfo';
}

if (!isset($currentNav)) {
    $currentNav = '';
}

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

$navItems = array(
    'dashboard' => array('url' => '/dashboard.php', 'label' => 'Dashboard'),
    'courses' => array('url' => '/courses/index.php', 'label' => 'Kurse'),
    'rooms' => array('url' => '/rooms/index.php', 'label' => 'Räume'),
    'bookings' => array('url' => '/bookings/index.php', 'label' => 'Buchungen')
);

if (isAdmin()) {
    $navItems['users'] = array('url' => '/users/index.php', 'label' => 'Mitarbeiter');
    $navItems['software'] = array('url' => '/software/index.php', 'label' => 'Software');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | FitFuerInfo</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css?v=20261002-3">
</head>
<body>
    <a class="skip-link" href="#main">Zum Inhalt</a>
    <header class="site-header">
        <div class="header-inner">
            <div class="brand">
                <a href="<?php echo e(BASE_URL); ?>/dashboard.php">FitFuerInfo</a>
                <span>Raum- und Kursverwaltung</span>
            </div>
            <nav class="site-nav" aria-label="Hauptnavigation">
                <?php foreach ($navItems as $navKey => $navItem): ?>
                    <a
                        href="<?php echo e(BASE_URL . $navItem['url']); ?>"
                        class="<?php echo $currentNav === $navKey ? 'active' : ''; ?>"
                    >
                        <?php echo e($navItem['label']); ?>
                    </a>
                <?php endforeach; ?>
                <form method="post" action="<?php echo e(BASE_URL); ?>/logout.php" class="nav-logout">
                    <?php echo csrfField(); ?>
                    <button type="submit">Abmelden</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="page" id="main">
        <div class="container">
            <?php if ($flashSuccess !== ''): ?>
                <div class="alert alert-success"><?php echo e($flashSuccess); ?></div>
            <?php endif; ?>

            <?php if ($flashError !== ''): ?>
                <div class="alert alert-error"><?php echo e($flashError); ?></div>
            <?php endif; ?>
