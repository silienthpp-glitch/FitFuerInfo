<?php

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)));
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function ($error) {
    error_log((string) $error);
    http_response_code(500);
    echo 'Die Anfrage konnte nicht verarbeitet werden. Bitte versuchen Sie es erneut.';
});
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('Cache-Control: no-store');

date_default_timezone_set('Europe/Berlin');

if (!defined('BASE_URL')) {
    $documentRoot = '';
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
        $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    }

    $appRootNormalized = str_replace('\\', '/', APP_ROOT);

    if ($documentRoot !== '' && strpos($appRootNormalized, $documentRoot) === 0) {
        $detectedBase = substr($appRootNormalized, strlen($documentRoot));
        if ($detectedBase === false) {
            $detectedBase = '/fitfuerinfo';
        }
        define('BASE_URL', $detectedBase);
    } else {
        define('BASE_URL', '/fitfuerinfo');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax'));
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
    }
    $sessionPath = getenv('FITFUERINFO_SESSION_PATH');
    if ($sessionPath) { session_save_path($sessionPath); }
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    $idle = isset($_SESSION['last_seen']) ? time() - $_SESSION['last_seen'] : 1801;
    $age = isset($_SESSION['created_at']) ? time() - $_SESSION['created_at'] : 28801;
    if ($idle > 1800 || $age > 28800) {
        $_SESSION = array();
        session_regenerate_id(true);
    } else {
        $_SESSION['last_seen'] = time();
    }
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';

// Serialize writes across this application's PHP workers. All mutations use POST.
// The database-scoped lock covers validation and persistence, including token use.
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $writeLock = $pdo->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':write'), 5)")->fetchColumn();
    if ((int) $writeLock !== 1) {
        http_response_code(503);
        header('Retry-After: 5');
        exit('Die Anwendung ist gerade ausgelastet. Bitte versuchen Sie es in wenigen Sekunden erneut.');
    }
    register_shutdown_function(function () use ($pdo) {
        try {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $pdo->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':write'))");
        } catch (Exception $error) { error_log('Freigabe der Schreibsperre fehlgeschlagen.'); }
    });
}
