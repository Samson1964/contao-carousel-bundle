<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

use Schachbulle\ContaoCaroufredselBundle\Elements\ContentCarouFredSelBackground;
use Schachbulle\ContaoCaroufredselBundle\Elements\ContentCarouFredSelGallery;
use Schachbulle\ContaoCaroufredselBundle\Elements\ContentCarouFredSelStart;
use Schachbulle\ContaoCaroufredselBundle\Elements\ContentCarouFredSelStop;
use Schachbulle\ContaoCaroufredselBundle\Models\CarouFredSelModel;
use Schachbulle\ContaoCaroufredselBundle\Modules\ModuleCarouFredSel;
use Schachbulle\ContaoCaroufredselBundle\Modules\ModuleCarouFredSelBackground;
use Schachbulle\ContaoCaroufredselBundle\Modules\ModuleCarouFredSelGallery;
use Schachbulle\ContaoCaroufredselBundle\Modules\ModuleCarouFredSelTicker;

/**
 * Backend-Modul im Menü "Inhalte": verwaltet die Karussell-Konfigurationen
 * (tl_dk_caroufredsel) samt der darin enthaltenen Inhaltselemente.
 */
$GLOBALS['BE_MOD']['content']['caroufredsel'] = array
(
	'tables' => array('tl_dk_caroufredsel', 'tl_content'),
	'icon'   => 'bundles/contaocaroufredsel/images/caroufredsel.png',
);

/**
 * Frontend-Module.
 *
 * Gruppe und Typnamen stammen aus der Contao-3-Fassung und bleiben unverändert,
 * damit bestehende Module einer umgestellten Installation ohne Nacharbeit
 * weiterlaufen. Der Newsticker erweitert die Nachrichtenliste und wird nur
 * registriert, wenn das News-Bundle installiert ist — sonst würde schon das
 * Laden der Klasse fehlschlagen.
 */
$GLOBALS['FE_MOD']['caroufredsel_category'] = array(
	'caroufredsel' => ModuleCarouFredSel::class,
	'caroufredsel_gallery' => ModuleCarouFredSelGallery::class,
	'caroufredsel_background' => ModuleCarouFredSelBackground::class,
);

if (class_exists(\Contao\ModuleNewsList::class))
{
	$GLOBALS['FE_MOD']['caroufredsel_category']['caroufredsel_ticker'] = ModuleCarouFredSelTicker::class;
}

/**
 * Inhaltselemente: Galerie, Hintergrund-Slideshow sowie das Wrapper-Paar
 * Start/Stop, das beliebige Inhaltselemente in ein Karussell verpackt.
 */
$GLOBALS['TL_CTE']['caroufredsel_category'] = array(
	'caroufredsel_gallery' => ContentCarouFredSelGallery::class,
	'caroufredsel_background' => ContentCarouFredSelBackground::class,
	'caroufredsel_start' => ContentCarouFredSelStart::class,
	'caroufredsel_stop' => ContentCarouFredSelStop::class,
);

/**
 * Start/Stop als Wrapper-Elemente kennzeichnen, damit das Backend die
 * eingeschlossenen Elemente eingerückt darstellt.
 */
$GLOBALS['TL_WRAPPERS']['start'][] = 'caroufredsel_start';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'caroufredsel_stop';

/**
 * Model-Registrierung für die Karussell-Konfigurationstabelle.
 */
$GLOBALS['TL_MODELS']['tl_dk_caroufredsel'] = CarouFredSelModel::class;
