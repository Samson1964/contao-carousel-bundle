# Changelog

## Version 3.1.1 (2026-08-08)

* Fix: Die Bildgrößen-Auswahllisten in tl_content und tl_module riefen den Dienst unter seinem Contao-4-Aliasnamen `contao.image.image_sizes` auf, den es in Contao 5 nicht mehr gibt — das Öffnen von Modulen im Backend brach unter Contao 5.7 mit „You have requested a non-existent service" ab. Jetzt wird `contao.image.sizes` verwendet, der Name gilt in Contao 4.13 und 5 gleichermaßen.

## Version 3.1.0 (2026-08-07)

* Change: Paket von `schachbulle/contao-caroufredsel-bundle` in `schachbulle/contao-carousel-bundle` umbenannt (samt GitHub-Repository, PHP-Namespace `Schachbulle\ContaoCarouselBundle` und Asset-Pfad `bundles/contaocarousel/`), weil die Anzeige-Engine seit Version 3.0.0 Swiper statt carouFredSel ist. Die internen Kennungen (Tabelle `tl_dk_caroufredsel`, `caroufredsel_*`-Typen, Template- und CSS-Namen) bleiben als Migrationsbrücke unverändert.

## Version 3.0.0 (2026-08-07)

* Change: Anzeige-Engine von jQuery.carouFredSel auf Swiper (`contao-components/swiper`, wie im Contao-Core) umgestellt; jQuery wird nicht mehr benötigt. Die Datenbanktabellen, Element- und Modultypen bleiben unverändert, die gespeicherten Einstellungen werden auf Swiper-Parameter abgebildet (Tabelle in der README).
* Add: Play/Pause-Schalter, Fortschrittsbalken und -kreis (über das `autoplayTimeLeft`-Ereignis), verzögerter Autoplay-Start, zufälliges Startelement, Vollbild-Hintergrundmodus per CSS `object-fit` und Synchronisierung zweier Karussells (Controller-Modul) als Vanilla-JS-Initialisierer `public/js/caroufredsel.js`.
* Change: Synchronisierte Vorschauleiste auf das Swiper-Thumbs-Modul umgestellt; die Seitenzahlen werden als Links gerendert, damit die mitgelieferten Skins weiter greifen.
* Change: Templates auf Swiper-Markup (`swiper` → `swiper-wrapper` → `swiper-slide`) umgestellt; der Initialisierungs-Aufruf läuft über `TL_BODY` statt `TL_JQUERY`, jQuery muss nicht mehr im Seitenlayout aktiviert sein.
* Change: Felder ohne Swiper-Entsprechung aus den Paletten entfernt (`scrollQueue`, `scrollEasing`, `cookie`, `responsive`, `swipeOnTouch`, `swipeOnMouse`, `autoProgressInterval`); die Datenbankspalten bleiben erhalten. Von den Erweiterungs-Einstellungen bleibt nur `dk_cfsUsageMode`.
* Fix: Die jQuery-Hilfsbibliotheken (carouFredSel, easing, touchSwipe, mousewheel, transit, throttle-debounce, krioImageLoader, readyLoad) wurden entfernt.

## Version 2.0.0 (2026-08-06)

* Add: Portierung der Contao-3-Erweiterung `dklemmt/contao_dk_caroufredsel` (1.3.2) als Contao-Bundle `schachbulle/contao-caroufredsel-bundle` für Contao 4.13 und Contao 5 mit PHP 8.1+.
* Add: Newsticker-Modul wird nur noch registriert, wenn das News-Bundle installiert ist; `contao/news-bundle` ist damit optional.
* Change: Bilderzeugung der Galerie vom entfernten `Controller::addImageToTemplate()` auf den FigureBuilder (`contao.image.studio`) umgestellt; Metadaten, Lightbox und Bildgrößen kommen jetzt aus dem Studio.
* Change: Backend-Erkennung von `TL_MODE` auf den ScopeMatcher umgestellt; `deserialize()`/`specialchars()` durch `StringUtil`, `$GLOBALS['TL_CONFIG']` durch `Config::get()`, `REQUEST_TOKEN` durch den CSRF-Dienst ersetzt (alles in Contao 5 entfernt).
* Change: Dateibaum-Feld sortiert über `isSortable` direkt in `dk_cfsMultiSRC`; das frühere `orderSRC`-Feld entfällt (seit Contao 4.10 nicht mehr vorhanden). Individuelle Sortierungen müssen einmal neu gespeichert werden.
* Change: Doppelte Galerie-Logik von Inhaltselement und Modul in einem gemeinsamen Trait zusammengeführt; Assets liegen jetzt unter `bundles/contaocarousel/`.
* Change: DCA modernisiert (`DC_Table::class`, SVG-Icons, Bildgrößen aus `contao.image.image_sizes` statt `TL_CROP`); die Paletten-Felder `space` und `guests` entfallen.
* Fix: Hintergrund-Slideshow setzte die Elementhöhe fälschlich auf `width: "variable"` statt `height: "variable"`.
* Fix: `runonce.php` der Contao-3-Fassung entfernt; die dort behandelten Migrationsfälle (vor Version 1.2) sind mit Version 1.3.2 abgeschlossen.
