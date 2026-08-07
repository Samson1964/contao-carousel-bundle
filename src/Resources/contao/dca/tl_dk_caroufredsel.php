<?php

declare(strict_types=1);

/**
 * carouFredSel-Bildkarussell für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @author    Dirk Klemmt (ursprüngliche Contao-3-Erweiterung)
 * @license   LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Message;

// Je nach Nutzungsmodus (Contao-Einstellung dk_cfsUsageMode) stehen nur die
// gängigsten oder alle Optionen zur Verfügung. Felder ohne
// Swiper-Entsprechung (scrollQueue, scrollEasing, cookie, responsive,
// swipeOnTouch/-OnMouse — Swiper ist immer responsiv und Touch-fähig)
// bleiben als Datenbankspalten erhalten, erscheinen aber nicht mehr in den
// Paletten.
switch (Config::get('dk_cfsUsageMode'))
{
	default:
	case 'basic':
		$subpaletteUsePlay = 'direction,carouselType,scrollItems,autoPlay';
		$subpaletteUseTransitions = 'scrollFx,scrollDuration';
		$subpaletteUseGeneralSize = 'widthSelect,heightSelect';
		$subpaletteUseItemsGeneral = 'itemsVisibleSelect';
		$subpaletteUseItemsSize = 'itemsWidthSelect,itemsHeightSelect';
		$subpaletteUseNavigation = 'navigation,pagination';
		break;

	case 'advanced':
		$subpaletteUsePlay = 'direction,carouselType,scrollItems,autoPlay';
		$subpaletteUseTransitions = 'scrollFx,scrollDuration';
		$subpaletteUseGeneralSize = 'widthSelect,heightSelect,padding';
		$subpaletteUseItemsGeneral = 'itemsVisibleSelect,itemsStartSelect';
		$subpaletteUseItemsSize = 'itemsWidthSelect,itemsHeightSelect';
		$subpaletteUseNavigation = 'prevKey,nextKey,mousewheel,navigation,pagination';
		break;
}

/**
 * Tabelle tl_dk_caroufredsel: eine wiederverwendbare Karussell-Konfiguration
 * je Datensatz. Elemente und Module verweisen über dk_cfsCarouFredSel darauf.
 */
