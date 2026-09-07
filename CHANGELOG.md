# Änderungsverlauf

Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung [Semantic Versioning](https://semver.org/lang/de/).

## [0.6.3]

### Behoben

- „1 Fundstellen anzeigen“ stand in der Befundliste, sobald eine Regel genau eine Fundstelle
  hatte. Die Zeichenkette kannte keine Einzahl. Sie liegt jetzt als `_n()` in beiden Formen vor,
  englisch wie deutsch — Sprachen mit anderen Pluralregeln bekommen damit ebenfalls den Platz,
  den sie brauchen.

## [0.6.2]

### Behoben

- Nach dem Bestätigen der Domain meldete die Seite oben „Die Domain ist bestätigt" und zeigte
  darunter weiter „Domain bestätigt: nein" — samt dem Knopf, der das gerade erledigt hatte. Bis
  zu fünf Minuten lang, und nur „Status aktualisieren" kam dagegen an. Der Grund war der
  Zwischenspeicher, den das Bestätigen als einzige Aktion nicht verworfen hat; das Auslösen
  einer Prüfung tat es längst. Ein gescheiterter Versuch lässt ihn weiterhin stehen: dann hat
  sich beim Dienst nichts geändert.
- Dieselbe Lücke an zwei weiteren Stellen: Eine neu eingetragene Verbindung und das
  Zurücksetzen der Adresse verwerfen den Zwischenspeicher jetzt ebenfalls. Er gehört zu einer
  Verbindung und zu einer Adresse, nicht zur Installation.

## [0.6.1]

### Geändert

- Der Absatz über das Token in der Einrichtungsanleitung ist gestrichen. Was das Token darf und
  wo es liegt, beantwortet dort eine Frage, die noch niemand gestellt hat — an dieser Stelle
  sucht man den Weg zum Token, nicht seine Rechte.
- Der letzte Schritt sagt jetzt, was die Adresse des Dienstes *ist* — wohin das Plugin seine
  Anfragen schickt —, statt zu raten, ob man sie ändern sollte. Dass sich das nur beim
  Selbstbetrieb lohnt, ist keine Auskunft, die beim Einrichten gebraucht wird.

## [0.6.0]

### Geändert

- Der Slug heißt `barrierepruefung-de-web-accessibility-checker` statt `a11y-checker`, und mit
  ihm die Text-Domain, der Ordnername im ZIP und die spätere URL im Verzeichnis. „a11y-checker"
  ist die Abkürzung von „Accessibility Checker" — das ist als `accessibility-checker` vergeben
  (Equalize Digital, 10.000+ Installationen), und generische Namen weist das Plugins-Team
  zurück oder schreibt sie auf markengeführte Slugs um. Bei der Einreichung muss der neue Slug
  ausdrücklich verlangt werden; abgeleitet würde er zu `barrierepruefungde-…`, weil
  `sanitize_title()` den Punkt in „Barrierepruefung.de" wegwirft.
- Mit dem Slug wandern die internen Präfixe, solange das billig ist: Optionen und Transients
  heißen `barrierepruefung_*`, Klassen `Barrierepruefung_*`, Konstanten `BARRIEREPRUEFUNG_*`,
  die Dateien `includes/class-barrierepruefung-*.php`. Nach der Aufnahme ins Verzeichnis wäre
  derselbe Schritt eine Datenmigration in fremden Installationen gewesen.
- Kurz und getrennt vom langen Slug bleiben die Bezeichner, die oft geschrieben werden: die
  Verwaltungsseite liegt unter `?page=barrierepruefung`, der Block heißt
  `barrierepruefung/erklaerung`, die CSS-Klassen im Frontend `barrierepruefung-erklaerung`,
  `barrierepruefung-stand` und `barrierepruefung-hinweis`.
- Die Deinstallation räumt zusätzlich die Optionen und Transients unter dem alten Präfix weg.
  Wer eine Vorabfassung im Einsatz hatte, soll die Reste nicht behalten — unter ihnen liegt ein
  Token.

### Unverändert

- `a11y-site-verification` als Meta-Name und Dateipfad des Domain-Nachweises. Der gehört dem
  Dienst, nicht diesem Plugin.
- Der Composer-Namensraum `lubomedia/barrierepruefung-wordpress`. Über ihn zieht das
  Dienst-Repository dieses Plugin als Paket herein.

### Achtung beim Aktualisieren

- Der Dateiname des Plugins hat sich geändert; WordPress deaktiviert eine Vorabfassung deshalb
  beim Austausch. Nach dem Aktivieren muss die Verbindung einmal neu eingetragen werden — die
  Optionen liegen jetzt unter dem neuen Präfix.
- Eine mit dem Block gesetzte Erklärung meldet „unerwarteter Inhalt": der Block-Namensraum hat
  gewechselt. Der Shortcode `[barrierefreiheitserklaerung]` ist davon nicht betroffen.
- Der Paritätstest im Dienst-Repository verweist auf `A11y_Checker_Shortcode` und
  `includes/class-a11y-checker-shortcode.php`. Er zieht das Plugin über `^0.4.0` herein und
  läuft deshalb zunächst weiter; wer dort auf `^0.6.0` hebt, muss Klassenname und Pfad mitziehen.

## [0.5.1]

### Geändert

- Der Verweis in der Plugin-Liste heißt jetzt durchgehend *Einstellungen* — die Aufschrift, die
  neben jedem anderen Plugin steht und nach der deshalb gesucht wird. Vorher stand dort
  *Einrichten*, solange nichts verbunden war.
- Als Autor steht *Lukas Bock* im Plugin-Kopf, verlinkt auf barrierepruefung.de. Der
  Composer-Namensraum `lubomedia/barrierepruefung-wordpress` bleibt, wie er ist: über ihn zieht
  das Dienst-Repository dieses Plugin als Paket.
- `languages/`: Der Autor ist Teil des Kopfes und damit der Übersetzungsdateien. Planmäßig
  untersetzt sind jetzt vier Einträge statt drei — Plugin-Name, Plugin-URI, Author und der neu
  dazugekommene Author-URI.

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
