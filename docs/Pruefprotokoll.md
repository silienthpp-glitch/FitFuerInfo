# Prüfprotokoll vom 02.10.2026

Prüfumgebung: PHP 8.2.12 CLI unter Windows.

## Syntax

Alle 28 ursprünglichen PHP-Dateien ohne Syntaxfehler geprüft.

## Hilfsfunktionen

Aufruf: `php tests/functions_test.php`. Keine Datenbank erforderlich.

```text
PASS Schaltjahr gueltig
PASS Ungueltiger Kalendertag
PASS Datumsformat strikt
PASS Mitternacht gueltig
PASS Letzte Sekunde gueltig
PASS Stunde 24 ungueltig
PASS Minute 60 ungueltig
PASS Zeitnormalisierung
PASS Kurzes Passwort abgelehnt
PASS Passwort ohne Zahl abgelehnt
PASS Passwort ohne Kleinbuchstaben abgelehnt
PASS Bestehende Mindestregel akzeptiert
PASS HTML Ausgabe maskiert
PASS Tokenhash SHA256
PASS Leeres Passwort erkannt
PASS Passendes CSRF Token
PASS Falsches CSRF Token
PASS Eigene Buchung loeschbar
PASS Fremde Buchung geschuetzt
PASS Admin darf fremde Buchung loeschen
20 Tests, 20 bestanden
```

## Noch offen

PHP-5.6-Kompatibilität, Browser- und Datenbankintegration, Parallelzugriffe und fachliche Abnahme. Die bestandenen isolierten Tests ersetzen diese Prüfungen nicht.
