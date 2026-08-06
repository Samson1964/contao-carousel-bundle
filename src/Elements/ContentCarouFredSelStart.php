<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCaroufredselBundle\Elements;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\FrontendTemplate;
use Schachbulle\ContaoCaroufredselBundle\Classes\CarouFredSel;

/**
 * Inhaltselement "caroufredsel_start" (öffnender Teil des Wrappers).
 *
 * Zusammen mit dem Stop-Element verpackt es beliebige dazwischenliegende
 * Inhaltselemente in ein carouFredSel-Karussell. Jedes direkte Kind-Element
 * wird dabei zu einer Karussell-Kachel.
 */
class ContentCarouFredSelStart extends ContentElement
{
	/**
	 * HTML-Template
	 * @var string
	 */
	protected $strTemplate = 'ce_caroufredsel';

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
	 * Ersetzt die drei Standard-Templates durch die im Backend gewählten,
	 * bevor das Element gerendert wird.
	 *
	 * @return string Das gerenderte Element
	 */
	public function generate()
	{
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
	 * Erzeugt die Frontend-Ausgabe des Start-Elements.
	 *
	 * Im Frontend werden die drei Templates (HTML, CSS, JavaScript) angelegt
	 * und über die Helferklasse mit der Karussell-Konfiguration befüllt; die
	 * CSS- und JavaScript-Blöcke landen dabei als Seiteneffekt in TL_HEAD
	 * bzw. TL_JQUERY. Im Backend erscheint nur ein Platzhalter, weil der
	 * Wrapper dort nicht lauffähig ist.
	 *
	 * @return void
	 */
	protected function compile()
	{
		if (!CarouFredSel::isBackend())
		{
			// Frontend-Template für das Karussell-Element anlegen
			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->setData($this->arrData);
			$this->Template->id = $this->id;

			// Frontend-Template für den CSS-Block anlegen
			$objTemplateCss = new FrontendTemplate($this->strTemplateCss);
			$objTemplateCss->id = $this->id;
			$objTemplateCss->cssIDOnly = $this->cssID[0] ?? '';

			// Frontend-Template für den JavaScript-Aufruf anlegen
			$objTemplateJs = new FrontendTemplate($this->strTemplateJs);
			$objTemplateJs->id = $this->id;
			if ($this->dk_cfsSynchronise)
			{
				$objTemplateJs->synchronise = $this->dk_cfsSynchronise;
			}

			CarouFredSel::createTemplateData($this->dk_cfsCarouFredSel, $this->type, $this->Template, $objTemplateCss, $objTemplateJs);
		}
		else
		{
			$this->strTemplate = 'be_wildcard';
			$this->Template = new BackendTemplate($this->strTemplate);
			$this->Template->title = $this->headline;
		}
	}
}
