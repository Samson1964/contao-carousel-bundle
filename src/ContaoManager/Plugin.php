<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCaroufredselBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoCaroufredselBundle\ContaoCaroufredselBundle;

/**
 * Meldet die Erweiterung beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Nennt das Bundle und seine Ladereihenfolge.
	 *
	 * Das Karussell wird nach dem Contao-Kern und — sofern vorhanden — nach dem
	 * News-Bundle geladen: Es ergänzt die Kern-Tabellen tl_content und tl_module,
	 * und das Newsticker-Modul erweitert die Nachrichtenliste. Beides setzt
	 * voraus, dass die Grundfassungen bereits registriert sind. Das News-Bundle
	 * ist als optionale Abhängigkeit eingetragen, damit die Installation auch
	 * ohne Nachrichten funktioniert.
	 *
	 * @param ParserInterface $parser Vom Manager gestellter Parser; wird hier
	 *                                nicht gebraucht, gehört aber zur Schnittstelle
	 *
	 * @return array<BundleConfig> Liste mit der Bundle-Beschreibung
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return [
			BundleConfig::create(ContaoCaroufredselBundle::class)
				->setLoadAfter([ContaoCoreBundle::class, 'Contao\NewsBundle\ContaoNewsBundle']),
		];
	}
}
