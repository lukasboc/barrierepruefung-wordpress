# Mitwirken

Fehlermeldungen und Vorschläge gern als
[Issue](https://github.com/lukasboc/barrierepruefung-wordpress/issues).

## Vor einem Pull Request

```bash
composer install
composer test
composer lint
```

Beides muss grün sein; die CI lässt `composer test` auf PHP 8.1 bis 8.4 laufen (`composer lint`
nur auf 8.1) und lässt zusätzlich `plugin-check` laufen.

## Was hier gilt

- **Sprache:** Code-Kommentare, Commit-Nachrichten und Dokumentation auf Deutsch. Sichtbare
  Zeichenketten kommen durch `__()` mit der Text-Domain `a11y-checker`.
- **Keine eigenen HTTP-Bibliotheken.** Ausschließlich `wp_remote_get()` / `wp_remote_post()` —
  das Plugin-Verzeichnis lässt nichts anderes zu.
- **Kein Nachladen von ausführbarem Code, keine Telemetrie.**
- **Berechtigungsprüfung und Nonce** für jede Backend-Aktion.
- **Kein Overlay.** Vorschläge, die die Website des Nutzers automatisch „reparieren", werden
  nicht übernommen — die Begründung steht im README.
- Änderungen an `ueberschriften_verschieben` brechen einen Vertrag mit dem Dienst (siehe
  README): ein Test dort vergleicht beide Fassungen Zeichen für Zeichen. Sie brauchen eine
  Begründung im Pull Request. `abschnitt` liegt im Dienst ebenfalls doppelt vor, wird dort aber
  nicht automatisch gegengeprüft (andere Signatur) — Änderungen daran verlangen deshalb
  besondere Sorgfalt von Hand statt eines grünen CI-Laufs.
- **Release:** Ein Tag hier (`vX.Y.Z`) verlangt im Dienst-Repository ein Anheben der
  `composer.json`-Anforderung `lubomedia/barrierepruefung-wordpress` auf die neue Version —
  sonst prüft der Paritätstest dort dauerhaft eine Fassung, die niemand installiert, und das
  ohne je fehlzuschlagen.

## Lizenz

Mit einem Beitrag stellst du ihn unter GPL-2.0-or-later.
