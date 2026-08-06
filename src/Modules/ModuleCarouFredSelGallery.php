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
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoCaroufredselBundle\Classes\CarouFredSel;
use Schachbulle\ContaoCaroufredselBundle\Classes\GalleryTrait;

/**
 * Frontend-Modul "caroufredsel_gallery".
 *
 * Zeigt eine Bildergalerie als carouFredSel-Karussell an, wahlweise mit
 * einer zweiten, synchronisierten Vorschauleiste (Thumbnails). Entspricht
 * dem gleichnamigen Inhaltselement, lässt sich aber im Seitenlayout
 * platzieren.
 */
class ModuleCarouFredSelGallery extends Module
{
	use GalleryTrait;

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
	 * Galerie-Template
	 * @var string
	 */
	protected $strTemplateGallery = 'caroufredsel_gallery';

	/**
	 * Zeigt im Backend einen Platzhalter an, bricht ohne Bildauswahl ab und
	 * übernimmt im Frontend die im Backend gewählten Templates.
	 *
	 * @return string Das gerenderte Modul, der Backend-Platzhalter oder ein
	 *                Leerstring wenn keine Dateien ausgewählt sind
	 */
	public function generate()
	{
		if (CarouFredSel::isBackend())
		{
			// Backend-Platzhalter für das Galerie-Modul
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CAROUFREDSEL GALLERY MODULE ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id)));

			return $objTemplate->parse();
		}

		// Abbrechen, wenn keine Dateien ausgewählt sind
		if (!$this->validateGallerySource())
		{
			return '';
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
	 * Erzeugt die Frontend-Ausgabe des Galerie-Moduls.
	 *
	 * Sammelt und sortiert die Bilder, rendert Karussell und optionale
	 * Vorschauleiste und übergibt die Karussell-Konfiguration an die
	 * Helferklasse; CSS- und JavaScript-Blöcke landen als Seiteneffekt in
	 * TL_HEAD bzw. TL_JQUERY.
	 *
	 * @return void
	 */
	protected function compile()
	{
		$images = $this->collectGalleryImages();
		list($body, $bodyThumbnails) = $this->buildGalleryCells($images);

		// Frontend-Template für die Galerie anlegen
		$objTemplate = new FrontendTemplate($this->strTemplateGallery);
		$objTemplate->setData($this->arrData);
		$objTemplate->body = $body;

		$this->Template->images = $objTemplate->parse();

		// Frontend-Template für die Vorschauleiste anlegen
		if ($this->dk_cfsUseThumbnails)
		{
			$objTemplateThumbnails = new FrontendTemplate('caroufredsel_thumbnails');
			$objTemplateThumbnails->id = $this->id;
			$objTemplateThumbnails->bodyThumbnails = $bodyThumbnails;

			$this->Template->thumbnails = $objTemplateThumbnails->parse();
		}

		// Frontend-Template für den CSS-Block anlegen
		$objTemplateCss = new FrontendTemplate($this->strTemplateCss);
		$objTemplateCss->id = $this->id;

		// Frontend-Template für den JavaScript-Aufruf anlegen; die Modul-ID
		// sorgt für eindeutige HTML-IDs im Markup
		$objTemplateJs = new FrontendTemplate($this->strTemplateJs);
		$objTemplateJs->id = $this->id;

		$this->applyThumbnailSettings($objTemplateCss, $objTemplateJs);

		CarouFredSel::createTemplateData($this->dk_cfsCarouFredSel, $this->type, $this->Template, $objTemplateCss, $objTemplateJs);
	}
}
