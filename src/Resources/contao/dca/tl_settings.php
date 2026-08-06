<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

// In Contao 5 gibt es das Einstellungen-Modul (tl_settings) nicht mehr; die
// Werte werden dort von Hand in config/parameters bzw. localconfig.php
// gepflegt (siehe README). Der Guard verhindert Schreibzugriffe auf eine
// nicht vorhandene DCA-Definition.
if (isset($GLOBALS['TL_DCA']['tl_settings']['palettes']['default']))
{
	/**
	 * Palette um die carouFredSel-Einstellungen ergänzen
	 */
	$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{caroufredsel_legend:hide},dk_cfsUsageMode,dk_cfsTriggerMode,dk_cfsOnWindowResize,dk_cfsImageLoader,dk_cfsTransition,dk_cfsDebug';

	/**
	 * Felder der carouFredSel-Einstellungen
	 */
	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsUsageMode'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsUsageMode'],
		'inputType'			=> 'select',
		'options'			=> array('basic', 'advanced'),
		'reference'			=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsUsageMode'],
		'eval'				=> array('helpwizard' => true)
	);

	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsTriggerMode'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsTriggerMode'],
		'inputType'			=> 'select',
		'options'			=> array('onDocumentReady', 'onWindowLoad', 'readyLoad'),
		'default'			=> 'readyLoad',
		'reference'			=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsTriggerMode'],
		'eval'				=> array('helpwizard' => true, 'tl_class' => 'w50')
	);

	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsOnWindowResize'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsOnWindowResize'],
		'inputType'			=> 'select',
		'options'			=> array('throttle', 'debounce'),
		'reference'			=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsOnWindowResize'],
		'eval'				=> array('helpwizard' => true, 'includeBlankOption' => true, 'tl_class' => 'w50')
	);

	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsImageLoader'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsImageLoader'],
		'inputType'			=> 'checkbox',
		'eval'				=> array('tl_class' => 'w50')
	);

	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsTransition'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsTransition'],
		'inputType'			=> 'checkbox',
		'eval'				=> array('tl_class' => 'w50')
	);

	$GLOBALS['TL_DCA']['tl_settings']['fields']['dk_cfsDebug'] = array
	(
		'label'				=> &$GLOBALS['TL_LANG']['tl_settings']['dk_cfsDebug'],
		'inputType'			=> 'checkbox',
		'eval'				=> array('tl_class' => 'w50')
	);
}
