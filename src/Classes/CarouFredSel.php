<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCarouselBundle\Classes;

use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Schachbulle\ContaoCarouselBundle\Models\CarouFredSelModel;

/**
 * Zentrale Helferklasse des Karussells.
 *
 * Seit Version 3.0.0 läuft das Frontend nicht mehr über das aufgegebene
 * jQuery-Plugin carouFredSel, sondern über Swiper (contao-components/swiper),
 * das auch der Contao-Core verwendet. Die Klasse übersetzt die unverändert
 * in tl_dk_caroufredsel gespeicherten Einstellungen in Swiper-Parameter und
 * befüllt die drei Frontend-Templates: das HTML-Gerüst, den elementabhängigen
 * CSS-Block und die JSON-Konfiguration für den JavaScript-Initialisierer
 * (public/js/caroufredsel.js). jQuery wird nicht mehr benötigt.
 *
 * Abbildung der wichtigsten Felder auf Swiper:
 * - carouselType circular/infinite → loop, once → kein Loop
 * - scrollItems → slidesPerGroup, scrollDuration → speed
 * - scrollFx fade/crossfade → effect 'fade', alles andere → 'slide'
 * - autoPlay/autoTimeoutDuration → autoplay.delay, scrollPauseOnHover →
 *   autoplay.pauseOnMouseEnter, direction right/down → reverseDirection
 * - itemsVisible → slidesPerView, itemsStart → initialSlide bzw. Zufall
 * - navigation/pagination → eigene Elemente der Templates als prevEl/nextEl
 *   bzw. pagination.el, prevKey/nextKey/paginationKeys → keyboard
 * - mousewheel → mousewheel, Vorschauleiste → Thumbs-Modul,
 *   Synchronisierung → Controller-Modul
 *
 * Ohne Entsprechung in Swiper und deshalb wirkungslos: scrollQueue,
 * scrollEasing, cookie, autoProgressInterval, responsive (Swiper ist immer
 * responsiv) sowie die früheren jQuery-Hilfsoptionen (TriggerMode,
 * ImageLoader, Transition, OnWindowResize, Debug).
 */
class CarouFredSel
{
	/**
	 * Stellt fest, ob die aktuelle Anfrage zum Contao-Backend gehört.
	 *
	 * Ersetzt die frühere Konstante TL_MODE, die es in Contao 5 nicht mehr
	 * gibt. Ohne aktuelle Anfrage (etwa auf der Kommandozeile) wird das
	 * Frontend angenommen, damit keine Backend-Platzhalter erzeugt werden.
	 *
	 * @return bool true im Backend, sonst false
	 */
	public static function isBackend(): bool
	{
		$container = System::getContainer();
		$request = $container->get('request_stack')->getCurrentRequest();

		return $request !== null && $container->get('contao.routing.scope_matcher')->isBackendRequest($request);
	}

