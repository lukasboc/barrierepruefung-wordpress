# Änderungsverlauf

Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung [Semantic Versioning](https://semver.org/lang/de/).

## [Unveröffentlicht]

### Geändert

- `readme.txt` und das Feld `Description:` im Plugin-Kopf sind jetzt englisch. Das Plugins-Team
  von wordpress.org verlangt das seit dem 28.07.2025; ohne diesen Schritt gäbe es keine
  Aufnahme ins Verzeichnis.
- Die Quellsprache der sichtbaren Zeichenketten ist von Deutsch auf Englisch gedreht. Für
  GlotPress sind die msgids die englischen Originale — mit deutschen Sätzen darin hätten
  Übersetzerinnen auf translate.wordpress.org Deutsch nach Deutsch übersetzen müssen. Deutsch
  ist damit eine Übersetzung wie jede andere und steht in `languages/a11y-checker-de_DE.po`;
  `languages/a11y-checker-en_US.po` ist entfallen. Eine deutsche Installation zeigt
  unverändert denselben Text wie bisher.
- `Tested up to:` steht auf 7.1, und der Plugin-Kopf nennt jetzt `Domain Path: /languages`.

### Bekannte Einschränkung

- Mitgeliefert ist nur die Sprachfassung `de_DE`. Installationen mit `de_AT`, `de_CH` oder
  `de_DE_formal` laufen bis zur Aufnahme ins Verzeichnis auf Englisch — WordPress fällt
  zwischen Locales nicht zurück. Mit der Aufnahme liefert translate.wordpress.org die
  Sprachpakete für diese Locales nach.

## [0.3.0]

### Hinzugefügt

- *Werkzeuge → Barrierefreiheit* zeigt die offenen Befunde der letzten Prüfung: je Regel der
  Schweregrad, das Erfolgskriterium, die Zahl der Fundstellen, ein Hinweis zur Behebung und
  die betroffenen Seiten. Damit ist die Seite eine Arbeitsliste — beheben lässt sich in
  WordPress, und die Schaltfläche „Jetzt prüfen" schließt den Durchgang ab. Für alle
  Einzelheiten führt ein Verweis in den vollständigen Bericht.
- Die Seite nennt das Seitenkontingent des laufenden Abrechnungszeitraums: verbrauchte und
  enthaltene Seiten, verbleibender Rest und das Ende des Zeitraums.
- Eine Schaltfläche „Status aktualisieren" holt Kontingent und Befunde neu. Sie ersetzt
  bewusst ein selbsttätiges Neuladen der Seite: ein automatischer Kontextwechsel wäre für
  Screenreader-Nutzende störend (WCAG 2.2.2/3.2.5). Das Plugin bringt weiterhin kein
  JavaScript mit.
- Läuft gerade eine Prüfung, sagt die Seite das, statt ein veraltetes Ergebnis als aktuelles
  auszugeben.

### Geändert

- Zustand und Befunde liegen fünf Minuten im Zwischenspeicher (`a11y_checker_status`), damit
  ein Blick ins Backend nicht bei jedem Aufruf zwei Anfragen an den Dienst auslöst. Das
  Auslösen einer Prüfung und die Schaltfläche „Status aktualisieren" verwerfen ihn; ein
  fehlgeschlagener Abruf wird gar nicht erst abgelegt.
- Lässt sich die Website nicht abrufen, steht jetzt der Grund des Dienstes dabei statt nur
  der allgemeine Hinweis.

### Voraussetzung

- Benötigt die Felder `quota`, `latest_scan` und `running_scan` in `GET /sites/{id}` sowie
  `remediation` und `page_urls` in `GET /scans/{id}/findings`. Ältere Stände des Dienstes
  liefern sie nicht; die neuen Abschnitte bleiben dann leer, alles Übrige arbeitet weiter.

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
