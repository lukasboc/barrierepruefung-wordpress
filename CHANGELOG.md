# Änderungsverlauf

Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung [Semantic Versioning](https://semver.org/lang/de/).

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
