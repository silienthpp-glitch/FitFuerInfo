# FitFuerInfo Optimierungsbericht

Stand: 02.10.2026 · Hopkins Colby und Julian Diaconu · RWTH Aachen

## Umgesetztes Design

Rote Akzente (#b42336), helle Flächen und eine einheitliche Schriftfamilie ersetzen die blaue Gestaltung. Abstände folgen einer gemeinsamen Skala von 4, 8, 12, 16, 24, 32 und 48 Pixeln. Überschriften, Formulare, Tabellen, Statusanzeigen und Schaltflächen verwenden festgelegte Größen. Auf kleinen Bildschirmen stehen die Kennzahlen in zwei Spalten. Tabellen sind innerhalb ihrer Karte horizontal scrollbar. Sichtbare Tastatur-Fokusmarkierungen, ein Sprunglink zum Inhalt und die Berücksichtigung reduzierter Bewegung verbessern die Bedienbarkeit.

## Behobene Schwachstellen

| Bereich | Änderung |
| --- | --- |
| Passwörter | Neue Passwörter benötigen mindestens 12 Zeichen, Kleinbuchstaben und eine Zahl; maximal 72 Bytes verhindern unbemerkte bcrypt-Abschneidung. Bestehende Passwörter werden nicht automatisch geändert. |
| Login-Versuche | Datenbankgestützte Begrenzung pro Benutzerkennung und IP-Adresse. Nach 10 beziehungsweise 40 Fehlversuchen innerhalb von 15 Minuten werden weitere Versuche mit HTTP 429 abgelehnt. Kennungen werden gehasht gespeichert. |
| Aktivierung und CSRF | Ausschließlich kryptografisch geeignete Zufallswerte; unsicherer Fallback entfernt. Deaktivierte Konten können keine Aktivierungscodes einlösen. |
| Parallele Änderungen | Datenbankweite Schreibsperre vom Beginn einer POST-Anfrage bis zu ihrem Abschluss verhindert konkurrierende Prüfung und Speicherung innerhalb dieser Anwendung. Zeitüberschreitung führt zu HTTP 503 statt unkontrollierter Fortsetzung. |
| Bestehende Buchungen | Änderungen an Räumen und Kursen validieren zukünftige und laufende Buchungen innerhalb der Transaktion erneut. Ungültige Änderungen werden zurückgerollt. |
| Sitzungen | HttpOnly, SameSite=Lax, Secure bei HTTPS und strikte Sitzungskennung; Ablauf nach 30 Minuten Inaktivität oder acht Stunden Gesamtdauer. Rollen und Kontostatus werden erneut geladen; Passwortänderungen entwerten bisherige Sitzungen. |
| Abmeldung | Nur POST mit gültigem CSRF-Token; GET liefert 405. |
| Ersteinrichtung | Standardmäßig gesperrt; Freigabe nur über Serverkonfiguration und lokalen Aufruf. |
| Fehler und Eingaben | Interne Fehlermeldungen werden protokolliert, nicht ausgegeben. Manipulierte Array-Parameter werden kontrolliert abgefangen. |
| Aktivierungslinks | Keine Verwendung des untrusted Host-Headers; explizit konfigurierter Ursprung oder relativer Link. |
| Browser und Server | CSP, Schutz vor Einbettung, no-referrer, nosniff und no-store für dynamische Seiten. Apache-Regeln sperren interne Verzeichnisse und deaktivieren Verzeichnislisten. |

## Ladezeiten und Datenbankzugriffe

- Berechtigungen werden je Anfrage gesammelt geladen: 200 Aufrufe der beiden Rechtefunktionen benötigten im Test zwei fachliche SELECT-Abfragen. Der vorherige Code erzeugte dafür 200 einzelne SELECT-Abfragen. Der Zähler-Test enthält zusätzlich eine eigene Messabfrage.
- Die vier Dashboard-Kennzahlen werden mit einer gemeinsamen Datenbankabfrage statt vier separaten Abfragen geladen. Die eigentliche Anzahl der gezählten Datensätze ändert sich nicht.
- JavaScript wird verzögert geladen. CSS und JavaScript besitzen Versionsparameter, damit Änderungen trotz Browsercache sichtbar werden.
- Apache kann statische Dateien sieben Tage zwischenspeichern und Textantworten komprimieren. Diese Regeln hängen von den aktivierten Servermodulen ab; ihre Wirkung konnte in der laufenden PHP-Entwicklungsserver-Vorschau nicht gemessen werden.
- Keine neuen Frameworks, Webfonts oder externen CDN-Abhängigkeiten. Es wird kein unbelegter prozentualer Ladezeitgewinn behauptet.

## Prüfungen

Prüfumgebung: Windows, PHP 8.2.12, MariaDB 10.4.32, zwei getrennte PHP-Prozesse auf localhost, ausschließlich isolierte Beispieldaten.

- 24 Funktionsprüfungen bestanden, einschließlich Passwortgrenzen, manipulierter Parameter, Zufallswerte und CSRF.
- 27 HTTP- und Datenbankprüfungen bestanden: geschützte Seiten, Konto- und Rollenprüfungen, Login-Begrenzung, Abmeldung, Session-Cookies und Sicherheitsheader.
- Zwei gleichzeitige Buchungen desselben Zeitfensters auf zwei PHP-Prozessen erzeugen genau eine Buchung.
- Zwei gleichzeitige Einlösungen desselben Aktivierungscodes erlauben genau eine Passwortänderung.
- Unpassende Raumverkleinerung und Kursvergrößerung werden abgelehnt und zurückgerollt.
- Dashboard im Desktopformat und bei 375 Pixeln Breite visuell geprüft. Weitere Hauptseiten wurden per HTTP auf erfolgreiche Ausgabe geprüft.

Das ist eine gezielte technische Prüfung, kein vollständiger Penetrationstest. Apache-Regeln, TLS, Produktivkonfiguration und ein Test unter PHP 5.6 wurden nicht verifiziert. Die ursprüngliche PDF-Dokumentation bleibt als historischer Stand erhalten; dieser Bericht dokumentiert die Änderungen.

## Weitere Optimierungsvorschläge

| Priorität | Vorschlag | Nutzen |
| --- | --- | --- |
| Hoch | Aktuell unterstützte PHP-Version mit neuestem Patchstand, aktuelle Datenbank und HTTPS einsetzen; kein öffentliches XAMPP-Testsystem | Betriebssystem- und Laufzeitlücken werden nicht durch Anwendungscode behoben. PHP 5.6 ist nicht mehr unterstützt. |
| Hoch | Eigenes Datenbankkonto mit minimalen Rechten und geschützte Backups samt Wiederherstellungstest | Begrenzter Schadensumfang und verlässliche Wiederherstellung. |
| Hoch | Bestehende schwache Passwörter neu vergeben und optional MFA für Administratoren | Die neue Mindestregel wirkt erst bei der nächsten Passwortvergabe. |
| Mittel | Seitennavigation und serverseitige Suche für Kurs-, Raum-, Mitarbeiter- und Buchungslisten | Die Listen laden weiterhin alle passenden Einträge; bei großen Beständen steigen Speicherbedarf und Antwortzeit. |
| Mittel | Auditprotokoll für Buchungen, Stammdaten und Rechteänderungen | Änderungen lassen sich später nachvollziehen. |
| Mittel | Feinere, konsistent geordnete Transaktionssperren statt globaler Schreibsperre | Höherer Durchsatz bei vielen gleichzeitigen Nutzern. Die derzeitige Lösung priorisiert Konsistenz für die kleine Anwendung. |
| Mittel | Automatisierte Integrationstests mit eigener Testdatenbank in CI | Regressionen bei Updates früher erkennen; die vorhandenen PHP-Funktionstests können bereits automatisiert laufen. |
| Niedrig | Kalenderansicht, Export und barrierefreie Tabellenbeschriftungen | Komfort und Zugänglichkeit verbessern. |

## Installation des Updates

Vor dem Codewechsel die Datenbank sichern und `sql/update_security.sql` importieren. Anschließend die Projektdateien einschließlich .htaccess aktualisieren; die lokale Datenbankkonfiguration beibehalten. Servereinstellungen und neue Umgebungsvariablen sind in README.md beschrieben. Bestehende Sitzungen müssen sich neu anmelden.

Quelle zur Laufzeitpflege: [PHP Supported Versions](https://www.php.net/supported-versions.php) und [PHP Unsupported Branches](https://www.php.net/eol.php).
