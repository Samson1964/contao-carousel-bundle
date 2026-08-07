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
use Contao\FrontendTemplate;
use Schachbulle\ContaoCarouselBundle\Classes\CarouFredSel;
use Schachbulle\ContaoCarouselBundle\Classes\GalleryTrait;

/**
 * Inhaltselement "caroufredsel_gallery".
 *
 * Zeigt eine Bildergalerie als carouFredSel-Karussell an, wahlweise mit
 * einer zweiten, synchronisierten Vorschauleiste (Thumbnails). Die Bilder
 * stammen aus einzeln ausgewählten Dateien oder ganzen Ordnern der
 * Dateiverwaltung.
 */
class ContentCarouFredSelGallery extends ContentElement
{
	use GalleryTrait;

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
	 * Galerie-Template
	 * @var string
	 */
	protected $strTemplateGallery = 'caroufredsel_gallery';

	/**
	 * Bricht ohne Bildauswahl ab und übernimmt die im Backend gewählten
	 * Templates, bevor das Element gerendert wird.
	 *
	 * @return string Das gerenderte Element, oder ein Leerstring wenn keine
	 *                Dateien ausgewählt sind
	 */
	public function generate()
	{
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
	 * Erzeugt die Ausgabe des Galerie-Elements.
	 *
	 * Im Frontend werden die Bilder gesammelt, sortiert und als Karussell
	 * samt optionaler Vorschauleiste aufbereitet; CSS- und JavaScript-Blöcke
	 * landen als Seiteneffekt in TL_HEAD bzw. TL_JQUERY. Im Backend zeigt
	 * das Element eine verkleinerte Bildvorschau über das Template
	 * be_caroufredsel.
	 *
	 * @return void
	 */
	protected function compile()
	{
		$blnBackend = CarouFredSel::isBackend();

		$images = $this->collectGalleryImages();
		list($body, $bodyThumbnails) = $this->buildGalleryCells($images, $blnBackend);

		// Frontend-Template für die Galerie anlegen
		$objTemplate = new FrontendTemplate($this->strTemplateGallery);
		$objTemplate->setData($this->arrData);
		$objTemplate->body = $body;

		if (!$blnBackend)
		{
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

			// Frontend-Template für den JavaScript-Aufruf anlegen; die
			// Element-ID sorgt für eindeutige HTML-IDs im Markup
			$objTemplateJs = new FrontendTemplate($this->strTemplateJs);
			$objTemplateJs->id = $this->id;

			$this->applyThumbnailSettings($objTemplateCss, $objTemplateJs);

			CarouFredSel::createTemplateData($this->dk_cfsCarouFredSel, $this->type, $this->Template, $objTemplateCss, $objTemplateJs);
		}
		else
		{
			// Bildvorschau im Backend
			$this->strTemplate = 'be_caroufredsel';
			$this->Template = new BackendTemplate($this->strTemplate);
			$this->Template->images = $objTemplate->parse();

			// CSS des Karussells auch im Backend einbinden
			$GLOBALS['TL_CSS'][] = 'bundles/contaocarousel/css/caroufredsel.css';
		}
	}
}
