<?php

$host = 'localhost';
$dbname = 'fitfuerinfo_db';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4',
        $dbuser,
        $dbpass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        )
    );
} catch (PDOException $e) {
    error_log('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
    die('Die Verbindung zur Datenbank konnte nicht hergestellt werden.');
}
