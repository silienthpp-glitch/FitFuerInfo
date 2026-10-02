<?php

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)));
}

// Entwicklungsphase: Fehler sichtbar machen.
// Für den Produktivbetrieb display_errors auf 0 setzen.
error_reporting(E_ALL);
ini_set('display_errors', '1');

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
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
