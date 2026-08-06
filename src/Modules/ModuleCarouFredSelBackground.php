<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCaroufredselBundle\Modules;

/**
 * Frontend-Modul "caroufredsel_background".
 *
 * Vollbild-Hintergrund-Slideshow: verhält sich wie das Galerie-Modul, die
 * Helferklasse überschreibt aber Größe, Ausrichtung und Sichtbarkeit so,
 * dass das Karussell das gesamte Browserfenster füllt (siehe
 * CarouFredSel::createTemplateData() und das JavaScript-Template).
 */
class ModuleCarouFredSelBackground extends ModuleCarouFredSelGallery
{
}
