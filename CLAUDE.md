# CLAUDE.md

WordPress-Plugin zu barrierepruefung.de (Prüfung digitaler Barrierefreiheit, BFSG/BITV 2.0).
Deutsch ist die Sprache für Kommentare, Commits und Dokumentation.

## Kommandos

```bash
composer install
composer test       # PHPUnit 10, ohne WordPress-Installation
composer lint       # PHPCS: WordPress.Security, WordPress.DB, WordPress.WP.AlternativeFunctions,
                    # WordPress.WP.EnqueuedResources, WordPress.WP.DeprecatedFunctions, WordPress.WP.I18n,
                    # PHPCompatibilityWP
composer lint:fix
```

## Aufbau

`barrierepruefung-de-web-accessibility-checker.php` lädt die vier Klassen aus `includes/` in `plugins_loaded`:
`Barrierepruefung_Client` (nur `wp_remote_*`, Zugang zur Public API v1), `Barrierepruefung_Verification`
(Domain-Nachweis als Meta-Element und unter `/.well-known/`), `Barrierepruefung_Shortcode`
(`[barrierefreiheitserklaerung]` samt Block, serverseitig gerendert) und `Barrierepruefung_Admin`
(Seite unter *Werkzeuge*).

## Was hier nicht verhandelbar ist

- Ausschließlich `wp_remote_get()` / `wp_remote_post()`, nie cURL oder eine eigene Bibliothek.
- Der Shortcode rendert **serverseitig**. Ein per JavaScript nachgeladener Text wäre für einen
  Teil der assistiven Technologien nicht vorhanden — in einem Barrierefreiheitsprodukt ist das
  kein zulässiger Kompromiss.
- Kein Nachladen von Code, keine Telemetrie, `manage_options` + Nonce für jede Backend-Aktion.
- Die Deinstallation räumt vollständig auf, im Netzwerk je Unterseite.

## Der Vertrag mit dem Dienst

`ueberschriften_verschieben()` existiert ein zweites Mal im Dienst
(`App\Domain\Declarations\DeclarationFragment`), weil es zwei Laufzeiten sind — portiert, nicht
geteilt. Das Dienst-Repository zieht dieses Plugin als Composer-Paket herein und vergleicht
beide Ausgaben Zeichen für Zeichen. Sie sind schon einmal auseinandergelaufen. Eine Änderung an
dieser Methode ist eine Änderung an beiden Seiten.

`abschnitt()` liegt ebenfalls doppelt vor (dort mit anderer Signatur, `string $teil` statt
`array $titel`), wird aber **nicht** automatisch gegen die Dienst-Fassung geprüft — der
Paritätstest hält nur `ueberschriften_verschieben()`. Änderungen an `abschnitt()` brauchen
deshalb besondere Sorgfalt von Hand.

## wordpress.org

