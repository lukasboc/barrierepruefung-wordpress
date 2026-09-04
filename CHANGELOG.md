# Änderungsverlauf

Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung [Semantic Versioning](https://semver.org/lang/de/).

## [0.2.1]

### Hinzugefügt

- *Werkzeuge → Barrierefreiheit* zeigt die eingetragene Verbindung und lässt sie wieder
  trennen. Bisher blendete das Plugin das Formular aus, sobald Token und Kennung einmal
  gespeichert waren — auch wenn sie falsch waren; korrigieren ließ sich das nur über WP-CLI
  oder die Datenbank. Getrennt wird alles entfernt, was zum Konto gehört: Token,
  Website-Kennung, Verifikationsnachweis und die zuletzt geholte Fassung der Erklärung.
  Im Konto bleibt das Token bestehen und wird dort widerrufen.
- Die Adresse des Dienstes lässt sich einzeln auf den Standardwert zurücksetzen, und das
  Formular schlägt jetzt die zuletzt eingetragene Adresse vor statt stets den Standard.

### Behoben

- Eine Adresse, die `esc_url_raw()` verworfen hat, blieb als leere Option stehen. Der
  Vorgabewert von `get_option()` greift dann nicht mehr, und jede Anfrage ging danach an
  einen relativen Pfad. Jetzt gilt in diesem Fall wieder der Standardwert.

## [0.2.0]

### Behoben

- Die Überschriftenebene wird jetzt an der obersten tatsächlich ausgegebenen Überschrift
  gemessen. Bisher blieb bei der Voreinstellung die h1 der Erklärung eine h1 — mitten in einer
  Seite ist das selbst ein Verstoß gegen WCAG 1.3.1 —, und ein mit `teil="maengel"` oder
  `teil="kontakt"` eingebundener Abschnitt kam eine Ebene zu tief heraus.

  Wer die Ebenen bisher mit `ueberschrift="3"` von Hand ausgeglichen hat, stellt jetzt auf
  `ueberschrift="2"` um (oder lässt das Attribut weg).
- Die Rückmeldung im Backend wurde doppelt URL-dekodiert, wodurch eine Meldung mit einer
  wörtlichen Prozent-Sequenz (z. B. `%41`) fälschlich als Zeichen ankam.

## [0.1.0]

### Hinzugefügt

- Erste Fassung: Verbindung, Domain-Bestätigung, Prüfung anstoßen, Shortcode und Block.
- Deutsche und englische Sprachfassung, vollständige Deinstallation.
