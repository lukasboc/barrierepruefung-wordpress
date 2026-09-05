# Barrierepruefung.de – Web Accessibility Checker

WordPress-Plugin zu [barrierepruefung.de](https://barrierepruefung.de): Prüfung der eigenen
Website aus dem Backend anstoßen, die offenen Befunde samt Hinweis zur Behebung und das
Seitenkontingent einsehen, und die Erklärung zur Barrierefreiheit per Shortcode oder Block
einbinden.

Unter *Werkzeuge → Barrierefreiheit* stehen die Befunde der letzten Prüfung als Arbeitsliste
— je Regel Schweregrad, Erfolgskriterium, Zahl der Fundstellen, was zu tun ist und welche
Seiten betroffen sind. Eine Regel lässt sich aufklappen: dann stehen ihre einzelnen
Fundstellen da, mit Selektor, Messwerten und HTML-Ausschnitt, damit die Stelle in WordPress
auffindbar ist. Darunter das Seitenkontingent. Behoben wird in WordPress, danach stößt
dieselbe Seite die nächste Prüfung an.

Screenshots bleiben auf der Website des Dienstes; die Seite verweist auf den vollständigen
Bericht, statt Bilder in die fremde Installation zu kopieren.

Die Erklärung wird **serverseitig** eingebunden — sie ist also auch ohne JavaScript und für
assistive Technologien vollständig vorhanden.

## Was das Plugin nicht tut

Es verändert Ihre Website nicht und repariert nichts automatisch. Sogenannte
Accessibility-Overlays beheben keine Barrieren, verschlechtern regelmäßig die Nutzbarkeit mit
assistiven Technologien und sind kein Konformitätsnachweis.

Es prüft auch nicht selbst: die Prüfung braucht einen echten Browser, das leistet keine
WordPress-Installation auf Shared Hosting. Das Plugin ist der Zugang zum Dienst, nicht die
Prüf-Engine.

## Installation

Das Plugin ist noch nicht im WordPress-Verzeichnis eingereicht. Bis dahin als ZIP aus den
[Releases](https://github.com/lukasboc/barrierepruefung-wordpress/releases); künftig auch aus
dem WordPress-Verzeichnis.

Einrichtung, Shortcode-Attribute und häufige Fragen stehen in [`readme.txt`](readme.txt) —
das ist zugleich der Text im Plugin-Verzeichnis und deshalb als einzige Datei hier auf
Englisch.

## Voraussetzungen

WordPress 6.5+, PHP 8.1+, ein Konto bei barrierepruefung.de (für das API-Token).

## Entwicklung

```bash
composer install
composer test   # PHPUnit, ohne WordPress
composer lint   # PHPCS: Sicherheits- und i18n-Regeln
```

Die Tests laufen ohne WordPress-Installation: `tests/bootstrap.php` stellt sparsame Attrappen
der wenigen berührten WordPress-Funktionen bereit. Geprüft wird, was tatsächlich etwas
berechnet — das Verschieben der Überschriftenebenen und das Herausschneiden von Abschnitten.

### Verhältnis zum Dienst

Die Logik der Überschriftenverschiebung liegt zweimal vor: hier im Plugin und serverseitig im
Dienst (für die Einbindung ohne Plugin). Das sind zwei Laufzeiten, deshalb portiert statt
geteilt. Ein Test im Dienst-Repository zieht dieses Plugin als Composer-Paket herein und
vergleicht beide Ausgaben von `ueberschriften_verschieben` Zeichen für Zeichen — wer sie
ändert, ändert damit einen Vertrag.

`abschnitt` liegt ebenfalls doppelt vor, hat serverseitig aber eine andere Signatur und wird
**nicht** automatisch gegengeprüft. Änderungen daran brauchen deshalb besondere Sorgfalt.

## Lizenz

GPL-2.0-or-later — siehe [LICENSE](LICENSE).