	/**
	 * Befüllt die drei Frontend-Templates eines Karussell-Elements und
	 * registriert die Swiper-Assets.
	 *
	 * Als Grundverhalten gelten die Vorgaben des alten carouFredSel-Plugins
	 * (Endlosschleife, automatisches Abspielen alle 2500 ms, 500 ms
	 * Übergangsdauer); die im Datensatz aktivierten Gruppen überschreiben
	 * sie. Als Seiteneffekt füllt die Methode die Globals TL_CSS,
	 * TL_JAVASCRIPT (Swiper-Bibliothek und Initialisierer im Seitenkopf),
	 * TL_HEAD (elementabhängiger CSS-Block) und TL_BODY (Aufruf des
	 * Initialisierers am Seitenende, wenn das DOM steht).
	 *
	 * @param mixed    $carouFredSelId      ID des Datensatzes in tl_dk_caroufredsel
	 * @param string   $strCarouFredSelType Element- bzw. Modultyp (z. B. 'caroufredsel_gallery');
	 *                                      steuert typabhängige Sonderfälle wie die Hintergrund-Slideshow
	 * @param Template $objTemplateHtml     Template für das HTML-Gerüst
	 * @param Template $objTemplateCss      Template für den elementabhängigen CSS-Block (landet in TL_HEAD)
	 * @param Template $objTemplateJs       Template für den Initialisierungs-Aufruf (landet in TL_BODY);
	 *                                      erwartet vorab gesetzte Werte wie id, synchronise und die
	 *                                      Thumbnail-Angaben aus applyThumbnailSettings()
	 *
	 * @return void Ohne gültige Konfiguration kehrt die Methode kommentarlos
	 *              zurück und lässt die Templates unverändert
	 */
	public static function createTemplateData($carouFredSelId, string $strCarouFredSelType, Template $objTemplateHtml, Template $objTemplateCss, Template $objTemplateJs): void
	{
		$objCarouFredSel = CarouFredSelModel::findByPk($carouFredSelId);
		if ($objCarouFredSel === null)
		{
			return;
		}

		// Elementtyp in alle drei Templates übernehmen
		$objTemplateHtml->type =
		$objTemplateJs->type =
		$objTemplateCss->type = $strCarouFredSelType;

		// Grundverhalten des alten carouFredSel-Plugins nachbilden
		$options = array
		(
			'loop' => true,
			'speed' => 500,
			'autoplay' => array('delay' => 2500),
		);

		// --- Abspielverhalten
		if ($objCarouFredSel->usePlay)
		{
			// Laufrichtung: up/down → vertikales Karussell; right/down laufen
			// rückwärts, das bildet autoplay.reverseDirection ab
			if ($objCarouFredSel->direction == 'up' || $objCarouFredSel->direction == 'down')
			{
				$options['direction'] = 'vertical';
			}
			if ($objCarouFredSel->direction == 'right' || $objCarouFredSel->direction == 'down')
			{
				$options['autoplay']['reverseDirection'] = true;
			}
			if ($objCarouFredSel->direction != 'left')
			{
				$objTemplateCss->direction = $objCarouFredSel->direction;
			}

			// 'once': einmal bis zum Ende, keine Endlosschleife
			if ($objCarouFredSel->carouselType == 'once')
			{
				$options['loop'] = false;
			}

			// Anzahl der Elemente je Weiterschaltung
			if ($objCarouFredSel->scrollItems > 0)
			{
				$options['slidesPerGroup'] = (int) $objCarouFredSel->scrollItems;
			}

			if (!$objCarouFredSel->autoPlay)
			{
				$options['autoplay'] = false;
			}
			else
			{
				$options['autoplay']['delay'] = (int) $objCarouFredSel->autoTimeoutDuration;

				// Anhalten, solange der Zeiger über dem Karussell steht; die
				// carouFredSel-Nuancen (restart/resume/immediate) kennt Swiper
				// nicht und sie fallen auf pauseOnMouseEnter zusammen
				if ($objCarouFredSel->scrollPauseOnHover != 'none' && $objCarouFredSel->scrollPauseOnHover != '')
				{
					$options['autoplay']['pauseOnMouseEnter'] = true;
					$options['autoplay']['disableOnInteraction'] = false;
				}

				// Fortschrittsanzeige (Balken oder Kreis) für Templates und Initialisierer
				if ($objCarouFredSel->autoProgress != 'none' && $objCarouFredSel->autoProgress != '')
				{
					$objTemplateHtml->autoProgress =
					$objTemplateJs->autoProgress =
					$objTemplateCss->autoProgress = $objCarouFredSel->autoProgress;
				}
			}
		}

		// --- Übergänge
		if ($objCarouFredSel->useTransitions)
		{
			// Nur die Fade-Effekte haben eine Swiper-Entsprechung; alle
			// übrigen carouFredSel-Effekte fallen auf 'slide' zurück
			if ($objCarouFredSel->scrollFx == 'fade' || $objCarouFredSel->scrollFx == 'crossfade')
			{
				$options['effect'] = 'fade';
				$options['fadeEffect'] = array('crossFade' => $objCarouFredSel->scrollFx == 'crossfade');
			}

			if ($objCarouFredSel->scrollDuration > 0)
			{
				$options['speed'] = (int) $objCarouFredSel->scrollDuration;
			}
		}

		// --- Gesamtgröße: wird vollständig über CSS gelöst (Swiper misst
		// selbst); der Innenabstand wird zum Abstand zwischen den Kacheln
		if ($objCarouFredSel->useGeneralSize)
		{
			switch ($objCarouFredSel->widthSelect)
			{
				case 'fixed':
				case 'fluid':
					$width = StringUtil::deserialize($objCarouFredSel->width, true);
					if (isset($width['value']) && $width['value'] != '')
					{
						$unit = $width['unit'] ?? ($objCarouFredSel->widthSelect == 'fluid' ? '%' : 'px');
						$objTemplateCss->width = sprintf('width: %s%s;', $width['value'], $unit);
						$objTemplateCss->widthValue = $width['value'];
						$objTemplateCss->widthUnit = $unit;
					}
					break;
			}

			switch ($objCarouFredSel->heightSelect)
			{
				case 'fixed':
				case 'fluid':
					$height = StringUtil::deserialize($objCarouFredSel->height, true);
					if (isset($height['value']) && $height['value'] != '')
					{
						$unit = $height['unit'] ?? ($objCarouFredSel->heightSelect == 'fluid' ? '%' : 'px');
						$objTemplateCss->height = sprintf('height: %s%s;', $height['value'], $unit);
					}
					break;
			}

			// Innenabstand: carouFredSel kannte ein Padding rund um die
			// Kacheln; das nächstliegende Swiper-Konzept ist der Abstand
			// zwischen den Kacheln (spaceBetween). Verwendet wird der Wert
			// in Laufrichtung (horizontal: rechts, vertikal: unten).
			$padding = StringUtil::deserialize($objCarouFredSel->padding, true);
			if (!empty($padding['unit']))
			{
				$space = ($options['direction'] ?? 'horizontal') == 'vertical'
					? ($padding['bottom'] ?? '')
					: ($padding['right'] ?? '');

				if ($space !== '' && (int) $space > 0)
				{
					$options['spaceBetween'] = (int) $space;
				}
			}

			// Ausrichtung des Karussells bei fester Breite
			if ($objCarouFredSel->widthSelect == 'fixed' && $objCarouFredSel->align != '' && $objCarouFredSel->align != 'none')
			{
				$objTemplateCss->align = $objCarouFredSel->align;
			}
		}

		// --- Elementgröße: feste oder prozentuale Kachelmaße laufen über
		// CSS; Swiper braucht dafür slidesPerView 'auto'
		if ($objCarouFredSel->useItemsSize)
		{
			switch ($objCarouFredSel->itemsWidthSelect)
			{
				case 'variable':
					$options['slidesPerView'] = 'auto';
					break;

				case 'fixed':
				case 'fluid':
					$itemsWidth = StringUtil::deserialize($objCarouFredSel->itemsWidth, true);
					if (isset($itemsWidth['value']) && $itemsWidth['value'] != '')
					{
						$unit = $itemsWidth['unit'] ?? ($objCarouFredSel->itemsWidthSelect == 'fluid' ? '%' : 'px');
						$objTemplateCss->itemsWidth = sprintf('width: %s%s;', $itemsWidth['value'], $unit);
						$options['slidesPerView'] = 'auto';
					}
					break;
			}

			switch ($objCarouFredSel->itemsHeightSelect)
			{
				case 'fixed':
				case 'fluid':
					$itemsHeight = StringUtil::deserialize($objCarouFredSel->itemsHeight, true);
					if (isset($itemsHeight['value']) && $itemsHeight['value'] != '')
					{
						$unit = $itemsHeight['unit'] ?? ($objCarouFredSel->itemsHeightSelect == 'fluid' ? '%' : 'px');
						$objTemplateCss->itemsHeight = sprintf('height: %s%s;', $itemsHeight['value'], $unit);
					}
					break;
			}
		}

		// --- Allgemeine Element-Einstellungen
		if ($objCarouFredSel->useItemsGeneral)
		{
			// Anzahl sichtbarer Elemente
			switch ($objCarouFredSel->itemsVisibleSelect)
			{
				case 'variable':
				case 'min/max':
					// Ohne feste Anzahl richtet sich die Sichtbarkeit nach
					// der Kachelbreite; die min/max-Grenzen des alten
					// Plugins kennt Swiper nicht
					$options['slidesPerView'] = 'auto';
					break;

				case 'fixed':
					if ($objCarouFredSel->itemsVisible > 0)
					{
						$options['slidesPerView'] = (int) $objCarouFredSel->itemsVisible;
						$objTemplateCss->itemsVisible = $objCarouFredSel->itemsVisible;
					}
					break;
			}

			// Startelement
			switch ($objCarouFredSel->itemsStartSelect)
			{
				case 'number':
					if ($objCarouFredSel->itemsStart > 0)
					{
						$options['initialSlide'] = (int) $objCarouFredSel->itemsStart;
					}
					break;

				case 'random':
					// Wird im Initialisierer ausgewürfelt, weil die
					// Kachelzahl erst im Browser feststeht
					$objTemplateJs->randomStart = true;
					break;
			}
		}

		// --- Navigation
		$autoButton = false;

		if ($objCarouFredSel->useNavigation)
		{
			// Pfeiltasten-Steuerung (die konkrete Tastenwahl des alten
			// Plugins kennt Swiper nicht, es nutzt immer die Pfeiltasten)
			if (($objCarouFredSel->prevKey != 'none' && $objCarouFredSel->prevKey != '')
				|| ($objCarouFredSel->nextKey != 'none' && $objCarouFredSel->nextKey != '')
				|| $objCarouFredSel->paginationKeys)
			{
				$options['keyboard'] = array('enabled' => true);
			}

			// Mausrad-Steuerung
			if ($objCarouFredSel->mousewheel)
			{
				$options['mousewheel'] = true;
			}

			// Vor-/Zurück-Pfeile: die eigenen Elemente der Templates werden
			// an Swiper übergeben, damit die mitgelieferten Skins samt
			// disabled-Zustand weiter funktionieren
			if ($objCarouFredSel->navigation)
			{
				$objTemplateHtml->navigation =
				$objTemplateJs->navigation =
				$objTemplateCss->navigation = $objCarouFredSel->navigation;

				$options['navigation'] = array
				(
					'prevEl' => '#caroufredsel_prev_' . $objTemplateJs->id,
					'nextEl' => '#caroufredsel_next_' . $objTemplateJs->id,
					'disabledClass' => 'disabled',
				);

				// Play/Pause-Schalter nur bei automatischem Abspielen
				if ($objCarouFredSel->autoPlay)
				{
					$autoButton = (bool) $objCarouFredSel->autoButton;
					$objTemplateHtml->autoButton =
					$objTemplateJs->autoButton =
					$objTemplateCss->autoButton = $objCarouFredSel->autoButton;
				}
			}

			// Seitenzahlen-Navigation im eigenen Container der Templates
			if ($objCarouFredSel->pagination)
			{
				$objTemplateHtml->pagination =
				$objTemplateJs->pagination =
				$objTemplateCss->pagination = $objCarouFredSel->pagination;

				$options['pagination'] = array
				(
					'el' => '#caroufredsel_pagi_' . $objTemplateJs->id,
					'clickable' => true,
					'bulletActiveClass' => 'selected',
				);
			}
		}

		// Typabhängige Vorgaben der Hintergrund-Slideshow: Sie füllt immer
		// das gesamte Browserfenster (CSS in caroufredsel.css), zeigt genau
		// ein Bild und läuft endlos
		if ($strCarouFredSelType == 'caroufredsel_background')
		{
			$options['slidesPerView'] = 1;
			$options['loop'] = true;
			$objTemplateJs->background = true;
		}

		// Vorschauleiste: Werte stammen aus applyThumbnailSettings() der
		// Elemente/Module; die Leiste wird ein eigener Swiper, den das
		// Thumbs-Modul mit dem Hauptkarussell koppelt
		$thumbs = null;
		if ($objTemplateJs->useThumbnails)
		{
			$thumbs = array
			(
				'el' => '#caroufredsel_thumbnails_' . $objTemplateJs->id,
				'prevEl' => '#caroufredsel_thumbnails_prev_' . $objTemplateJs->id,
				'nextEl' => '#caroufredsel_thumbnails_next_' . $objTemplateJs->id,
				'vertical' => \in_array($objTemplateJs->thumbnailsPosition, array('left', 'right'), true),
				'visible' => $objTemplateJs->thumbnailsVisible ? (int) $objTemplateJs->thumbnailsVisible : null,
			);
		}

		// Gesamtkonfiguration für den Initialisierer (caroufredsel.js)
		$config = array
		(
			'id' => (string) $objTemplateJs->id,
			'options' => $options,
			'autoButton' => $autoButton,
			'autoProgress' => $objTemplateJs->autoProgress ?: null,
			'randomStart' => (bool) $objTemplateJs->randomStart,
			'autoDelay' => ($objCarouFredSel->usePlay && $objCarouFredSel->autoPlay) ? (int) $objCarouFredSel->autoDelay : 0,
			'sync' => $objTemplateJs->synchronise ? (string) $objTemplateJs->synchronise : null,
			'background' => (bool) $objTemplateJs->background,
			'thumbs' => $thumbs,
		);

		$objTemplateJs->config = json_encode($config, JSON_UNESCAPED_SLASHES);

		// Assets registrieren:

		// ... Swiper-Bibliothek (contao-components/swiper) und Initialisierer
		$GLOBALS['TL_CSS'][] = 'assets/swiper/css/swiper-bundle.min.css|static';
		$GLOBALS['TL_JAVASCRIPT'][] = 'assets/swiper/js/swiper-bundle.min.js|static';
		$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocarousel/js/caroufredsel.js|static';

		// ... globale CSS-Datei des Karussells
		$GLOBALS['TL_CSS'][] = 'bundles/contaocarousel/css/caroufredsel.css||static';

		// ... elementabhängiger CSS-Block im Seitenkopf
		$GLOBALS['TL_HEAD'][] = $objTemplateCss->parse();

		// ... Initialisierungs-Aufruf am Seitenende (DOM steht dann);
		// jQuery wird seit Version 3.0.0 nicht mehr benötigt
		$GLOBALS['TL_BODY'][] = $objTemplateJs->parse();
	}

