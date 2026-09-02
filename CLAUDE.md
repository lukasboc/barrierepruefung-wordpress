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

Offen bis zur Einreichung: die Grafiken in `assets/`, `Tested up to:` gegen die aktuelle
WordPress-Version, und die Text-Domain auf den beim Review vergebenen Slug ziehen (sonst laden
Übersetzungen von translate.wordpress.org still nicht).
