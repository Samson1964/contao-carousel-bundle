<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCaroufredselBundle\Classes;

use Contao\Config;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Schachbulle\ContaoCaroufredselBundle\Models\CarouFredSelModel;

/**
 * Zentrale Helferklasse des Karussells.
 *
 * Sie übersetzt eine im Backend gepflegte Karussell-Konfiguration
 * (tl_dk_caroufredsel) in die drei Frontend-Templates: das HTML-Gerüst,
 * den elementabhängigen CSS-Block und den JavaScript-Aufruf von
 * jQuery.carouFredSel. Außerdem registriert sie alle benötigten Assets
 * (CSS- und JavaScript-Dateien) über die Contao-Globals.
 *
 * Anders als in der Contao-3-Fassung ist die Klasse bewusst statisch und
 * erbt nicht mehr von Frontend: Sie hält keinen Zustand und braucht keine
 * Contao-Altlasten wie den geschützten System-Konstruktor.
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
	 * registriert alle benötigten Assets.
	 *
	 * Es werden nur Werte in die Templates geschrieben, die vom
	 * carouFredSel-Standard abweichen — der JavaScript-Aufruf bleibt dadurch
	 * so kurz wie möglich. Als Seiteneffekt füllt die Methode die Globals
	 * TL_CSS, TL_HEAD, TL_JAVASCRIPT und TL_JQUERY; der TL_JQUERY-Block wird
	 * nur ausgegeben, wenn jQuery im Seitenlayout aktiviert ist.
	 *
	 * @param mixed    $carouFredSelId      ID des Datensatzes in tl_dk_caroufredsel
	 * @param string   $strCarouFredSelType Element- bzw. Modultyp (z. B. 'caroufredsel_gallery');
	 *                                      steuert typabhängige Sonderfälle wie die Hintergrund-Slideshow
	 * @param Template $objTemplateHtml     Template für das HTML-Gerüst
	 * @param Template $objTemplateCss      Template für den elementabhängigen CSS-Block (landet in TL_HEAD)
	 * @param Template $objTemplateJs       Template für den JavaScript-Aufruf (landet in TL_JQUERY)
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

		if ($objTemplateJs->synchronise)
		{
			$objTemplateJs->synchronise = 'synchronise : ["#caroufredsel_' . $objTemplateJs->synchronise . '", false]';
		}

		// --- Abspielverhalten
		if ($objCarouFredSel->usePlay)
		{
			// carouFredSel-Option 'direction': Standardwert ist 'left'
			if ($objCarouFredSel->direction != 'left')
			{
				$objTemplateJs->direction = 'direction: "' . $objCarouFredSel->direction . '"';
				$objTemplateCss->direction = $objCarouFredSel->direction;
			}

			// carouFredSel-Optionen 'circular' und 'infinite': Vorgabe der Erweiterung ist 'circular'
			if ($objCarouFredSel->carouselType == 'once')
			{
				$objTemplateJs->carouselType = 'circular: false, infinite: false';
			}
			elseif ($objCarouFredSel->carouselType == 'infinite')
			{
				$objTemplateJs->carouselType = 'circular: false';
			}

			// carouFredSel-Option 'scroll.items': Standardwert ist 'null'
			if ($objCarouFredSel->scrollItems != '0')
			{
				$objTemplateJs->scrollItems = 'items: ' . $objCarouFredSel->scrollItems;
			}

			// carouFredSel-Option 'scroll.queue': Standardwert ist 'false'
			if ($objCarouFredSel->scrollQueue != 'none')
			{
				$objTemplateJs->scrollQueue = 'queue: ' . ($objCarouFredSel->scrollQueue == 'all' ? 'true' : '"' . $objCarouFredSel->scrollQueue . '"');
			}

			if (!$objCarouFredSel->autoPlay)
			{
				$objTemplateJs->autoPlay = 'auto: false';
			}

			if ($objCarouFredSel->autoPlay)
			{
				// carouFredSel-Option 'auto.timeoutDuration': Standardwert ist '2500'
				if ($objCarouFredSel->autoTimeoutDuration != '2500')
				{
					$objTemplateJs->autoTimeoutDuration = 'timeoutDuration: ' . $objCarouFredSel->autoTimeoutDuration;
				}

				// carouFredSel-Option 'auto.delay': Standardwert ist '0'
				if ($objCarouFredSel->autoDelay != '0')
				{
					$objTemplateJs->autoDelay = 'delay: ' . $objCarouFredSel->autoDelay;
				}

				// carouFredSel-Option 'scroll.pauseOnHover': Standardwert ist 'false'
				if ($objCarouFredSel->scrollPauseOnHover != 'none')
				{
					$objTemplateJs->scrollPauseOnHover = 'pauseOnHover: ' . ($objCarouFredSel->scrollPauseOnHover == 'restart' ? 'true' : '"' . $objCarouFredSel->scrollPauseOnHover . '"');
				}

				// carouFredSel-Option 'auto.progress': Standardwert ist 'null'
				if ($objCarouFredSel->autoProgress != 'none')
				{
					$objTemplateHtml->autoProgress =
					$objTemplateJs->autoProgress =
					$objTemplateCss->autoProgress = $objCarouFredSel->autoProgress;

					// carouFredSel-Option 'auto.progress.interval': Standardwert ist '50'
					if ($objCarouFredSel->autoProgressInterval != '50')
					{
						$objTemplateJs->autoProgressInterval = 'interval: ' . $objCarouFredSel->autoProgressInterval;
					}
				}
			}
		}

		// --- Übergänge
		if ($objCarouFredSel->useTransitions)
		{
			// carouFredSel-Option 'scroll.fx': Standardwert ist 'scroll'
			if ($objCarouFredSel->scrollFx != 'scroll')
			{
				$objTemplateJs->scrollFx = 'fx: "' . $objCarouFredSel->scrollFx . '"';
			}

			// carouFredSel-Option 'scroll.easing': Standardwert ist 'swing'
			if ($objCarouFredSel->scrollEasing != 'swing')
			{
				$objTemplateJs->scrollEasing = 'easing: "' . $objCarouFredSel->scrollEasing . '"';
			}

			// carouFredSel-Option 'scroll.duration': Standardwert ist '500'
			if ($objCarouFredSel->scrollDuration != '500')
			{
				$objTemplateJs->scrollDuration = 'duration: ' . $objCarouFredSel->scrollDuration;
			}
		}

		// --- Gesamtgröße
		if ($objCarouFredSel->useGeneralSize)
		{
			$objTemplateJs->widthSelect = $objCarouFredSel->widthSelect;
			$objTemplateJs->heightSelect = $objCarouFredSel->heightSelect;

			// carouFredSel-Option 'width': Standardwert ist 'null'
			switch ($objCarouFredSel->widthSelect)
			{
				case 'variable':
					$objTemplateJs->width = 'width: "variable"';
					break;

				case 'auto':
					$objTemplateJs->width = 'width: "auto"';
					break;

				case 'fixed':
					$width = StringUtil::deserialize($objCarouFredSel->width, true);
					if (isset($width['value']) && $width['value'] != '')
					{
						$objTemplateJs->width = 'width: ' . $width['value'];
						$objTemplateCss->width = $objTemplateJs->width . ($width['unit'] ?? 'px') . ';';
						$objTemplateCss->widthValue = $width['value'];
						$objTemplateCss->widthUnit = $width['unit'] ?? 'px';
					}
					break;

				case 'fluid':
					$width = StringUtil::deserialize($objCarouFredSel->width, true);
					if (isset($width['value']) && $width['value'] != '')
					{
						$objTemplateJs->width = sprintf('width: "%s%s"', $width['value'], $width['unit'] ?? '%');
						$objTemplateCss->width = sprintf('width: %s%s;', $width['value'], $width['unit'] ?? '%');
						$objTemplateCss->widthValue = $width['value'];
						$objTemplateCss->widthUnit = $width['unit'] ?? '%';
					}
					break;
			}

			// carouFredSel-Option 'height': Standardwert ist 'null'
			switch ($objCarouFredSel->heightSelect)
			{
				case 'variable':
					$objTemplateJs->height = 'height: "variable"';
					break;

				case 'auto':
					$objTemplateJs->height = 'height: "auto"';
					break;

				case 'fixed':
					$height = StringUtil::deserialize($objCarouFredSel->height, true);
					if (isset($height['value']) && $height['value'] != '')
					{
						$objTemplateJs->height = 'height: ' . $height['value'];
						$objTemplateCss->height = $objTemplateJs->height . ($height['unit'] ?? 'px') . ';';
					}
					break;

				case 'fluid':
					$height = StringUtil::deserialize($objCarouFredSel->height, true);
					if (isset($height['value']) && $height['value'] != '')
					{
						$objTemplateJs->height = sprintf('height: "%s%s"', $height['value'], $height['unit'] ?? '%');
						$objTemplateCss->height = sprintf('height: %s%s;', $height['value'], $height['unit'] ?? '%');
					}
					break;
			}

			// carouFredSel-Option 'padding': Standardwert ist 'null'.
			// Die vier Richtungswerte werden wie in der CSS-Kurzschreibweise
			// zusammengefasst, damit der JavaScript-Aufruf kompakt bleibt.
			$padding = StringUtil::deserialize($objCarouFredSel->padding, true);
			if (!empty($padding['unit']))
			{
				$paddingTop = (!empty($padding['top']) ? $padding['top'] : '0');
				$paddingRight = (!empty($padding['right']) ? $padding['right'] : '0');
				$paddingBottom = (!empty($padding['bottom']) ? $padding['bottom'] : '0');
				$paddingLeft = (!empty($padding['left']) ? $padding['left'] : '0');

				if (($paddingTop == $paddingRight) && ($paddingTop == $paddingBottom) && ($paddingTop == $paddingLeft))
				{
					if ($paddingTop != '0')
					{
						$objTemplateJs->padding = 'padding: ' . $paddingTop;
					}
				}
				elseif (($paddingTop == $paddingBottom) && ($paddingRight == $paddingLeft))
				{
					$objTemplateJs->padding = sprintf('padding: [%s, %s]', $paddingTop, $paddingRight);
				}
				elseif ($paddingRight == $paddingLeft)
				{
					$objTemplateJs->padding = sprintf('padding: [%s, %s, %s]', $paddingTop, $paddingRight, $paddingBottom);
				}
				else
				{
					$objTemplateJs->padding = sprintf('padding: [%s, %s, %s, %s]', $paddingTop, $paddingRight, $paddingBottom, $paddingLeft);
				}
			}

			// carouFredSel-Option 'align': Standardwert ist 'center'; nur bei fester Breite/Höhe sinnvoll
			if (($objCarouFredSel->align != 'center') && ($objCarouFredSel->widthSelect == 'fixed' || $objCarouFredSel->heightSelect == 'fixed'))
			{
				$objTemplateJs->align = 'align: ' . ($objCarouFredSel->align == 'none' ? 'false' : '"' . $objCarouFredSel->align . '"');
			}
		}

		// --- Elementgröße
		if ($objCarouFredSel->useItemsSize)
		{
			// carouFredSel-Option 'items.width': Standardwert ist 'null'
			switch ($objCarouFredSel->itemsWidthSelect)
			{
				case 'variable':
					$objTemplateJs->itemsWidth = 'width: "variable"';
					break;

				case 'fixed':
					$itemsWidth = StringUtil::deserialize($objCarouFredSel->itemsWidth, true);
					if (isset($itemsWidth['value']) && $itemsWidth['value'] != '')
					{
						$objTemplateJs->itemsWidth = 'width: ' . $itemsWidth['value'];
					}
					break;

				case 'fluid':
					$itemsWidth = StringUtil::deserialize($objCarouFredSel->itemsWidth, true);
					if (isset($itemsWidth['value']) && $itemsWidth['value'] != '')
					{
						$objTemplateJs->itemsWidth = sprintf('width: "%s%s"', $itemsWidth['value'], $itemsWidth['unit'] ?? '%');
					}
					break;
			}

			// carouFredSel-Option 'items.height': Standardwert ist 'null'
			switch ($objCarouFredSel->itemsHeightSelect)
			{
				case 'variable':
					$objTemplateJs->itemsHeight = 'height: "variable"';
					break;

				case 'fixed':
					$itemsHeight = StringUtil::deserialize($objCarouFredSel->itemsHeight, true);
					if (isset($itemsHeight['value']) && $itemsHeight['value'] != '')
					{
						$objTemplateJs->itemsHeight = 'height: ' . $itemsHeight['value'];
					}
					break;

				case 'fluid':
					$itemsHeight = StringUtil::deserialize($objCarouFredSel->itemsHeight, true);
					if (isset($itemsHeight['value']) && $itemsHeight['value'] != '')
					{
						$objTemplateJs->itemsHeight = sprintf('height: "%s%s"', $itemsHeight['value'], $itemsHeight['unit'] ?? '%');
					}
					break;
			}
		}

		// --- Allgemeine Element-Einstellungen
		if ($objCarouFredSel->useItemsGeneral)
		{
			// carouFredSel-Option 'responsive': Standardwert ist 'false'
			if ($objCarouFredSel->responsive)
			{
				$objTemplateJs->responsive = 'responsive: true';
			}

			// carouFredSel-Option 'cookie': Standardwert ist 'false'
			if ($objCarouFredSel->cookie)
			{
				$objTemplateJs->cookie = 'cookie: true';
			}

			// carouFredSel-Option 'items.visible': Standardwert ist 'null'
			switch ($objCarouFredSel->itemsVisibleSelect)
			{
				case 'variable':
					$objTemplateJs->itemsVisible = 'visible: "variable"';
					break;

				case 'fixed':
					$objTemplateJs->itemsVisible = ($objCarouFredSel->itemsVisible == '0' ? '' : 'visible: ' . $objCarouFredSel->itemsVisible);
					$objTemplateCss->itemsVisible = $objCarouFredSel->itemsVisible;
					break;

				case 'min/max':
					if (($objCarouFredSel->itemsVisibleMin != '0') && ($objCarouFredSel->itemsVisibleMax != '0'))
					{
						$objTemplateJs->itemsVisible = 'visible: { min: ' . $objCarouFredSel->itemsVisibleMin . ', max: ' . $objCarouFredSel->itemsVisibleMax . ' }';
					}
					elseif ($objCarouFredSel->itemsVisibleMax != '0')
					{
						$objTemplateJs->itemsVisible = 'visible: { max: ' . $objCarouFredSel->itemsVisibleMax . ' }';
					}
					elseif ($objCarouFredSel->itemsVisibleMin != '0')
					{
						$objTemplateJs->itemsVisible = 'visible: { min: ' . $objCarouFredSel->itemsVisibleMin . ' }';
					}
					break;
			}

			// carouFredSel-Option 'items.start': Standardwert ist '0'
			switch ($objCarouFredSel->itemsStartSelect)
			{
				case 'number':
					$objTemplateJs->itemsStart = ($objCarouFredSel->itemsStart == '0' ? '' : 'start: ' . $objCarouFredSel->itemsStart);
					break;

				case 'random':
					$objTemplateJs->itemsStart = 'start: "random"';
					break;

				case 'anchor':
					$objTemplateJs->itemsStart = 'start: true';
					break;
			}
		}

		// --- Navigation
		if ($objCarouFredSel->useNavigation)
		{
			// carouFredSel-Option 'prev.key': Standardwert ist 'null'
			if ($objCarouFredSel->prevKey != 'none')
			{
				$objTemplateJs->prevKey = 'key: "' . $objCarouFredSel->prevKey . '"';
			}

			// carouFredSel-Option 'next.key': Standardwert ist 'null'
			if ($objCarouFredSel->nextKey != 'none')
			{
				$objTemplateJs->nextKey = 'key: "' . $objCarouFredSel->nextKey . '"';
			}

			// carouFredSel-Option 'swipe.onTouch'
			if ($objCarouFredSel->swipeOnTouch)
			{
				$objTemplateJs->swipeOnTouch = 'onTouch: true';
			}

			// carouFredSel-Option 'swipe.onMouse'
			if ($objCarouFredSel->swipeOnMouse)
			{
				$objTemplateJs->swipeOnMouse = 'onMouse: true';
			}

			// carouFredSel-Option 'mousewheel'
			if ($objCarouFredSel->mousewheel)
			{
				$objTemplateJs->mousewheel = 'mousewheel: true';
			}

			if ($objCarouFredSel->navigation)
			{
				$objTemplateHtml->navigation =
				$objTemplateJs->navigation =
				$objTemplateCss->navigation = $objCarouFredSel->navigation;

				// carouFredSel-Option 'auto.button': Standardwert ist 'null'; nur bei automatischem Abspielen
				if ($objCarouFredSel->autoPlay)
				{
					$objTemplateHtml->autoButton =
					$objTemplateJs->autoButton =
					$objTemplateCss->autoButton = $objCarouFredSel->autoButton;
				}
			}

			if ($objCarouFredSel->pagination)
			{
				$objTemplateHtml->pagination =
				$objTemplateJs->pagination =
				$objTemplateCss->pagination = $objCarouFredSel->pagination;

				// carouFredSel-Option 'keys'
				if ($objCarouFredSel->paginationKeys)
				{
					$objTemplateJs->paginationKeys = 'keys: true';
				}
			}
		}

		// Typabhängige Vorgaben der Hintergrund-Slideshow: Sie füllt immer das
		// gesamte Browserfenster, deshalb werden Größen- und Sichtbarkeits-
		// einstellungen des Datensatzes überschrieben.
		if ($strCarouFredSelType == 'caroufredsel_background')
		{
			// Gesamtgröße
			$objTemplateJs->width = 'width: $(window).width()';
			$objTemplateJs->height = 'height: $(window).height()';
			$objTemplateJs->align = 'align: false';

			// Elementgröße
			$objTemplateJs->itemsWidth = 'width: "variable"';
			$objTemplateJs->itemsHeight = 'height: "variable"';

			// Sichtbare Elemente
			$objTemplateJs->itemsVisible = 'visible: 1';
		}

		// JavaScript-Auslösemodus (Einstellung der Erweiterung, siehe README)
		$objTemplateCss->triggerMode =
		$objTemplateJs->triggerMode = Config::get('dk_cfsTriggerMode');

		// Bildlader aktivieren
		if (Config::get('dk_cfsImageLoader'))
		{
			$objTemplateJs->useImageLoader = Config::get('dk_cfsImageLoader');
		}

		// Verhalten bei Größenänderung des Browserfensters
		if (Config::get('dk_cfsOnWindowResize'))
		{
			$objTemplateJs->onWindowResize = 'onWindowResize: "' . Config::get('dk_cfsOnWindowResize') . '"';
		}

		// CSS-Übergänge statt jQuery-Animationen
		if (Config::get('dk_cfsTransition'))
		{
			$objTemplateJs->transition = 'transition: true';
		}

		// Debug-Modus des jQuery-Plugins
		if (Config::get('dk_cfsDebug'))
		{
			$objTemplateJs->debug = 'debug: true';
		}

		// Assets registrieren:

		// ... globale CSS-Datei des Karussells
		$GLOBALS['TL_CSS'][] = 'bundles/contaocaroufredsel/css/caroufredsel.css||static';

		// ... elementabhängiger CSS-Block im Seitenkopf
		$GLOBALS['TL_HEAD'][] = $objTemplateCss->parse();

		// ... das carouFredSel-Plugin selbst
		$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.carouFredSel.min.js|static';

		// ... elementabhängiger JavaScript-Aufruf; TL_JQUERY wird nur ausgegeben,
		// wenn jQuery im Seitenlayout aktiviert ist (gilt für Contao 4 und 5)
		$GLOBALS['TL_JQUERY'][] = $objTemplateJs->parse();

		if ($objCarouFredSel->autoProgress == 'pie')
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.carouFredSelHelper.js|static';
		}

		// Hilfsskripte:

		// ... zusätzliche Easing-Methoden
		if (str_starts_with((string) $objCarouFredSel->scrollEasing, 'ease'))
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.easing.1.3.min.js|static';
		}

		// ... Touch-/Swipe-Unterstützung
		if ($objCarouFredSel->swipeOnTouch || $objCarouFredSel->swipeOnMouse)
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.touchSwipe.min.js|static';
		}

		// ... Mausrad-Unterstützung
		if ($objCarouFredSel->mousewheel)
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.mousewheel.min.js|static';
		}

		// ... Auslösemodus 'readyLoad' (Start je Element, sobald dessen Bilder geladen sind)
		if (Config::get('dk_cfsTriggerMode') == 'readyLoad')
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.readyLoad.js|static';
		}

		// ... Drosselung des Resize-Ereignisses (throttle/debounce)
		if (Config::get('dk_cfsOnWindowResize'))
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.ba-throttle-debounce.min.js|static';
		}

		// ... CSS-Übergänge statt jQuery-Animationen
		if (Config::get('dk_cfsTransition'))
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.transit.min.js|static';
		}

		// ... Bildlader
		if (Config::get('dk_cfsImageLoader'))
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaocaroufredsel/js/jquery.krioImageLoader-min.js|static';
		}
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
		if ($objCarouFredSel->usePlay && $objCarouFredSel->autoPlay && $objCarouFredSel->autoProgress != 'none')
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
