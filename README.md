# FitFuerInfo

Lokale Webanwendung zur Verwaltung von Mitarbeitern, Kursprofilen, Räumen und Raumbuchungen.

Das Projekt entstand für **XAMPP 5.6.36 / PHP 5.6**. Der überarbeitete Stand wurde mit **PHP 8.2.12** geprüft. PHP 5.6 ist nicht mehr unterstützt; für den Betrieb ist eine gepflegte PHP-Version mit aktuellen Sicherheitsupdates erforderlich. Es verwendet nur HTML, CSS, PHP, JavaScript und MySQL/PDO. Es gibt keine Frameworks, kein Composer und keine CDN-Abhängigkeiten.

## 1. Voraussetzungen

- XAMPP mit Apache und MySQL/MariaDB
- Eine aktuell unterstützte PHP-Version mit PDO MySQL und sicherer Zufallsquelle; Apache 2.4 für die mitgelieferten .htaccess-Regeln
- Webbrowser

## 2. XAMPP starten

1. XAMPP Control Panel öffnen.
2. **Apache** starten.
3. **MySQL** starten.

## 3. Projekt ablegen

Das Projektverzeichnis muss hier liegen:

```text
C:\xampp\htdocs\fitfuerinfo
```

## 4. Datenbank importieren

1. Im Browser [phpMyAdmin](http://localhost/phpmyadmin) öffnen.
2. Den Reiter **Importieren** wählen.
3. Die Datei `sql/database.sql` auswählen und importieren.

Dadurch wird die Datenbank `fitfuerinfo_db` angelegt. Zusätzlich werden Beispiel-Softwareeinträge (z. B. Wireshark) importiert.

Wenn die Datenbank schon existiert, zusätzlich `sql/update_password_tokens.sql` importieren.

## 5. Datenbankverbindung einrichten

1. Die Datei `config/database.example.php` nach `config/database.php` kopieren.
2. Bei Bedarf die Zugangsdaten anpassen.

Standardwerte für XAMPP:

- Host: `localhost`
- Datenbank: `fitfuerinfo_db`
- Benutzer: `root`
- Passwort: leer

`config/database.php` wird von Git ignoriert, weil dort Zugangsdaten stehen können.

## 6. Ersten Administrator anlegen

Die Ersteinrichtung ist standardmäßig gesperrt. Auf dem lokalen Server vorübergehend die Umgebungsvariable `FITFUERINFO_ALLOW_SETUP=1` setzen (bei Apache beispielsweise `SetEnv FITFUERINFO_ALLOW_SETUP 1` in der lokalen Serverkonfiguration). Der Aufruf muss von `127.0.0.1` oder `::1` erfolgen. Danach die Freigabe wieder entfernen. Bereits vorhandene Konten benötigen diesen Schritt nicht.

Im Browser öffnen:

```text
http://localhost/fitfuerinfo/setup_admin.php
```

## 7. Administrator-Daten eingeben

Das Passwort muss:

- mindestens 12 Zeichen lang sein und darf höchstens 72 Bytes umfassen
- mindestens einen Kleinbuchstaben enthalten
- mindestens eine Zahl enthalten

Das Passwort wird nur als Hash gespeichert, niemals im Klartext.

## 8. setup_admin.php danach entfernen

Nach der ersten Einrichtung **`setup_admin.php` löschen oder umbenennen**.

Die Datei legt nur den ersten Administrator an. Existiert bereits ein Admin, wird kein zweiter über diese Seite erstellt.

## 9. Anwendung öffnen

```text
http://localhost/fitfuerinfo/
```

Nicht angemeldete Benutzer werden zum Login weitergeleitet. Nach dem Login erscheint das Dashboard.

## Ordnerstruktur

```text
fitfuerinfo/
    assets/css/          gemeinsames Stylesheet
    assets/js/           kleines Bestätigungs-Skript
    bookings/            Raumbuchungen und Raumbelegung
    config/              Datenbankverbindung
    courses/             Kursverwaltung
    includes/            Auth, Hilfsfunktionen, Kopf- und Fußzeile
    rooms/               Raumverwaltung
    software/            zentrale Softwareverwaltung (Admin)
    sql/                 Datenbankskript
    users/               Mitarbeiterverwaltung (Admin)
    dashboard.php        Startseite nach dem Login
    index.php            Weiterleitung Login/Dashboard
    login.php            Anmeldung
    logout.php           Abmeldung
    set_password.php     Passwortvergabe durch den Mitarbeiter
    setup_admin.php      einmalige Erst-Einrichtung
```

## Mitarbeiterpasswörter

Der Administrator legt Mitarbeiter **ohne Passwort** an. Stattdessen erzeugt das System einen einmaligen Aktivierungscode.

- Der Code wird nur als Hash gespeichert.
- Der Klartext-Link wird dem Administrator **einmalig** angezeigt, damit er ihn dem Mitarbeiter geben kann.
- Der Mitarbeiter setzt sein Passwort selbst unter `set_password.php`.
- Danach ist der Code ungültig.
- Der Administrator darf später kein Passwort setzen, sondern höchstens einen neuen Einmalcode erzeugen.

## Rollen kurz erklärt

- **Mitarbeiter** können Kurse ansehen und anlegen, Räume ansehen, Räume buchen und eigene Buchungen löschen.
- **Kurs-Eigentümer** dürfen den eigenen Kurs bearbeiten.
- **Raum-Bearbeiter** dürfen den freigegebenen Raum bearbeiten.
- **Administrator** darf Mitarbeiter, Software, alle Kurse, alle Räume und alle Buchungen verwalten.

## Sicherheit

- PDO mit Prepared Statements
- `password_hash()` / `password_verify()`
- Ausgaben über `htmlspecialchars`
- Session nach Login mit `session_regenerate_id(true)`
- serverseitige Rollen- und Eigentümerprüfung
- CSRF-Token für verändernde Formulare

## Projektdokumentation

- [Projektdokumentation als PDF](docs/FitFuerInfo_Projektdokumentation.pdf)
- [Bearbeitbare Dokumentation](docs/Projektdokumentation.md)
- [Prüfprotokoll vom 02.10.2026](docs/Pruefprotokoll.md)

Projektbearbeitung: Hopkins Colby und Julian Diaconu, RWTH Aachen. Betreuung: Herr Meier. Projektzeitraum: 11.09.2026 bis 16.10.2026.

## Update Design und Sicherheit vom 02.10.2026

1. Vorhandene Datenbank sichern. In einer Testumgebung beginnen.
2. Vor dem Austausch des PHP-Codes `sql/update_security.sql` importieren. Es legt die Tabelle für die Login-Begrenzung an. Ein erneuter Import löscht keine Daten.
3. Projektdateien einschließlich `.htaccess` aktualisieren. Die eigene `config/database.php` beibehalten.
4. Bei Apache `AllowOverride` und die Module `mod_rewrite`, `mod_expires` sowie `mod_deflate` passend aktivieren. Für andere Webserver gleichwertige Regeln einrichten: kein direkter HTTP-Zugriff auf config, includes, sql, tests und versteckte Dateien. Der PHP-Entwicklungsserver wertet .htaccess nicht aus und ist nur für lokale Tests geeignet.
5. `FITFUERINFO_PUBLIC_URL` auf den tatsächlichen Ursprung ohne abschließenden Pfad setzen, beispielsweise `https://kurse.example.org`. Ohne diese Einstellung werden relative Aktivierungslinks ausgegeben; der Host-Header wird nicht vertraut.
6. Optional `FITFUERINFO_SESSION_PATH` auf ein beschreibbares, nicht öffentliches Sitzungsverzeichnis setzen. HTTPS verwenden und Proxy-TLS korrekt konfigurieren; Secure-Cookies werden bei vom Server erkanntem HTTPS gesetzt.
7. Alle Nutzer erneut anmelden. Alte Sitzungen werden durch die neue Passwortbindung ungültig. Bestehende kurze Passwörter bleiben anmeldbar und sollten gezielt neu vergeben werden.

- [Änderungen, Testergebnisse und weitere Vorschläge](docs/Optimierungsbericht.md)
- [Ursprüngliche Projektdokumentation als PDF, vor der Überarbeitung](docs/FitFuerInfo_Projektdokumentation.pdf)
- Lokale Funktionsprüfungen: `php tests/functions_test.php`.

Die Schreibsperre setzt MySQL/MariaDB und nichtpersistente PDO-Verbindungen voraus. Alle schreibenden HTTP-Anfragen werden pro Datenbank serialisiert; nach fünf Sekunden Wartezeit folgt eine kontrollierte 503-Antwort. Für hohe Parallelität ist eine feinere Sperrstrategie vorzusehen. Externe SQL-Schreibzugriffe müssen dieselbe fachliche Konsistenz gewährleisten.
