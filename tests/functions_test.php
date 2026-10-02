<?php
require dirname(__DIR__) . '/includes/functions.php';
$checks = array(
    'Schaltjahr gueltig' => isValidDate('2024-02-29'),
    'Ungueltiger Kalendertag' => !isValidDate('2025-02-29'),
    'Datumsformat strikt' => !isValidDate('02.10.2026'),
    'Mitternacht gueltig' => isValidTime('00:00'),
    'Letzte Sekunde gueltig' => isValidTime('23:59:59'),
    'Stunde 24 ungueltig' => !isValidTime('24:00'),
    'Minute 60 ungueltig' => !isValidTime('12:60'),
    'Zeitnormalisierung' => normalizeTime('12:30') === '12:30:00',
    'Kurzes Passwort abgelehnt' => validatePassword('a1') !== '',
    'Passwort ohne Zahl abgelehnt' => validatePassword('abcd') !== '',
    'Passwort ohne Kleinbuchstaben abgelehnt' => validatePassword('ABC1') !== '',
    'Bestehende Mindestregel akzeptiert' => validatePassword('abc1') === '',
    'HTML Ausgabe maskiert' => e('<script>') === '&lt;script&gt;',
    'Tokenhash SHA256' => strlen(hashActivationToken('test')) === 64,
    'Leeres Passwort erkannt' => !userHasPasswordSet(array('password_hash' => null))
);
$_SESSION = array('user_id' => 7, 'role' => 'employee', 'csrf_token' => 'session-test');
$_POST = array('csrf_token' => 'session-test');
$checks['Passendes CSRF Token'] = isValidCsrf();
$_POST['csrf_token'] = 'wrong';
$checks['Falsches CSRF Token'] = !isValidCsrf();
$checks['Eigene Buchung loeschbar'] = canDeleteBooking(array('created_by' => 7));
$checks['Fremde Buchung geschuetzt'] = !canDeleteBooking(array('created_by' => 8));
$_SESSION['role'] = 'admin';
$checks['Admin darf fremde Buchung loeschen'] = canDeleteBooking(array('created_by' => 8));
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL; }
echo count($checks) . ' Tests, ' . count(array_filter($checks)) . ' bestanden' . PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
