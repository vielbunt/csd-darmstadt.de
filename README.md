# CSD Darmstadt – WordPress Block-Child-Theme

Block-Child-Theme auf Basis von **Twenty Twenty-Five** für [csd-darmstadt.de](https://www.csd-darmstadt.de).  
Veranstaltet von [vielbunt e.V.](https://www.vielbunt.org) – AK Öffentlichkeitsarbeit.

## Installation

1. **Eltern-Theme installieren:** In WordPress unter *Design → Themes → Theme hinzufügen* nach „Twenty Twenty-Five" suchen und installieren (muss vorhanden, aber nicht aktiviert sein).
2. **Dieses Theme hochladen:** Die ZIP-Datei unter *Design → Themes → Theme hinzufügen → Theme hochladen* einspielen und aktivieren.
3. **Cera Pro:** Die Schrift wird automatisch aus der Mediathek geladen (`/wp-content/uploads/2021/01/`). Keine Font-Dateien im Theme (Lizenz).
4. **Menü zuweisen:** Im Site-Editor den Navigations-Block im Header öffnen und das bestehende Menü von csd-darmstadt.de auswählen.
5. **Startseite setzen:** Unter *Einstellungen → Lesen* „Eine statische Seite" wählen – das Template `front-page` greift automatisch.

## Dynamische Blöcke

| Block | Beschreibung |
|---|---|
| `csd/hero` | Startseiten-Hero mit Jahresgrafik rechts, Hintergrundbild im Editor wählbar |
| `csd/quicklinks` | 8 Schnellzugriff-Kacheln, Hintergrundbilder im Editor wählbar |
| `csd/events` | Letzte 8 Beiträge als Kacheln (Foto oder Farb-Kachel mit Datum) |
| `csd/feed` | Letzte 6 Beiträge als Liste |
| `csd/logo` | `variant="csd"` → CSD-Logo, `variant="vielbunt"` → vielbunt-Logo |
| `csd/footerlinks` | Footer-Navigationslinks |
| `csd/post-hero` | Hero für Einzel-Beiträge und Seiten |

## Jahresgrafik im Hero

Die Datei `assets/flag-pic-2026.svg` ist der Platzhalter für die jährliche CSD-Grafik
(Rainbow Flag + Datum). Für optimale Darstellung eine Version mit **transparentem Hintergrund**
erstellen und diese Datei ersetzen – kein Code-Änderung nötig.

## Farben

Primärfarbe: **CSD Lila `#6546B4`** (registriert als `--wp--preset--color--purple`).
Der Slug `pink` ist im Theme ebenfalls auf `#6546B4` gesetzt, damit alle CSS-Referenzen
des Basis-Frameworks funktionieren.

## Lizenzhinweis Cera Pro

Die Schriftart ist lizenziert für vielbunt-Kontexte. Keine Schriftdateien im Theme –
geladen per `@font-face` aus der Mediathek. Pfad ggf. in `functions.php` anpassen.