	/**
	 * Befüllt das HTML-Template des Stop-Elements mit den Anzeigedaten des
	 * zugehörigen Start-Elements.
	 *
	 * Das Stop-Element rendert den schließenden Teil des Wrappers samt
	 * Navigation, Fortschrittsanzeige und Seitenzahlen. Welche dieser
	 * Bausteine erscheinen, entscheidet die Karussell-Konfiguration des
	 * Start-Elements — deshalb wird sie hier erneut ausgewertet.
	 *
	 * @param mixed    $carouFredSelId  ID des Datensatzes in tl_dk_caroufredsel
	 * @param Template $objTemplateHtml Template des Stop-Elements
	 *
	 * @return void Ohne gültige Konfiguration kehrt die Methode kommentarlos zurück
	 */
	public static function createTemplateDataStopElement($carouFredSelId, Template $objTemplateHtml): void
	{
		$objCarouFredSel = CarouFredSelModel::findByPk($carouFredSelId);
		if ($objCarouFredSel === null)
		{
			return;
		}

		// --- Abspielverhalten
		if ($objCarouFredSel->usePlay && $objCarouFredSel->autoPlay && $objCarouFredSel->autoProgress != 'none' && $objCarouFredSel->autoProgress != '')
		{
			$objTemplateHtml->autoProgress = $objCarouFredSel->autoProgress;
		}

		// --- Navigation
		if ($objCarouFredSel->useNavigation)
		{
			if ($objCarouFredSel->navigation)
			{
				$objTemplateHtml->navigation = $objCarouFredSel->navigation;
				if ($objCarouFredSel->autoPlay)
				{
					$objTemplateHtml->autoButton = $objCarouFredSel->autoButton;
				}
			}

			if ($objCarouFredSel->pagination)
			{
				$objTemplateHtml->pagination = $objCarouFredSel->pagination;
			}
		}
	}
}
