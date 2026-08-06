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

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\ModuleNewsList;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoCaroufredselBundle\Classes\CarouFredSel;

/**
 * Frontend-Modul "caroufredsel_ticker".
 *
 * Newsticker auf Basis der Nachrichtenliste: Die Beiträge der gewählten
 * Nachrichtenarchive laufen als carouFredSel-Karussell durch. Setzt das
 * News-Bundle voraus; ohne dieses wird das Modul in config.php gar nicht
 * erst registriert.
 */
class ModuleCarouFredSelTicker extends ModuleNewsList
{
	/**
	 * HTML-Template
	 * @var string
	 */
	protected $strTemplate = 'mod_caroufredsel_ticker';

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
			// Backend-Platzhalter für das Newsticker-Modul
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CAROUFREDSEL NEWSTICKER ###';
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
	 * Erzeugt die Frontend-Ausgabe des Newstickers.
	 *
	 * Die Nachrichtenliste selbst baut die Elternklasse auf; hier kommen nur
	 * die Karussell-Templates hinzu. CSS- und JavaScript-Blöcke landen als
	 * Seiteneffekt in TL_HEAD bzw. TL_JQUERY.
	 *
	 * @return void
	 */
	protected function compile()
	{
		parent::compile();

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
