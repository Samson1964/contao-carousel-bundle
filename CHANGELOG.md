# Changelog

## Version 2.0.0 (2026-08-06)

* Add: Portierung der Contao-3-Erweiterung `dklemmt/contao_dk_caroufredsel` (1.3.2) als Contao-Bundle `schachbulle/contao-caroufredsel-bundle` für Contao 4.13 und Contao 5 mit PHP 8.1+.
* Add: Newsticker-Modul wird nur noch registriert, wenn das News-Bundle installiert ist; `contao/news-bundle` ist damit optional.
* Change: Bilderzeugung der Galerie vom entfernten `Controller::addImageToTemplate()` auf den FigureBuilder (`contao.image.studio`) umgestellt; Metadaten, Lightbox und Bildgrößen kommen jetzt aus dem Studio.
* Change: Backend-Erkennung von `TL_MODE` auf den ScopeMatcher umgestellt; `deserialize()`/`specialchars()` durch `StringUtil`, `$GLOBALS['TL_CONFIG']` durch `Config::get()`, `REQUEST_TOKEN` durch den CSRF-Dienst ersetzt (alles in Contao 5 entfernt).
* Change: Dateibaum-Feld sortiert über `isSortable` direkt in `dk_cfsMultiSRC`; das frühere `orderSRC`-Feld entfällt (seit Contao 4.10 nicht mehr vorhanden). Individuelle Sortierungen müssen einmal neu gespeichert werden.
* Change: Doppelte Galerie-Logik von Inhaltselement und Modul in einem gemeinsamen Trait zusammengeführt; Assets liegen jetzt unter `bundles/contaocaroufredsel/`.
* Change: DCA modernisiert (`DC_Table::class`, SVG-Icons, Bildgrößen aus `contao.image.image_sizes` statt `TL_CROP`); die Paletten-Felder `space` und `guests` entfallen.
* Fix: Hintergrund-Slideshow setzte die Elementhöhe fälschlich auf `width: "variable"` statt `height: "variable"`.
* Fix: `runonce.php` der Contao-3-Fassung entfernt; die dort behandelten Migrationsfälle (vor Version 1.2) sind mit Version 1.3.2 abgeschlossen.
