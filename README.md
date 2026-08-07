# contao-carousel-bundle

Bildkarussell für **Contao 4.13 und Contao 5** auf Basis von **Swiper** ([contao-components/swiper](https://packagist.org/packages/contao-components/swiper)), das auch der Contao-Core verwendet. Das Bundle ist die Portierung der Contao-3-Erweiterung [dk_caroufredsel](https://github.com/dklemmt/contao_dk_caroufredsel) von Dirk Klemmt: Die Datenbanktabellen, Element- und Modultypen bleiben unverändert, nur die Anzeige-Engine ist seit Version 3.0.0 Swiper statt des aufgegebenen jQuery-Plugins carouFredSel. jQuery wird nicht mehr benötigt.

Bis Version 3.0.0 hieß das Paket `schachbulle/contao-caroufredsel-bundle`; mit dem Wechsel auf Swiper wurde es in `schachbulle/contao-carousel-bundle` umbenannt. Die internen Kennungen (`tl_dk_caroufredsel`, `caroufredsel_*`-Typen, Template- und CSS-Namen) behalten bewusst den alten Namen, damit bestehende Daten weiterlaufen.

## Funktionen

- **Galerie** (Inhaltselement und Frontend-Modul): Bilder aus Dateien oder Ordnern als Karussell, wahlweise mit **synchronisierter Vorschauleiste** (Swiper-Thumbs-Modul), Lightbox-Großansicht und Begrenzung der Bildanzahl.
- **Wrapper Start/Stop** (Inhaltselemente): verpackt beliebige Inhaltselemente dazwischen in ein Karussell — jedes Element wird automatisch eine Kachel.
- **Karussell-Modul**: zeigt die im Backend-Modul gepflegten Inhaltselemente einer Karussell-Konfiguration als Karussell.
- **Newsticker** (Frontend-Modul): lässt Nachrichtenbeiträge als Ticker durchlaufen (benötigt `contao/news-bundle`).
- **Hintergrund-Slideshow** (Inhaltselement und Frontend-Modul): bildschirmfüllende, durchlaufende Hintergrundbilder (CSS `object-fit`, ohne Skript-Rechnereien).
- **Wiederverwendbare Karussell-Konfigurationen**: Abspielverhalten, Übergänge, Größen, Navigation, Paginierung, Tastatur- und Mausrad-Steuerung werden zentral im Backend-Modul „carouFredSel" gepflegt und von beliebig vielen Elementen/Modulen genutzt.
- **Extras, die der Core-Slider nicht bietet:** Play/Pause-Schalter, Fortschrittsbalken oder -kreis je Autoplay-Intervall, verzögerter Autoplay-Start, zufälliges Startelement, Synchronisierung zweier Karussells (Controller-Modul), fertiges Skin „light".

## Voraussetzungen

- Contao **4.13** oder **5.x**
- PHP **8.1** oder neuer
- Für das Newsticker-Modul: `contao/news-bundle`

## Installation

```bash
composer require schachbulle/contao-carousel-bundle
```

Anschließend im Install-Tool bzw. über `contao:migrate` die Datenbank aktualisieren. Swiper wird als Composer-Abhängigkeit (`contao-components/swiper`) automatisch mitinstalliert.

## Nutzung

1. Im Backend unter **Inhalte → carouFredSel** eine Karussell-Konfiguration anlegen (Titel + gewünschte Optionen).
2. Ein Inhaltselement (`caroufredsel_gallery`, `caroufredsel_background` oder das Paar `caroufredsel_start`/`caroufredsel_stop`) oder ein Frontend-Modul anlegen und die Konfiguration zuweisen.
3. Beim Wrapper: Alle Inhaltselemente zwischen Start und Stop werden zu Karussell-Kacheln.

## Einstellungen der Erweiterung

Es gibt nur noch eine Einstellung:

| Einstellung       | Werte               | Standard | Wirkung |
|-------------------|---------------------|----------|---------|
| `dk_cfsUsageMode` | `basic`, `advanced` | `basic`  | Umfang der angebotenen Optionen im Backend |

- **Contao 4.13:** unter **System → Einstellungen** (Bereich „carouFredSel").
- **Contao 5:** Das Einstellungen-Modul existiert nicht mehr; der Wert kann über `system/config/localconfig.php` gesetzt werden: `$GLOBALS['TL_CONFIG']['dk_cfsUsageMode'] = 'advanced';`

## Abbildung der Karussell-Optionen auf Swiper

| Feld (tl_dk_caroufredsel) | Swiper |
|---------------------------|--------|
| `carouselType` circular/infinite | `loop: true` |
| `carouselType` once | kein Loop |
| `direction` up/down | `direction: 'vertical'` |
| `direction` right/down | `autoplay.reverseDirection` |
| `scrollItems` | `slidesPerGroup` |
| `autoPlay`, `autoTimeoutDuration` | `autoplay.delay` |
| `autoDelay` | verzögerter `autoplay.start()` |
| `scrollPauseOnHover` | `autoplay.pauseOnMouseEnter` |
| `autoProgress` bar/pie | Fortschrittsanzeige über das `autoplayTimeLeft`-Ereignis |
| `scrollFx` fade/crossfade | `effect: 'fade'`; alle anderen Effekte → Standard `slide` |
| `scrollDuration` | `speed` |
| `widthSelect`/`heightSelect`, `padding`, `align` | CSS des Containers; `padding` wird zu `spaceBetween` |
| `itemsWidth`/`itemsHeight` | CSS der Kacheln + `slidesPerView: 'auto'` |
| `itemsVisibleSelect` fixed | `slidesPerView: n` |
| `itemsVisibleSelect` variable, min/max | `slidesPerView: 'auto'` |
| `itemsStartSelect` number/random | `initialSlide` (bei random im Browser ausgewürfelt) |
| `prevKey`/`nextKey`, `paginationKeys` | `keyboard.enabled` (immer Pfeiltasten) |
| `mousewheel` | `mousewheel` |
| `navigation`, `pagination` | eigene Elemente der Templates als `prevEl`/`nextEl` bzw. `pagination.el` |
| Vorschauleiste | Thumbs-Modul (eigener Swiper, gekoppelt) |
| Synchronisierung | Controller-Modul |

**Ohne Wirkung** (Spalten bleiben erhalten, Felder sind ausgeblendet): `scrollQueue`, `scrollEasing`, `cookie`, `responsive` (Swiper ist immer responsiv), `swipeOnTouch`/`swipeOnMouse` (Touch und Mausziehen sind immer aktiv), `autoProgressInterval` sowie die früheren jQuery-Einstellungen (`dk_cfsTriggerMode`, `dk_cfsOnWindowResize`, `dk_cfsImageLoader`, `dk_cfsTransition`, `dk_cfsDebug`).

## Templates

Alle Templates lassen sich wie gewohnt im Theme überschreiben und (im erweiterten Modus) je Element auswählen:

- `ce_caroufredsel`, `mod_caroufredsel`, `mod_caroufredsel_ticker`, `news_caroufredsel_ticker` — HTML-Gerüste (Swiper-Markup: `swiper` → `swiper-wrapper` → `swiper-slide`)
- `caroufredsel_gallery`, `caroufredsel_thumbnails` — Bild- und Vorschaulisten
- `js_caroufredsel` — Aufruf des Initialisierers (`caroufredselInit`, siehe `public/js/caroufredsel.js`)
- `css_caroufredsel`, `css_caroufredsel_light` — elementabhängiges CSS; „light" ist ein fertiges Skin mit Pfeilen, Play/Pause und Paginierung

## Umstieg

- **Von Version 2.x dieses Bundles:** Keine Datenänderungen nötig. Eigene Template-Ableitungen von `ce_caroufredsel`, `mod_caroufredsel*`, `js_caroufredsel` oder den CSS-Templates müssen an das Swiper-Markup angepasst werden. jQuery kann aus dem Layout entfernt werden, sofern nichts anderes es braucht.
- **Von dk_caroufredsel (Contao 3):** Tabellen- und Feldnamen sind unverändert; bestehende Daten bleiben nutzbar. Die alte Erweiterung vorher deinstallieren. Die Sortierung „benutzerdefiniert" folgt der Reihenfolge im Dateibaum-Feld (das frühere Feld `orderSRC` gibt es seit Contao 4.10 nicht mehr) — individuell sortierte Galerien einmal öffnen, ordnen und speichern.

## Danksagung und Lizenz

- **Dirk Klemmt** — Autor der ursprünglichen Contao-3-Erweiterung [contao_dk_caroufredsel](https://github.com/dklemmt/contao_dk_caroufredsel) (MIT/GPL); Backend-Konzept und Skins stammen aus dieser Erweiterung
- **Vladimir Kharlampidi** — Autor von [Swiper](https://swiperjs.com/) (MIT)
- **Fred Heusschen (Caroufredsel)** — Autor des namensgebenden jQuery-Plugins [carouFredSel](https://github.com/Samson1964/carouFredSel), das bis Version 2.x die Engine war

Das Bundle steht unter der [LGPL-3.0-or-later](LICENSE).
