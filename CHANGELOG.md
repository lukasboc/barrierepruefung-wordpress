# Änderungsverlauf

Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung [Semantic Versioning](https://semver.org/lang/de/).

## [0.5.0]

### Hinzugefügt

- Die Seite erklärt beim ersten Einrichten, was zu tun ist und warum: dass das Plugin nicht
  selbst prüft, sondern der Zugang zum Dienst ist, dass das Token nur für diese eine Website
  gilt — und dann Schritt für Schritt, wo Konto, Token und Website-Kennung herkommen
  (*Websites → Ihre Website → Einbindung*, Abschnitt *WordPress-Plugin und API*). Vorher stand
  dort ein Satz, der ein Konto voraussetzte, von dem noch niemand wusste.
- Die Anleitung nennt die Adresse dieser Installation. Wird beim Dienst eine andere hinterlegt,
  schlägt später der Domain-Nachweis fehl, ohne dass jemand den Grund sähe.
- Nach der Aktivierung führt ein einmaliger Hinweis zur Seite, und in der Plugin-Liste steht ein
  Verweis *Einrichten*. Eine Aktivierung, die im Nichts endet, ist keine.
- Das Feld für das Token sagt, dass Nummer und senkrechter Strich davor mitgehören. Wer nur den
  Teil dahinter einfügt, bekam bisher eine Fehlermeldung ohne erkennbaren Grund.

### Geändert

- Während einer laufenden Prüfung ist *Nachsehen, ob die Prüfung fertig ist* der hervorgehobene
  Knopf, und der Hinweis auf den laufenden Lauf steht darüber statt darunter. Vorher ging der
  graue *Aktualisieren*-Knopf neben dem blauen *Website prüfen* unter — wer eine Prüfung
  angestoßen hatte, sah nicht, wie er an das Ergebnis kommt.
- *Website prüfen* fällt weg, solange eine Prüfung läuft: ein zweiter Lauf verbrauchte nur
  Kontingent.
- Läuft die erste Prüfung noch, sagt die Befundliste das, statt auf einen Knopf zu verweisen,
  den es in dem Moment gar nicht gibt.
- Die Seite sagt bei laufender Prüfung auch, *warum* sie sich nicht von selbst aktualisiert: ein
  automatisches Neuladen verschöbe den Fokus und unterbräche Screenreader-Nutzende (WCAG 2.2.2).

## [0.4.0]

### Hinzugefügt

- Jede Regel in der Befundliste lässt sich aufklappen: „N Fundstellen anzeigen" holt die
  einzelnen Stellen dieser einen Regel — Seite, Selektor, Messwerte wie „Kontrast 2,41:1 statt
  4,5:1", Farbwerte, der Zustand, in dem das Element sichtbar wird, der Darstellungsfall und
  der HTML-Ausschnitt. Damit ist die Stelle in WordPress auffindbar, ohne den Bericht zu
  öffnen. Geblättert wird in 20er-Schritten.
- Aufklappen und Blättern sind gewöhnliche Verweise. Es kommt weiterhin kein JavaScript ins
  Plugin, und geholt wird nur die Regel, an der gerade gearbeitet wird.

### Geändert

- Die Befunde stehen jetzt **über** dem Kontingent. Sie sind der Grund, warum jemand die Seite
  öffnet; das Kontingent ist die Nebenauskunft.
- Screenshots werden **nicht** ins WordPress-Backend geladen. Die Seite verweist auf den
  vollständigen Bericht und sagt dazu, dass er eine Anmeldung verlangt. Bilder in eine fremde
  Installation zu kopieren hieße, sie dort ohne Aufbewahrungsfrist und ohne Zugriffsprüfung
  liegen zu haben.

### Behoben

- Der Verweis auf den vollständigen Bericht endete für abgemeldete Nutzerinnen in einer nackten
  Fehlerseite („403 This action is unauthorized."). Der Dienst leitet nicht angemeldete
  Besucher jetzt zur Anmeldung und danach zurück zum Bericht. Behoben wurde das im Dienst; das
  Plugin benennt die Anmeldung nun ausdrücklich.

### Voraussetzung

- Benötigt `GET /scans/{id}/findings/{rule}` der Public API v1. Ältere Stände des Dienstes
  liefern den Endpunkt nicht; das Aufklappen meldet dann, dass die Fundstellen nicht abgerufen
  werden konnten. Alles Übrige arbeitet weiter.

## [0.3.1]

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
