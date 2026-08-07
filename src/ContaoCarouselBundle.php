<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCarouselBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony-Bundle-Klasse des carouFredSel-Karussells.
 *
 * Sie enthält bewusst keine eigene Logik: Contao findet über den Bundle-Pfad
 * die Ressourcen unter src/Resources/contao (Konfiguration, DCA, Sprachdateien,
 * Templates) und src/Resources/public (CSS, JavaScript, Skin-Grafiken).
 */
class ContaoCarouselBundle extends Bundle
{
}
