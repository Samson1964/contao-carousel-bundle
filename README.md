# contao-caroufredsel-bundle

Bildkarussell für Contao auf Basis des jQuery-Plugins [carouFredSel](https://github.com/Samson1964/carouFredSel) von Fred Heusschen. Das Bundle ist die Portierung der Contao-3-Erweiterung [dk_caroufredsel](https://github.com/dklemmt/contao_dk_caroufredsel) von Dirk Klemmt auf **Contao 4.13 und Contao 5** mit **PHP 8**.

## Funktionen

- **Galerie** (Inhaltselement und Frontend-Modul): Bilder aus Dateien oder Ordnern als Karussell, wahlweise mit synchronisierter Vorschauleiste (Thumbnails), Lightbox-Großansicht und Begrenzung der Bildanzahl.
- **Wrapper Start/Stop** (Inhaltselemente): verpackt beliebige Inhaltselemente dazwischen in ein Karussell — jedes Element wird eine Kachel.
- **Karussell-Modul**: zeigt die im Backend-Modul gepflegten Inhaltselemente einer Karussell-Konfiguration als Karussell.
- **Newsticker** (Frontend-Modul): lässt Nachrichtenbeiträge als Ticker durchlaufen (benötigt `contao/news-bundle`).
- **Hintergrund-Slideshow** (Inhaltselement und Frontend-Modul): bildschirmfüllende, durchlaufende Hintergrundbilder.
- **Wiederverwendbare Karussell-Konfigurationen**: Abspielverhalten, Übergänge, Größen, Navigation, Paginierung, Tastatur-, Touch- und Mausrad-Steuerung werden zentral im Backend-Modul „carouFredSel" gepflegt und von beliebig vielen Elementen/Modulen genutzt.

## Voraussetzungen

- Contao **4.13** oder **5.x**
- PHP **8.1** oder neuer
- **jQuery** muss im Seitenlayout aktiviert sein (Bereich „JavaScript-Bibliotheken" bzw. „jQuery laden"). Die Skripte des Bundles werden über `TL_JQUERY` eingebunden und erscheinen nur, wenn das Layout jQuery lädt.
- Für das Newsticker-Modul: `contao/news-bundle`

## Installation

```bash
composer require schachbulle/contao-caroufredsel-bundle
```

Anschließend im Install-Tool bzw. über `contao:migrate` die Datenbank aktualisieren.

## Nutzung

1. Im Backend unter **Inhalte → carouFredSel** eine Karussell-Konfiguration anlegen (Titel + gewünschte Optionen).
2. Ein Inhaltselement (`caroufredsel_gallery`, `caroufredsel_background` oder das Paar `caroufredsel_start`/`caroufredsel_stop`) oder ein Frontend-Modul anlegen und die Konfiguration zuweisen.
3. Beim Wrapper: Alle Inhaltselemente zwischen Start und Stop werden zu Karussell-Kacheln.

## Einstellungen der Erweiterung

| Einstellung          | Werte                                        | Standard          | Wirkung |
|----------------------|----------------------------------------------|-------------------|---------|
| `dk_cfsUsageMode`    | `basic`, `advanced`                          | `basic`           | Umfang der angebotenen Optionen im Backend |
| `dk_cfsTriggerMode`  | `onDocumentReady`, `onWindowLoad`, `readyLoad` | `onDocumentReady` | Zeitpunkt, zu dem die Karussells starten |
| `dk_cfsOnWindowResize` | leer, `throttle`, `debounce`               | leer              | Drosselung des Resize-Ereignisses |
| `dk_cfsImageLoader`  | Checkbox                                     | aus               | Bilder per krioImageLoader nachladen |
| `dk_cfsTransition`   | Checkbox                                     | aus               | CSS-Übergänge statt jQuery-Animationen |
| `dk_cfsDebug`        | Checkbox                                     | aus               | Debug-Ausgaben des jQuery-Plugins |

- **Contao 4.13:** Die Einstellungen erscheinen unter **System → Einstellungen** (Bereich „carouFredSel").
- **Contao 5:** Das Einstellungen-Modul existiert nicht mehr. Die Werte können in der `config/parameters.yml` bzw. über `system/config/localconfig.php` gesetzt werden, z. B. `$GLOBALS['TL_CONFIG']['dk_cfsUsageMode'] = 'advanced';` — ohne Eintrag gelten die Standardwerte.

## Templates

Alle Templates lassen sich wie gewohnt im Theme überschreiben und (im erweiterten Modus) je Element auswählen:

- `ce_caroufredsel`, `mod_caroufredsel`, `mod_caroufredsel_ticker`, `news_caroufredsel_ticker` — HTML-Gerüste
- `caroufredsel_gallery`, `caroufredsel_thumbnails` — Bild- und Vorschaulisten
- `js_caroufredsel` — JavaScript-Aufruf des Plugins
- `css_caroufredsel`, `css_caroufredsel_light` — elementabhängiges CSS; „light" ist ein fertiges Skin mit Pfeilen, Play/Pause und Paginierung

## Umstieg von dk_caroufredsel (Contao 3)

- Tabellen- und Feldnamen sind unverändert (`tl_dk_caroufredsel`, `dk_cfs*`-Felder); bestehende Daten bleiben nutzbar. Die alte Erweiterung vorher deinstallieren.
- Die Sortierung „benutzerdefiniert" folgt jetzt der Reihenfolge im Dateibaum-Feld (das frühere Feld `orderSRC` gibt es seit Contao 4.10 nicht mehr). Eine individuell sortierte Galerie einmal öffnen, ordnen und speichern.
- Die Backend-Einstellungen `space` und `guests` der alten Paletten sind entfallen (in Contao 5 entfernt).

## Danksagung und Lizenz

- **Dirk Klemmt** — Autor der ursprünglichen Contao-3-Erweiterung [contao_dk_caroufredsel](https://github.com/dklemmt/contao_dk_caroufredsel) (MIT/GPL)
- **Fred Heusschen (Caroufredsel)** — Autor des jQuery-Plugins [carouFredSel](https://github.com/Samson1964/carouFredSel) (MIT/GPL)

Das Bundle steht unter der [LGPL-3.0-or-later](LICENSE); die eingebetteten JavaScript-Bibliotheken behalten ihre ursprünglichen Lizenzen (siehe Dateiköpfe unter `src/Resources/public/js/`).
