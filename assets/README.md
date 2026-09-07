# Grafiken für das Plugin-Verzeichnis

Sie gehören nicht ins Plugin-ZIP (`.distignore` schließt `assets/` aus); der Deploy-Workflow
lädt sie in das SVN-Verzeichnis `assets/`.

| Datei | Maße | Zweck |
|---|---|---|
| `banner-772x250.png` | 772 × 250 | Kopfbild der Plugin-Seite |
| `banner-1544x500.png` | 1544 × 500 | dasselbe für hohe Auflösung |
| `icon-128x128.png` | 128 × 128 | Symbol in Suche und Liste |
| `icon-256x256.png` | 256 × 256 | dasselbe für hohe Auflösung |
| `screenshot-1.png` | 2464 × 1808 | Einrichtung unter *Werkzeuge → Barrierefreiheit* |
| `screenshot-2.png` | 2464 × 2220 | Dieselbe Seite nach dem Verbinden: Status, Kontingent, Shortcode |
| `screenshot-3.png` | 2464 × 2908 | Dieselbe Seite nach einer Prüfung: Befunde und betroffene Seiten |

Die Beschriftungen stehen unter `== Screenshots ==` in der `readme.txt`. Ihre Reihenfolge ist
die Nummerierung dieser Dateien — kommt eine Datei dazu oder fällt eine weg, gehört die Liste
dort in denselben Commit.

Die Screenshots zeigen die deutsche Oberfläche, die `readme.txt` beschriftet sie englisch. Das
ist im Verzeichnis üblich und richtig so: die Beschriftung liest, wer die Plugin-Seite
aufschlägt, die Oberfläche sieht, wer das Plugin einsetzt.

Barrierefreiheit gilt auch hier: ausreichender Kontrast im Banner, keine Aussage allein über
Farbe, lesbare Schriftgröße im Screenshot.
