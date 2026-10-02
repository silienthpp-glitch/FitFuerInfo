# FitFuerInfo

Lokale Webanwendung zur Verwaltung von Mitarbeitern, Kursprofilen, Räumen und Raumbuchungen.

Das Projekt ist für **XAMPP 5.6.36 / PHP 5.6** geschrieben. Es verwendet nur HTML, CSS, PHP, JavaScript und MySQL/PDO. Es gibt keine Frameworks, kein Composer und keine CDN-Abhängigkeiten.

## 1. Voraussetzungen

- XAMPP mit Apache und MySQL/MariaDB
- PHP 5.6 oder kompatibel (z. B. XAMPP 5.6.36)
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

Im Browser öffnen:

```text
http://localhost/fitfuerinfo/setup_admin.php
```

## 7. Administrator-Daten eingeben

Das Passwort muss:

- mindestens 4 Zeichen lang sein
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