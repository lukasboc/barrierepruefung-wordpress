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

`a11y-checker.php` lädt die vier Klassen aus `includes/` in `plugins_loaded`:
`A11y_Checker_Client` (nur `wp_remote_*`, Zugang zur Public API v1), `A11y_Checker_Verification`
(Domain-Nachweis als Meta-Element und unter `/.well-known/`), `A11y_Checker_Shortcode`
(`[barrierefreiheitserklaerung]` samt Block, serverseitig gerendert) und `A11y_Checker_Admin`
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
`languages/a11y-checker-de_DE.po`. Kommt eine Zeichenkette dazu oder ändert sich eine, gehören
beide Dateien in denselben Commit:

```bash
wp i18n make-pot . languages/a11y-checker.pot --slug=a11y-checker --domain=a11y-checker \
    --exclude=vendor,tests,dist
msgmerge --update --backup=none languages/a11y-checker-de_DE.po languages/a11y-checker.pot
# deutsche Fassung nachtragen, dann:
msgfmt --check -o languages/a11y-checker-de_DE.mo languages/a11y-checker-de_DE.po
```

Eine englische msgid ohne deutschen msgstr fällt nicht auf: die Seite zeigt dann englischen
Text in einer deutschen Installation, ohne Fehler. `msgfmt --statistics` nennt die Zahl der
untersetzten Einträge — vier sind es planmäßig (Plugin-Name, Plugin-URI, Author,
Author-URI).

Auch `*.mo` gehört ins Repository. Solange das Plugin nicht im Verzeichnis ist, gibt es keine
Sprachpakete von translate.wordpress.org, und ohne die mitgelieferte `.mo` läuft jede deutsche
Installation auf Englisch. Nach der Aufnahme gewinnen die Sprachpakete aus `WP_LANG_DIR`
ohnehin. Mitgeliefert ist nur `de_DE`; `de_AT`, `de_CH` und `de_DE_formal` sehen bis zur
Aufnahme Englisch — WordPress hat keinen Rückfall zwischen Locales.

Nicht übersetzt werden Shortcode-Name und Attribute (`teil`, `stand`, `ueberschrift`, `sprache`
samt Werten `komplett`, `maengel`, `kontakt`) — das sind Bezeichner, keine Prosa. Der
Änderungsverlauf in der `readme.txt` ist die englische Fassung von `CHANGELOG.md`, nicht dessen
Kopie.

Offen bis zur Einreichung: die Grafiken in `assets/` samt `== Screenshots ==` in der
`readme.txt`, und die Text-Domain auf den beim Review vergebenen Slug ziehen (sonst laden
Übersetzungen von translate.wordpress.org still nicht — und die mitgelieferte
`a11y-checker-de_DE.mo` heißt dann ebenfalls anders).