$GLOBALS['TL_DCA']['tl_dk_caroufredsel'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'			=> DC_Table::class,
		'ctable'				=> array('tl_content'),
		'enableVersioning'		=> true,
		'onload_callback'		=> array
		(
			array('tl_dk_caroufredsel', 'changePalette')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'				=> DataContainer::MODE_SORTED,
			'fields'			=> array('title'),
			'flag'				=> DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'		=> 'filter;search,limit'
		),
		'label' => array
		(
			'fields'			=> array('title'),
			'format'			=> '%s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'			=> 'act=select',
				'class'			=> 'header_edit_all',
				'attributes'	=> 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['edit'],
				'href'			=> 'table=tl_content',
				'icon'			=> 'edit.svg',
				'attributes'	=> 'class="contextmenu"'
			),
			'editheader' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['editheader'],
				'href'			=> 'act=edit',
				'icon'			=> 'header.svg',
				'attributes'	=> 'class="edit-header"'
			),
			'copy' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['copy'],
				'href'			=> 'act=copy',
				'icon'			=> 'copy.svg'
			),
			'delete' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['delete'],
				'href'			=> 'act=delete',
				'icon'			=> 'delete.svg',
				'attributes'	=> 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['show'],
				'href'			=> 'act=show',
				'icon'			=> 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'			=> array('usePlay', 'useTransitions', 'useGeneralSize', 'useItemsGeneral', 'useItemsSize', 'useNavigation'),
		'default'				=> 'title;{play_legend},usePlay;{transistion_legend},useTransitions;{generalSize_legend},useGeneralSize;{itemsSize_legend},useItemsSize;{itemsGeneral_legend},useItemsGeneral;{navigation_legend},useNavigation;'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'usePlay'						=> $subpaletteUsePlay,
		'useTransitions'				=> $subpaletteUseTransitions,
		'useGeneralSize'				=> $subpaletteUseGeneralSize,
		'useItemsGeneral'				=> $subpaletteUseItemsGeneral,
		'useItemsSize'					=> $subpaletteUseItemsSize,
		'useNavigation'					=> $subpaletteUseNavigation
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'				=> "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'				=> "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['title'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('mandatory' => true, 'maxlength' => 255),
			'sql'				=> "varchar(255) NOT NULL default ''"
		),

		// --- Abspielverhalten
		'usePlay' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['usePlay'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'carouselType' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['carouselType'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options'			=> array('circular', 'infinite', 'once'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel'],
			'eval'				=> array('helpwizard' => true, 'tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'direction' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['direction'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'left',
			'options'			=> array('left', 'right', 'up', 'down'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['direction'],
			'eval'				=> array('submitOnChange' => true, 'tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'scrollItems' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollItems'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 5, 'rgxp' => 'digit', 'tl_class' => 'clr'),
			'sql'				=> "smallint(5) unsigned NOT NULL default '0'"
		),
		'scrollQueue' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollQueue'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'none',
			'options'			=> array('none', 'all', 'first', 'last'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollQueue'],
			'eval'				=> array('helpwizard' => true),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'autoPlay' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoPlay'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'default'			=> '1',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'autoTimeoutDuration' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoTimeoutDuration'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 10, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "int(10) NOT NULL default '2500'"
		),
		'autoDelay' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoDelay'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 10, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "int(10) NOT NULL default '0'"
		),
		'scrollPauseOnHover' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollPauseOnHover'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'none',
			'options'			=> array('none', 'restart', 'resume', 'immediate', 'immediate-resume'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollPauseOnHover'],
			'eval'				=> array('helpwizard' => true, 'tl_class' => 'clr'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'autoProgress' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoProgress'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'none',
			'options'			=> array('none', 'bar', 'pie'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoProgress'],
			'eval'				=> array('submitOnChange' => true, 'helpwizard' => true, 'tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'autoProgressInterval' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoProgressInterval'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 10, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "int(10) NOT NULL default '50'"
		),

		// --- Übergänge
		'useTransitions' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['useTransitions'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'scrollFx' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollFx'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'scroll',
			'options'			=> array('none', 'scroll', 'directscroll', 'fade', 'crossfade', 'cover', 'cover-fade', 'uncover', 'uncover-fade'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel'],
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'scrollEasing' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollEasing'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'swing',
			'options'			=> array('linear', 'swing', 'quadratic', 'cubic', 'elastic',
									'easeInQuad', 'easeOutQuad', 'easeInOutQuad', 'easeInCubic', 'easeOutCubic',
									'easeInOutCubic', 'easeInQuart', 'easeOutQuart', 'easeInOutQuart', 'easeInQuint',
									'easeOutQuint', 'easeInOutQuint', 'easeInSine', 'easeOutSine', 'easeInOutSine',
									'easeInExpo', 'easeOutExpo', 'easeInOutExpo', 'easeInCirc', 'easeOutCirc',
									'easeInOutCirc', 'easeInElastic', 'easeOutElastic', 'easeInOutElastic', 'easeInBack',
									'easeOutBack', 'easeInOutBack', 'easeInBounce', 'easeOutBounce', 'easeInOutBounce'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel'],
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'scrollDuration' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['scrollDuration'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 10, 'rgxp' => 'digit', 'tl_class' => 'clr'),
			'sql'				=> "int(10) NOT NULL default '500'"
		),

		// --- Gesamtgröße
		'useGeneralSize' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['useGeneralSize'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'widthSelect' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['widthSelect'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getWidthSelectOptions'),
			'reference'			=> &$GLOBALS['TL_LANG']['DK_CFS']['widthSelect'],
			'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'width' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['width'],
			'exclude'			=> true,
			'inputType'			=> 'inputUnit',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getWidthUnitOptions'),
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "varchar(64) NOT NULL default ''"
		),
		'heightSelect' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['heightSelect'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getHeightSelectOptions'),
			'reference'			=> &$GLOBALS['TL_LANG']['DK_CFS']['heightSelect'],
			'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w50 clr'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'height' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['height'],
			'exclude'			=> true,
			'inputType'			=> 'inputUnit',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getHeightUnitOptions'),
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "varchar(64) NOT NULL default ''"
		),
		'padding' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['padding'],
			'exclude'			=> true,
			'inputType'			=> 'trbl',
			'options'			=> array('px'),
			'eval'				=> array('includeBlankOption' => true, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50 clr'),
			'sql'				=> "varchar(128) NOT NULL default ''"
		),
		'align' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['align'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'center',
			'options'			=> array('none', 'center', 'left', 'right'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['align'],
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),

		// --- Elementgröße
		'useItemsSize' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['useItemsSize'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'itemsWidthSelect' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsWidthSelect'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getItemsWidthSelectOptions'),
			'reference'			=> &$GLOBALS['TL_LANG']['DK_CFS']['itemsWidthSelect'],
			'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'itemsWidth' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsWidth'],
			'exclude'			=> true,
			'inputType'			=> 'inputUnit',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getItemsWidthUnitOptions'),
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "varchar(64) NOT NULL default ''"
		),
		'itemsHeightSelect' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsHeightSelect'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getItemsHeightSelectOptions'),
			'reference'			=> &$GLOBALS['TL_LANG']['DK_CFS']['itemsHeightSelect'],
			'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w50 clr'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'itemsHeight' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsHeight'],
			'exclude'			=> true,
			'inputType'			=> 'inputUnit',
			'options_callback'	=> array('tl_dk_caroufredsel', 'getItemsHeightUnitOptions'),
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "varchar(64) NOT NULL default ''"
		),

		// --- Allgemeine Element-Einstellungen
		'useItemsGeneral' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['useItemsGeneral'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'responsive' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['responsive'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true, 'tl_class' => 'w50'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'cookie' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['cookie'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'itemsVisibleSelect' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsVisibleSelect'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options'			=> array('variable', 'fixed', 'min/max'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsVisibleSelect'],
			'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w50 clr'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'itemsVisible' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsVisible'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "smallint(5) unsigned NOT NULL default '0'"
		),
		'itemsVisibleMin' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsVisibleMin'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50 clr'),
			'sql'				=> "smallint(5) unsigned NOT NULL default '0'"
		),
		'itemsVisibleMax' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsVisibleMax'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "smallint(5) unsigned NOT NULL default '0'"
		),
		'itemsStartSelect' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsStartSelect'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'options'			=> array('number', 'random'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsStartSelect'],
			'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w50 clr'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'itemsStart' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['itemsStart'],
			'exclude'			=> true,
			'inputType'			=> 'text',
			'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
			'sql'				=> "smallint(5) NOT NULL default '0'"
		),

		// --- Navigation
		'useNavigation' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['useNavigation'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'prevKey' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['prevKey'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'none',
			'options'			=> array('none', 'left', 'up', 'right', 'down'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['prevKey'],
			'eval'				=> array('tl_class' => 'w50 clr'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'nextKey' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['nextKey'],
			'exclude'			=> true,
			'inputType'			=> 'select',
			'default'			=> 'none',
			'options'			=> array('none', 'right', 'down', 'left', 'up'),
			'reference'			=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['nextKey'],
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "varchar(32) NOT NULL default ''"
		),
		'swipeOnTouch' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['swipeOnTouch'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'swipeOnMouse' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['swipeOnMouse'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'mousewheel' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['mousewheel'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50 clr'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'navigation' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['navigation'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50 clr'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'autoButton' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['autoButton'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'pagination' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['pagination'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('submitOnChange' => true, 'tl_class' => 'w50 clr'),
			'sql'				=> "char(1) NOT NULL default ''"
		),
		'paginationKeys' => array
		(
			'label'				=> &$GLOBALS['TL_LANG']['tl_dk_caroufredsel']['paginationKeys'],
			'exclude'			=> true,
			'inputType'			=> 'checkbox',
			'eval'				=> array('tl_class' => 'w50'),
			'sql'				=> "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Callback-Klasse der Tabelle tl_dk_caroufredsel.
 *
 * Blendet abhängige Felder ein und liefert die Auswahllisten für Breiten-
 * und Höhenangaben. Die Datenbankzugriffe laufen bewusst über
 * Database::getInstance(), weil das magische $this->Database der alten
 * Fassung in Contao 5 nicht mehr zuverlässig funktioniert.
 */
class tl_dk_caroufredsel extends Backend
{
	/**
	 * Passt die Subpaletten beim Laden des Datensatzes an den gespeicherten
	 * Zustand an.
	 *
	 * Contao kann keine Checkbox-Subpalette innerhalb einer bereits durch
	 * eine Checkbox geöffneten Subpalette darstellen. Deshalb liest die
	 * Methode die inneren Auswahlwerte direkt aus der Datenbank und blendet
	 * die abhängigen Felder von Hand ein — derselbe Kniff wie in der
	 * Contao-3-Fassung. Im Basis-Modus erscheint zusätzlich ein Hinweis,
	 * dass der erweiterte Modus mehr Einstellungen bietet.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return void Ohne gespeicherten Datensatz (Neuanlage) passiert nichts
	 */
	public function changePalette(DataContainer $dc)
	{
		switch (Config::get('dk_cfsUsageMode'))
		{
			default:
			case 'basic':
				Message::addInfo($GLOBALS['TL_LANG']['tl_dk_caroufredsel']['basicModeInfo'] ?? '');
				$subsubpaletteAutoPlay = 'autoPlay,autoTimeoutDuration,scrollPauseOnHover';
				break;

			case 'advanced':
				$subsubpaletteAutoPlay = 'autoPlay,autoTimeoutDuration,autoDelay,scrollPauseOnHover,autoProgress';
				break;
		}

		$obj = Database::getInstance()
				->prepare("SELECT autoPlay, autoProgress, navigation, pagination, widthSelect, heightSelect, itemsWidthSelect, itemsHeightSelect, itemsVisibleSelect, itemsStartSelect
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		if ($obj->numRows < 1)
		{
			return;
		}

		if ($obj->autoPlay)
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['usePlay'] = str_replace('autoPlay', $subsubpaletteAutoPlay, $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['usePlay']);
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useNavigation'] = str_replace('navigation', 'navigation,autoButton', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useNavigation']);
		}

		if (Config::get('dk_cfsUsageMode') == 'advanced')
		{
			if ($obj->pagination)
			{
				$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useNavigation'] = str_replace('pagination', 'pagination,paginationKeys', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useNavigation']);
			}

			// Ausrichtung nur bei fester Breite oder Höhe anbieten
			if ($obj->widthSelect == 'fixed' || $obj->heightSelect == 'fixed')
			{
				$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useGeneralSize'] = str_replace(',padding', ',padding,align', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useGeneralSize']);
			}
		}

		// Breite nur anzeigen, wenn 'fixed' oder 'fluid' gewählt ist
		if ($obj->widthSelect == 'fixed' || $obj->widthSelect == 'fluid')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useGeneralSize'] = str_replace('widthSelect', 'widthSelect,width', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useGeneralSize']);
		}

		// Höhe nur anzeigen, wenn 'fixed' oder 'fluid' gewählt ist
		if ($obj->heightSelect == 'fixed' || $obj->heightSelect == 'fluid')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useGeneralSize'] = str_replace('heightSelect', 'heightSelect,height', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useGeneralSize']);
		}

		// Anzahl sichtbarer Elemente nur bei 'fixed' anzeigen
		if ($obj->itemsVisibleSelect == 'fixed')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsGeneral'] = str_replace('itemsVisibleSelect', 'itemsVisibleSelect,itemsVisible', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsGeneral']);
		}

		// Minimum/Maximum nur bei 'min/max' anzeigen
		if ($obj->itemsVisibleSelect == 'min/max')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsGeneral'] = str_replace('itemsVisibleSelect', 'itemsVisibleSelect,itemsVisibleMin,itemsVisibleMax', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsGeneral']);
		}

		// Elementbreite nur anzeigen, wenn 'fixed' oder 'fluid' gewählt ist
		if ($obj->itemsWidthSelect == 'fixed' || $obj->itemsWidthSelect == 'fluid')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsSize'] = str_replace('itemsWidthSelect', 'itemsWidthSelect,itemsWidth', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsSize']);
		}

		// Elementhöhe nur anzeigen, wenn 'fixed' oder 'fluid' gewählt ist
		if ($obj->itemsHeightSelect == 'fixed' || $obj->itemsHeightSelect == 'fluid')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsSize'] = str_replace('itemsHeightSelect', 'itemsHeightSelect,itemsHeight', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsSize']);
		}

		// Startelement nur anzeigen, wenn eine feste Nummer gewählt ist
		if ($obj->itemsStartSelect == 'number')
		{
			$GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsGeneral'] = str_replace('itemsStartSelect', 'itemsStartSelect,itemsStart', $GLOBALS['TL_DCA']['tl_dk_caroufredsel']['subpalettes']['useItemsGeneral']);
		}
	}

	/**
	 * Liefert die möglichen Breitenarten des Karussells.
	 *
	 * 'fluid' (prozentuale Breite) gibt es nur bei horizontalen Karussells,
	 * deshalb wird die gespeicherte Laufrichtung geprüft.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste der Optionen für das Feld widthSelect
	 */
	public function getWidthSelectOptions(DataContainer $dc)
	{
		$horizontalSizeOptions = array('variable', 'auto', 'fixed');

		$obj = Database::getInstance()
				->prepare("SELECT direction
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		if ($obj->numRows > 0 && ($obj->direction == 'left' || $obj->direction == 'right'))
		{
			$horizontalSizeOptions[] = 'fluid';
		}

		return $horizontalSizeOptions;
	}

	/**
	 * Liefert die Einheit für die Breitenangabe.
	 *
	 * Bei 'fluid' wird in Prozent gerechnet, sonst in Pixeln.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste mit genau einer Einheit ('%' oder 'px')
	 */
	public function getWidthUnitOptions(DataContainer $dc)
	{
		$obj = Database::getInstance()
				->prepare("SELECT widthSelect
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		return array(($obj->numRows && ($obj->widthSelect == 'fluid')) ? '%' : 'px');
	}

	/**
	 * Liefert die möglichen Höhenarten des Karussells.
	 *
	 * 'fluid' (prozentuale Höhe) gibt es nur bei vertikalen Karussells,
	 * deshalb wird die gespeicherte Laufrichtung geprüft.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste der Optionen für das Feld heightSelect
	 */
	public function getHeightSelectOptions(DataContainer $dc)
	{
		$verticalSizeOptions = array('variable', 'auto', 'fixed');

		$obj = Database::getInstance()
				->prepare("SELECT direction
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		if ($obj->numRows > 0 && ($obj->direction == 'up' || $obj->direction == 'down'))
		{
			$verticalSizeOptions[] = 'fluid';
		}

		return $verticalSizeOptions;
	}

	/**
	 * Liefert die Einheit für die Höhenangabe.
	 *
	 * Bei 'fluid' wird in Prozent gerechnet, sonst in Pixeln.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste mit genau einer Einheit ('%' oder 'px')
	 */
	public function getHeightUnitOptions(DataContainer $dc)
	{
		$obj = Database::getInstance()
				->prepare("SELECT heightSelect
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		return array(($obj->numRows && ($obj->heightSelect == 'fluid')) ? '%' : 'px');
	}

	/**
	 * Liefert die möglichen Breitenarten der Karussell-Elemente.
	 *
	 * 'fluid' gibt es nur bei responsiven, vertikalen Karussells.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste der Optionen für das Feld itemsWidthSelect
	 */
	public function getItemsWidthSelectOptions(DataContainer $dc)
	{
		$horizontalItemSizeOptions = array('variable', 'fixed');

		$obj = Database::getInstance()
				->prepare("SELECT responsive, direction
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		if ($obj->numRows > 0 && $obj->responsive && ($obj->direction == 'up' || $obj->direction == 'down'))
		{
			$horizontalItemSizeOptions[] = 'fluid';
		}

		return $horizontalItemSizeOptions;
	}

	/**
	 * Liefert die Einheit für die Breitenangabe der Karussell-Elemente.
	 *
	 * Bei 'fluid' wird in Prozent gerechnet, sonst in Pixeln.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste mit genau einer Einheit ('%' oder 'px')
	 */
	public function getItemsWidthUnitOptions(DataContainer $dc)
	{
		$obj = Database::getInstance()
				->prepare("SELECT itemsWidthSelect
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		return array(($obj->numRows && ($obj->itemsWidthSelect == 'fluid')) ? '%' : 'px');
	}

	/**
	 * Liefert die möglichen Höhenarten der Karussell-Elemente.
	 *
	 * 'fluid' gibt es nur bei responsiven, horizontalen Karussells.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste der Optionen für das Feld itemsHeightSelect
	 */
	public function getItemsHeightSelectOptions(DataContainer $dc)
	{
		$verticalItemSizeOptions = array('variable', 'fixed');

		$obj = Database::getInstance()
				->prepare("SELECT responsive, direction
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		if ($obj->numRows > 0 && $obj->responsive && ($obj->direction == 'left' || $obj->direction == 'right'))
		{
			$verticalItemSizeOptions[] = 'fluid';
		}

		return $verticalItemSizeOptions;
	}

	/**
	 * Liefert die Einheit für die Höhenangabe der Karussell-Elemente.
	 *
	 * Bei 'fluid' wird in Prozent gerechnet, sonst in Pixeln.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste mit genau einer Einheit ('%' oder 'px')
	 */
	public function getItemsHeightUnitOptions(DataContainer $dc)
	{
		$obj = Database::getInstance()
				->prepare("SELECT itemsHeightSelect
						   FROM   tl_dk_caroufredsel
						   WHERE  id = ? ")
				->limit(1)
				->execute($dc->id);

		return array(($obj->numRows && ($obj->itemsHeightSelect == 'fluid')) ? '%' : 'px');
	}
}
