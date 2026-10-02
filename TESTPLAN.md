# Testplan FitFuerInfo

Diese Tests können lokal unter `http://localhost/fitfuerinfo/` durchgeführt werden.

Voraussetzung: Datenbank ist importiert, ein Administrator existiert, mindestens ein weiterer Mitarbeiter ist angelegt.

Empfohlene Testdaten:

- Admin: darf alles
- Mitarbeiter A: legt Kurse an und bucht
- Mitarbeiter B: darf fremde Kurse/Buchungen nicht ändern
- Raum klein: z. B. 10 Arbeitsplätze, ohne Wireshark
- Raum groß: z. B. 20 Arbeitsplätze, mit Wireshark
- Kurs: z. B. 15 Teilnehmer, benötigt Wireshark

---

## LOGIN

### richtiger Benutzer + richtiges Passwort

1. `login.php` öffnen.
2. Gültigen Benutzernamen und gültiges Passwort eingeben.
3. **Erwartung:** Weiterleitung zum Dashboard, Begrüßung mit Benutzername.

### falsches Passwort

1. Gültigen Benutzernamen und ein falsches Passwort eingeben.
2. **Erwartung:** Meldung „Benutzername oder Passwort ist nicht korrekt.“
3. Keine Session, kein Zugang zum Dashboard.

### deaktivierter Benutzer

1. Als Admin einen Mitarbeiter deaktivieren.
2. Logout.
3. Mit dem deaktivierten Konto anmelden.
4. **Erwartung:** Dieselbe allgemeine Fehlermeldung, kein Login.

### Zugriff ohne Login

1. Abmelden.
2. Direkt aufrufen: `dashboard.php`, `users/index.php`, `courses/index.php`, `rooms/index.php`, `bookings/index.php`.
3. **Erwartung:** Weiterleitung zu `login.php`.

### Mitarbeiter legt eigenes Passwort fest

1. Als Admin einen Mitarbeiter **ohne Passwortfeld** anlegen.
2. Den einmal angezeigten Aktivierungslink notieren.
3. Abmelden und `set_password.php` mit diesem Code öffnen.
4. Ein gültiges Passwort setzen (mind. 4 Zeichen, Kleinbuchstabe, Zahl).
5. **Erwartung:** Login mit diesem Passwort funktioniert. Derselbe Code ist danach ungültig.

### Admin kennt kein Mitarbeiterpasswort

1. In `users/create.php` und `users/edit.php` nach einem Passwortfeld suchen.
2. **Erwartung:** Es gibt keines. Es kann nur ein neuer Aktivierungscode erzeugt werden.

### Konto ohne gesetztes Passwort

1. Einen neu angelegten Mitarbeiter vor der Passwortvergabe am Login versuchen.
2. **Erwartung:** Kein Login, allgemeine Fehlermeldung.

---

## KURS

### Kurs anlegen

1. Als Mitarbeiter A anmelden.
2. Kurs anlegen mit Name, Beschreibung, Teilnehmerzahl und Software.
3. **Erwartung:** Kurs erscheint in der Liste. Mitarbeiter A ist Eigentümer.

### eigener Kurs bearbeiten

1. Als Mitarbeiter A den eigenen Kurs öffnen.
2. Name oder Software ändern und speichern.
3. **Erwartung:** Änderung wird gespeichert.

### fremder Kurs nicht bearbeitbar

1. Als Mitarbeiter B denselben Kurs aufrufen.
2. **Erwartung:** Kein Bearbeiten-/Löschen-Button.
3. Direkter Aufruf von `courses/edit.php?id=...` wird serverseitig abgelehnt.

### Admin darf jeden Kurs bearbeiten

1. Als Administrator denselben Kurs bearbeiten.
2. **Erwartung:** Speichern ist möglich.

### weiterer Eigentümer funktioniert

1. Als Admin im Kurs einen weiteren Mitarbeiter als Eigentümer setzen.
2. Mit diesem Mitarbeiter anmelden.
3. **Erwartung:** Der Kurs kann jetzt auch von diesem Mitarbeiter bearbeitet werden.

### Kurs mit zukünftiger Buchung nicht löschbar

1. Für den Kurs eine zukünftige Raumbuchung anlegen.
2. Kurs löschen oder deaktivieren versuchen.
3. **Erwartung:** Verständliche Fehlermeldung, Kurs bleibt erhalten.

### Kurs nur mit vergangenen Buchungen

1. Einen Kurs verwenden, der ausschließlich vergangene Buchungen hat.
2. Löschen versuchen.
3. **Erwartung:** Kein hartes Löschen, weil die Buchungshistorie über den Fremdschlüssel erhalten bleiben muss. Der Kurs wird deaktiviert.

---

## RAUM

### normaler Mitarbeiter kann keinen Raum erstellen

1. Als Mitarbeiter A `rooms/create.php` aufrufen.
2. **Erwartung:** Kein Zugriff. Der Button „Raum anlegen“ ist nicht sichtbar, der direkte Aufruf wird serverseitig blockiert.

### Admin kann Raum erstellen

1. Als Admin einen Raum mit Name, Arbeitsplätzen, Beschreibung und Software anlegen.
2. **Erwartung:** Raum erscheint in der Liste.

### berechtigter Raumeditor kann Raum bearbeiten

1. Als Admin Mitarbeiter A als Bearbeiter für einen Raum eintragen.
2. Als Mitarbeiter A den Raum bearbeiten.
3. **Erwartung:** Speichern ist möglich.

### unberechtigter Mitarbeiter nicht

1. Als Mitarbeiter B denselben Raum bearbeiten.
2. **Erwartung:** Kein Bearbeiten-Button, direkter Aufruf von `rooms/edit.php?id=...` wird abgelehnt.

---

## BUCHUNG

### gültige Buchung

1. Kurs und passenden Raum wählen (genug Arbeitsplätze, alle Software vorhanden, Zeitraum frei).
2. Speichern.
3. **Erwartung:** Buchung erscheint in der Übersicht mit Datum, Zeit, Raum, Kurs und Ersteller.

### Raum zu klein

1. Kurs mit 15 Teilnehmern in einem Raum mit 10 Arbeitsplätzen buchen.
2. **Erwartung:** „Der Raum besitzt nur 10 von 15 benötigten Arbeitsplätzen.“

### benötigte Software fehlt

1. Kurs mit Wireshark in einem Raum ohne Wireshark buchen.
2. **Erwartung:** „Im Raum fehlt die Software Wireshark.“

### Zeitüberschneidung

1. Denselben Raum am selben Tag in einem überschneidenden Zeitraum buchen.
2. **Erwartung:** „Der Raum ist zu diesem Zeitpunkt bereits gebucht.“
3. Direkt anschließende Zeiten (z. B. 10:00–12:00 und 12:00–14:00) dürfen erlaubt sein.

### eigene Buchung löschen

1. Als Ersteller die eigene Buchung löschen.
2. **Erwartung:** Buchung wird entfernt.

### fremde Buchung nicht löschen

1. Als anderer Mitarbeiter dieselbe Buchung löschen.
2. **Erwartung:** Kein Löschen-Button, direkter Aufruf von `bookings/delete.php?id=...` wird abgelehnt.

### Admin darf jede Buchung löschen

1. Als Administrator eine fremde Buchung löschen.
2. **Erwartung:** Löschen ist möglich.
