<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCarouselBundle\Elements;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\System;
use Schachbulle\ContaoCarouselBundle\Classes\CarouFredSel;

/**
 * Inhaltselement "caroufredsel_stop" (schließender Teil des Wrappers).
 *
 * Rendert den schließenden Teil des Karussells samt Navigation,
 * Fortschrittsanzeige und Seitenzahlen. Die Einstellungen dafür stammen
 * aus dem zugehörigen Start-Element, das über die Sortierreihenfolge
 * gefunden wird.
 */
class ContentCarouFredSelStop extends ContentElement
{
	/**
	 * HTML-Template (dasselbe wie beim Start-Element)
	 * @var string
	 */
	protected $strTemplate = 'ce_caroufredsel';

	/**
	 * Erzeugt die Frontend-Ausgabe des Stop-Elements.
	 *
	 * Gesucht wird das nächste sichtbare Start-Element oberhalb der eigenen
	 * Sortierposition im selben Artikel. Fehlt es, wird nichts ausgegeben
	 * und ein Fehler protokolliert — ein Stop ohne Start ist ein
	 * Pflegefehler im Backend. Im Backend erscheint nur ein Platzhalter.
	 *
	 * @return void
	 */
	protected function compile()
	{
		if (!CarouFredSel::isBackend())
		{
			// Erstes sichtbares Start-Element vor diesem Stop-Element suchen
			$objStartElement = Database::getInstance()
				->prepare("SELECT id, dk_cfsCarouFredSel, dk_cfsHtmlTpl
						   FROM   tl_content
						   WHERE  type = 'caroufredsel_start' AND pid = ? AND sorting < ? AND invisible != '1'
						   ORDER  by sorting DESC")
				->limit(1)
				->execute($this->pid, $this->sorting);

			if ($objStartElement->numRows < 1)
			{
				System::getContainer()->get('monolog.logger.contao.error')
					->error('carouFredSel: Zum Stop-Element ID ' . $this->id . ' fehlt das zugehörige Start-Element');

				return;
			}

			// HTML-Template des Start-Elements übernehmen, damit beide Teile
			// des Wrappers dieselbe Struktur verwenden
			if ($objStartElement->dk_cfsHtmlTpl)
			{
				$this->strTemplate = $objStartElement->dk_cfsHtmlTpl;
			}

			// Frontend-Template für das Karussell-Element anlegen
			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->type = $this->type;
			$this->Template->id = $objStartElement->id;

			CarouFredSel::createTemplateDataStopElement($objStartElement->dk_cfsCarouFredSel, $this->Template);
		}
		else
		{
			$this->strTemplate = 'be_wildcard';
			$this->Template = new BackendTemplate($this->strTemplate);
		}
	}
}
