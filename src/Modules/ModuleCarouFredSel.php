<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCarouselBundle\Modules;

use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoCarouselBundle\Classes\CarouFredSel;

/**
 * Frontend-Modul "caroufredsel".
 *
 * Zeigt die direkt in der Karussell-Konfiguration (Backend-Modul
 * "carouFredSel") gepflegten Inhaltselemente als Karussell an. Jedes
 * Inhaltselement wird zu einer Karussell-Kachel.
 */
class ModuleCarouFredSel extends Module
{
	/**
	 * HTML-Template
	 * @var string
	 */
	protected $strTemplate = 'mod_caroufredsel';

	/**
	 * CSS-Template
	 * @var string
	 */
	protected $strTemplateCss = 'css_caroufredsel';

	/**
	 * JavaScript-Template
	 * @var string
	 */
	protected $strTemplateJs = 'js_caroufredsel';

	/**
	 * Zeigt im Backend einen Platzhalter an und übernimmt im Frontend die
	 * im Backend gewählten Templates.
	 *
	 * @return string Das gerenderte Modul bzw. der Backend-Platzhalter
	 */
	public function generate()
	{
		if (CarouFredSel::isBackend())
		{
			// Backend-Platzhalter für das Karussell-Modul
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CAROUFREDSEL MODUL ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id)));

			return $objTemplate->parse();
		}

		// Gewähltes HTML-Template übernehmen
		if ($this->dk_cfsHtmlTpl)
		{
			$this->strTemplate = $this->dk_cfsHtmlTpl;
		}

		// Gewähltes CSS-Template übernehmen
		if ($this->dk_cfsCssTpl)
		{
			$this->strTemplateCss = $this->dk_cfsCssTpl;
		}

		// Gewähltes JavaScript-Template übernehmen
		if ($this->dk_cfsJsTpl)
		{
			$this->strTemplateJs = $this->dk_cfsJsTpl;
		}

		return parent::generate();
	}

	/**
	 * Erzeugt die Frontend-Ausgabe des Moduls.
	 *
	 * Lädt alle veröffentlichten Inhaltselemente der gewählten
	 * Karussell-Konfiguration, rendert sie einzeln und übergibt sie dem
	 * HTML-Template; CSS- und JavaScript-Blöcke landen als Seiteneffekt in
	 * TL_HEAD bzw. TL_JQUERY.
	 *
	 * @return void
	 */
	protected function compile()
	{
		// Inhaltselemente der Karussell-Konfiguration laden
		$arrElements = array();
		$objCte = ContentModel::findPublishedByPidAndTable((int) $this->dk_cfsCarouFredSel, 'tl_dk_caroufredsel');

		if ($objCte !== null)
		{
			while ($objCte->next())
			{
				$arrElements[] = $this->getContentElement($objCte->current());
			}
		}

		$this->Template->elements = $arrElements;

		// Frontend-Template für den CSS-Block anlegen
		$objTemplateCss = new FrontendTemplate($this->strTemplateCss);
		$objTemplateCss->id = $this->id;

		// Frontend-Template für den JavaScript-Aufruf anlegen; die Modul-ID
		// sorgt für eindeutige HTML-IDs im Markup
		$objTemplateJs = new FrontendTemplate($this->strTemplateJs);
		$objTemplateJs->id = $this->id;

		CarouFredSel::createTemplateData($this->dk_cfsCarouFredSel, $this->type, $this->Template, $objTemplateCss, $objTemplateJs);
	}
}
