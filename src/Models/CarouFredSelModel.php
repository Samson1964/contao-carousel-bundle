<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCaroufredselBundle\Models;

use Contao\Model;

/**
 * Model für die Karussell-Konfigurationen in tl_dk_caroufredsel.
 *
 * Der Tabellenname behält das dk_-Präfix der Contao-3-Erweiterung, damit
 * bestehende Daten einer umgestellten Installation ohne Migration
 * weiterverwendet werden können.
 */
class CarouFredSelModel extends Model
{
	/**
	 * Name der zugehörigen Datenbanktabelle
	 * @var string
	 */
	protected static $strTable = 'tl_dk_caroufredsel';
}