Der Slug — und mit ihm die Text-Domain, der Ordnername im ZIP und die URL im Verzeichnis
(<https://wordpress.org/plugins/barrierepruefung-de-web-accessibility-checker/>) — ist
`barrierepruefung-de-web-accessibility-checker`. Er ist die Marke
„Barrierepruefung.de" samt Zusatz, in der einzigen Schreibweise, die ein Slug erlaubt. Kürzer
wäre er nicht besser: „a11y-checker" hieß er bis 0.6.0 und wäre genau der Fall, den das
Plugins-Team ausschließt — „Accessibility Checker" ist als `accessibility-checker` vergeben,
und das Team schreibt generische Namen ohnehin auf markengeführte Slugs um (`checkbarriere`,
`kipphard-accessibility-audit`, `accessgo-barrierefreiheit`).

Vergeben ist er seit dem 16.09.2026, und ein Slug lässt sich im Verzeichnis nicht mehr ändern.
Text-Domain, `phpcs.xml.dist`, beide Workflows und die Dateinamen unter `languages/` hängen an
ihm; weicht eines davon ab, laden die Übersetzungen von translate.wordpress.org still nicht.

Kurz gehalten sind bewusst drei Dinge, die nicht die Text-Domain sind: der Slug der
Verwaltungsseite (`?page=barrierepruefung`), der Block-Namensraum
(`barrierepruefung/erklaerung`) und die CSS-Klassen im Frontend (`barrierepruefung-erklaerung`
und Geschwister). Der lange Slug gehört in die Text-Domain, nicht in jedes class-Attribut.

`a11y-site-verification` bleibt, wie es ist: Meta-Name und Dateipfad des Domain-Nachweises
gehören dem Dienst (`SiteVerificationService`), nicht diesem Plugin.

`readme.txt` ist das Gesicht im Verzeichnis, `README.md` das auf GitHub. Der Änderungsverlauf
steht in beiden — `CHANGELOG.md` ist die Quelle. `.distignore` bestimmt, was ins ZIP kommt.

`readme.txt`, das Feld `Description:` im Plugin-Kopf und **jede Zeichenkette in `__()`** sind
englisch und bleiben es. Das Plugins-Team verlangt das für die `readme.txt` seit dem 28.07.2025
(<https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/>),
und für die Strings folgt es aus derselben Sache: GlotPress liest die msgids als die englischen
Originale. Standen dort deutsche Sätze, müssten Übersetzerinnen auf translate.wordpress.org
Deutsch nach Deutsch übersetzen. Das ist die einzige Ausnahme von der deutschen Hausregel —
Kommentare, Commits, Testnamen und alle übrigen Dokumente bleiben deutsch.

Deutsch ist damit eine Übersetzung wie jede andere und liegt in
`languages/barrierepruefung-de-web-accessibility-checker-de_DE.po`. Kommt eine Zeichenkette dazu oder ändert sich eine, gehören
beide Dateien in denselben Commit:

```bash
wp i18n make-pot . languages/barrierepruefung-de-web-accessibility-checker.pot --slug=barrierepruefung-de-web-accessibility-checker --domain=barrierepruefung-de-web-accessibility-checker \
    --exclude=vendor,tests,dist
msgmerge --update --backup=none languages/barrierepruefung-de-web-accessibility-checker-de_DE.po languages/barrierepruefung-de-web-accessibility-checker.pot
# deutsche Fassung nachtragen, dann:
msgfmt --check -o languages/barrierepruefung-de-web-accessibility-checker-de_DE.mo languages/barrierepruefung-de-web-accessibility-checker-de_DE.po
```

Eine englische msgid ohne deutschen msgstr fällt nicht auf: die Seite zeigt dann englischen
Text in einer deutschen Installation, ohne Fehler. `msgfmt --statistics` nennt die Zahl der
untersetzten Einträge — vier sind es planmäßig (Plugin-Name, Plugin-URI, Author,
Author-URI).

Auch `*.mo` gehört ins Repository. Solange translate.wordpress.org für eine Sprache kein
Sprachpaket gebaut hat, läuft ohne die mitgelieferte `.mo` jede deutsche Installation auf
Englisch. Gibt es eins, gewinnt es aus `WP_LANG_DIR` ohnehin. Mitgeliefert ist nur `de_DE`;
`de_AT`, `de_CH` und `de_DE_formal` sehen bis zu ihrem Sprachpaket Englisch — WordPress hat
keinen Rückfall zwischen Locales.

Nicht übersetzt werden Shortcode-Name und Attribute (`teil`, `stand`, `ueberschrift`, `sprache`
samt Werten `komplett`, `maengel`, `kontakt`) — das sind Bezeichner, keine Prosa. Der
Änderungsverlauf in der `readme.txt` ist die englische Fassung von `CHANGELOG.md`, nicht dessen
Kopie.

### Veröffentlichen

Eine Version erscheint, indem ihr Tag `vX.Y.Z` gepusht wird. `release.yml` baut daraus einmal
den Baum nach `.distignore` und gibt ihn zweimal aus: als ZIP im GitHub-Release und als
`trunk/` samt `tags/X.Y.Z/` im SVN (<https://plugins.svn.wordpress.org/barrierepruefung-de-web-accessibility-checker>).
Vorher müssen `Version:` im Plugin-Kopf, `Stable tag:` in der `readme.txt` und das Tag
übereinstimmen — der Workflow bricht sonst ab, denn das Verzeichnis liefert den Stable tag aus,
nicht das neueste Tag.

Der SVN-Teil läuft nur bei der Repository-Variable `WPORG_DEPLOY=true`; die Zugangsdaten liegen
im Environment `wordpress-org`, das nur Tags `v*` zulässt. SVN ist ein Auslieferungsweg, kein
Arbeitsstand: dort landet nur, was fertig ist. 0.6.3 ging am 18.09.2026 als erste Version von
Hand hinein.
