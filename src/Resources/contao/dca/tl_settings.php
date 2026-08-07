<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

// Seit der Swiper-Umstellung (Version 3.0.0) gibt es nur noch eine
// Einstellung: den Nutzungsmodus. Die früheren jQuery-Optionen
// (dk_cfsTriggerMode, dk_cfsOnWindowResize, dk_cfsImageLoader,
// dk_cfsTransition, dk_cfsDebug) sind wirkungslos und entfallen.
//
// In Contao 5 gibt es das Einstellungen-Modul (tl_settings) nicht mehr; der
// Wert wird dort von Hand in system/config/localconfig.php gepflegt (siehe
// README). Der Guard verhindert Schreibzugriffe auf eine nicht vorhandene
// DCA-Definition.
if (isset($GLOBALS['TL_DCA']['tl_settings']['palettes']['default']))
{
	/**
	 * Palette um die carouFredSel-Einstellungen ergänzen
	 */
	$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{caroufredsel_legend:hide},dk_cfsUsageMode';

	/**
	 * Nutzungsmodus: 'basic' zeigt nur die gängigsten Karussell-Optionen,
	 * 'advanced' alle
	 */
	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsUsageMode'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsUsageMode'],
		'inputType'			=> 'select',
		'options'			=> array('basic', 'advanced'),
		'reference'			=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsUsageMode'],
		'eval'				=> array('helpwizard' => true)
	);
}
