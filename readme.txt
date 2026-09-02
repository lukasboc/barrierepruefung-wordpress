=== Barrierepruefung.de – Web Accessibility Checker ===
Contributors: lukasbo
Tags: accessibility, barrierefreiheit, bitv, bfsg, wcag
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prüft diese Website auf Barrierefreiheit und bindet die Erklärung zur Barrierefreiheit per Shortcode ein.

== Description ==

Das Plugin verbindet Ihre WordPress-Installation mit einem Prüfdienst für digitale
Barrierefreiheit. Sie können aus dem Backend eine Prüfung anstoßen und den Text Ihrer
Erklärung zur Barrierefreiheit per Shortcode oder Block auf einer Seite ausgeben.

Die Erklärung wird **serverseitig** eingebunden — sie ist also auch ohne JavaScript und für
assistive Technologien vollständig vorhanden.

= Was das Plugin nicht tut =

Es verändert Ihre Website nicht und repariert nichts automatisch. Sogenannte
Accessibility-Overlays beheben keine Barrieren, verschlechtern regelmäßig die Nutzbarkeit mit
assistiven Technologien und sind kein Konformitätsnachweis.

== External services ==

Dieses Plugin ruft einen externen Dienst auf, dessen Adresse Sie bei der Einrichtung selbst
eintragen (Standard: barrierepruefung.de).

Übertragen werden:

* die Adresse dieser Website,
* das von Ihnen erzeugte API-Token,
* die Kennung Ihrer Website im Dienst.

Übertragen werden **keine** Inhalte oder personenbezogenen Daten Ihrer Besucherinnen und
Besucher. Die Übertragung erfolgt, wenn Sie eine Prüfung anstoßen, die Domain bestätigen oder
die Erklärung abgerufen wird (höchstens einmal pro Stunde, danach aus dem Zwischenspeicher).

Datenschutzerklärung und Nutzungsbedingungen des Dienstes: https://barrierepruefung.de/rechtliches

== Installation ==

1. Plugin installieren und aktivieren.
2. Im Konto des Dienstes unter *Integrationen* ein API-Token für diese Website erzeugen.
   Dort stehen zugleich die Website-Kennung und die Adresse des Dienstes.
3. Unter *Werkzeuge → Barrierefreiheit* diese drei Angaben eintragen.
4. Domain bestätigen — das Plugin liefert den Nachweis selbst aus, Sie brauchen keinen
   DNS-Zugriff.
5. Prüfung anstoßen und den Shortcode `[barrierefreiheitserklaerung]` auf einer Seite einfügen.

== Frequently Asked Questions ==

= Muss ich einen DNS-Eintrag setzen? =

Nein. Das Plugin liefert den Nachweis als Meta-Element und als Datei unter
`/.well-known/a11y-site-verification.txt` selbst aus.

= Was passiert, wenn der Dienst nicht erreichbar ist? =

Die zuletzt erfolgreich geladene Fassung der Erklärung wird weiter ausgeliefert — mit ihrem
ursprünglichen Stand-Datum. Eine Erklärung, die wegen einer Störung von der Website
verschwindet, wäre für Sie ein Rechtsproblem.

= In welchen Sprachen liegt das Plugin vor? =

Deutsch (Ausgangssprache) und Englisch. Die Vorlage für weitere Übersetzungen liegt dem
Plugin unter `languages/a11y-checker.pot` bei.

= Was bleibt nach der Deinstallation zurück? =

Nichts. Die Deinstallation entfernt alle Optionen und Zwischenspeicher, einschließlich des
Tokens und der zuletzt geladenen Fassung der Erklärung — in einem Netzwerk für jede
Unterseite einzeln.

= Ersetzt die automatische Prüfung ein Gutachten? =

Nein. Automatisierte Tests decken nur einen Teil der Anforderungen ab. Der Dienst führt Sie
durch die übrigen Prüfschritte; die Erklärung weist ausdrücklich aus, dass sie auf einer
Selbstbewertung beruht.

== Changelog ==

= 0.2.0 =
* Behoben: Die Überschriftenebene wird jetzt an der obersten tatsächlich ausgegebenen
  Überschrift gemessen. Bisher blieb bei der Voreinstellung die h1 der Erklärung eine h1 -
  mitten in einer Seite ist das selbst ein Verstoß gegen WCAG 1.3.1 -, und ein mit
  teil="maengel" oder teil="kontakt" eingebundener Abschnitt kam eine Ebene zu tief heraus.
  Wer die Ebenen bisher mit ueberschrift="3" von Hand ausgeglichen hat, stellt jetzt auf
  ueberschrift="2" um (oder lässt das Attribut weg).

= 0.1.0 =
* Erste Fassung: Verbindung, Domain-Bestätigung, Prüfung anstoßen, Shortcode und Block.
* Deutsche und englische Sprachfassung, vollständige Deinstallation.
