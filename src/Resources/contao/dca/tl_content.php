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
use Contao\BackendUser;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

// Im Backend-Modul "carouFredSel" hängen die Inhaltselemente an der
// Karussell-Konfiguration statt am Artikel
if (Input::get('do') == 'caroufredsel')
{
	$GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_dk_caroufredsel';
}

// Je nach Nutzungsmodus (Contao-Einstellung dk_cfsUsageMode) stehen nur die
// gängigsten oder alle Einstellungen zur Verfügung. Die Felder 'space' und
// 'guests' der Contao-3-Paletten gibt es nicht mehr und sie entfallen.
switch (Config::get('dk_cfsUsageMode'))
{
	default:
	case 'basic':
		$paletteCaroufredsel_start = '{type_legend},type;{caroufredsel_legend},dk_cfsCarouFredSel;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
		$paletteCaroufredsel_gallery = '{type_legend},type,headline;{source_legend},dk_cfsMultiSRC,dk_cfsSortBy;{caroufredsel_thumbnails_legend},dk_cfsUseThumbnails;{caroufredsel_legend},dk_cfsCarouFredSel;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
		$paletteCaroufredsel_background = '{type_legend},type,headline;{source_legend},dk_cfsMultiSRC,dk_cfsSortBy;{caroufredsel_thumbnails_legend},dk_cfsUseThumbnails;{caroufredsel_legend},dk_cfsCarouFredSel;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
		break;

	case 'advanced':
		$paletteCaroufredsel_start = '{type_legend},type;{caroufredsel_legend},dk_cfsCarouFredSel,dk_cfsSynchronise,dk_cfsHtmlTpl,dk_cfsCssTpl,dk_cfsJsTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
		$paletteCaroufredsel_gallery = '{type_legend},type,headline;{source_legend},dk_cfsMultiSRC,dk_cfsSortBy;{caroufredsel_image_legend},dk_cfsImageSize,dk_cfsFullsize,dk_cfsNumberOfItems;{caroufredsel_thumbnails_legend},dk_cfsUseThumbnails;{caroufredsel_legend},dk_cfsCarouFredSel,dk_cfsSynchronise,dk_cfsHtmlTpl,dk_cfsCssTpl,dk_cfsJsTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
		$paletteCaroufredsel_background = '{type_legend},type,headline;{source_legend},dk_cfsMultiSRC,dk_cfsSortBy;{caroufredsel_image_legend},dk_cfsNumberOfItems;{caroufredsel_thumbnails_legend},dk_cfsUseThumbnails;{caroufredsel_legend},dk_cfsCarouFredSel,dk_cfsSynchronise,dk_cfsHtmlTpl,dk_cfsCssTpl,dk_cfsJsTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
		break;
}

/**
 * Paletten für die carouFredSel-Inhaltselemente
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'dk_cfsUseThumbnails';

$GLOBALS['TL_DCA']['tl_content']['palettes']['caroufredsel_start'] = $paletteCaroufredsel_start;
$GLOBALS['TL_DCA']['tl_content']['palettes']['caroufredsel_gallery'] = $paletteCaroufredsel_gallery;
$GLOBALS['TL_DCA']['tl_content']['palettes']['caroufredsel_background'] = $paletteCaroufredsel_background;

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['dk_cfsUseThumbnails'] = 'dk_cfsThumbnailSize,dk_cfsThumbnailsPosition,dk_cfsThumbnailsAlign,dk_cfsThumbnailsWidth,dk_cfsThumbnailsHeight';

/**
 * Felder der carouFredSel-Inhaltselemente.
 *
 * Das Dateibaum-Feld sortiert seit Contao 4.10 über 'isSortable' direkt in
 * multiSRC; das frühere orderSRC-Feld entfällt. Die Bildgrößen-Optionen
 * kommen aus dem Dienst contao.image.image_sizes statt aus dem entfernten
 * Global TL_CROP.
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsMultiSRC'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsMultiSRC'],
	'exclude'			=> true,
	'inputType'			=> 'fileTree',
	'eval'				=> array('multiple' => true, 'fieldType' => 'checkbox', 'files' => true, 'isGallery' => true, 'isSortable' => true, 'mandatory' => true),
	'sql'				=> "blob NULL"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsSortBy'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsSortBy'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options'			=> array('custom', 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'),
	'reference'			=> &$GLOBALS['TL_LANG']['tl_content'],
	'eval'				=> array('tl_class' => 'w50'),
	'sql'				=> "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsImageSize'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsImageSize'],
	'exclude'			=> true,
	'inputType'			=> 'imageSize',
	// Liefert die im System hinterlegten Bildgrößen als Auswahlliste, beschränkt
	// auf die Größen, die der angemeldete Benutzer sehen darf. Der Dienst heißt
	// seit Contao 5 "contao.image.sizes"; unter Contao 4.13 ist
	// "contao.image.image_sizes" nur noch ein Alias darauf, der alte Name führt
	// in Contao 5 dagegen zu einem Fehler.
	'options_callback'	=> static function ()
	{
		return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
	},
	'reference'			=> &$GLOBALS['TL_LANG']['MSC'],
	'eval'				=> array('rgxp' => 'natural', 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'),
	'sql'				=> "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsFullsize'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsFullsize'],
	'exclude'			=> true,
	'inputType'			=> 'checkbox',
	'eval'				=> array('tl_class' => 'w50 m12'),
	'sql'				=> "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsNumberOfItems'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsNumberOfItems'],
	'exclude'			=> true,
	'inputType'			=> 'text',
	'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit'),
	'sql'				=> "smallint(5) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsUseThumbnails'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsUseThumbnails'],
	'exclude'			=> true,
	'inputType'			=> 'checkbox',
	'eval'				=> array('submitOnChange' => true),
	'sql'				=> "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailsVisibleSelect'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsVisibleSelect'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options'			=> array('variable'),
	'reference'			=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsVisibleSelect'],
	'eval'				=> array('helpwizard' => true, 'submitOnChange' => true, 'tl_class' => 'clr w50'),
	'sql'				=> "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailSize'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailSize'],
	'exclude'			=> true,
	'inputType'			=> 'imageSize',
	// Liefert die im System hinterlegten Bildgrößen als Auswahlliste, beschränkt
	// auf die Größen, die der angemeldete Benutzer sehen darf. Der Dienst heißt
	// seit Contao 5 "contao.image.sizes"; unter Contao 4.13 ist
	// "contao.image.image_sizes" nur noch ein Alias darauf, der alte Name führt
	// in Contao 5 dagegen zu einem Fehler.
	'options_callback'	=> static function ()
	{
		return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
	},
	'reference'			=> &$GLOBALS['TL_LANG']['MSC'],
	'eval'				=> array('rgxp' => 'natural', 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'),
	'sql'				=> "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailsVisible'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsVisible'],
	'exclude'			=> true,
	'inputType'			=> 'text',
	'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'),
	'sql'				=> "smallint(5) unsigned NOT NULL default '5'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailsPosition'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsPosition'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'default'			=> 'bottom',
	'options'			=> array('top', 'bottom', 'left', 'right'),
	'reference'			=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsPosition'],
	'eval'				=> array('tl_class' => 'clr w50'),
	'sql'				=> "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailsAlign'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsAlign'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'default'			=> 'center',
	'options'			=> array('center', 'left', 'right'),
	'reference'			=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsAlign'],
	'eval'				=> array('tl_class' => 'w50'),
	'sql'				=> "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailsWidth'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsWidth'],
	'exclude'			=> true,
	'inputType'			=> 'inputUnit',
	'options'			=> array('px', '%'),
	'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'includeBlankOption' => true, 'tl_class' => 'w50'),
	'sql'				=> "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsThumbnailsHeight'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsThumbnailsHeight'],
	'exclude'			=> true,
	'inputType'			=> 'inputUnit',
	'options'			=> array('px', '%'),
	'eval'				=> array('maxlength' => 4, 'rgxp' => 'digit', 'includeBlankOption' => true, 'tl_class' => 'w50'),
	'sql'				=> "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsCarouFredSel'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsCarouFredSel'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'foreignKey'		=> 'tl_dk_caroufredsel.title',
	'eval'				=> array('includeBlankOption' => true, 'mandatory' => true, 'chosen' => true, 'submitOnChange' => true),
	'wizard'			=> array(array('tl_content_caroufredsel', 'editCarouFredSel')),
	'sql'				=> "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsSynchronise'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsSynchronise'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options_callback'	=> array('tl_content_caroufredsel', 'getCarouFredSelCarousels'),
	'eval'				=> array('includeBlankOption' => true, 'maxlength' => 255, 'tl_class' => 'clr w50'),
	'sql'				=> "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsHtmlTpl'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsHtmlTpl'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options_callback'	=> static function ()
	{
		return Backend::getTemplateGroup('ce_caroufredsel');
	},
	'eval'				=> array('includeBlankOption' => true, 'maxlength' => 255, 'tl_class' => 'w50 clr'),
	'sql'				=> "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsCssTpl'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsCssTpl'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options_callback'	=> static function ()
	{
		return Backend::getTemplateGroup('css_caroufredsel');
	},
	'eval'				=> array('includeBlankOption' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
	'sql'				=> "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsJsTpl'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsJsTpl'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options_callback'	=> static function ()
	{
		return Backend::getTemplateGroup('js_caroufredsel');
	},
	'eval'				=> array('includeBlankOption' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
	'sql'				=> "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['dk_cfsGalleryTpl'] = array
(
	'label'				=> &$GLOBALS['TL_LANG']['tl_content']['dk_cfsGalleryTpl'],
	'exclude'			=> true,
	'inputType'			=> 'select',
	'options_callback'	=> static function ()
	{
		return Backend::getTemplateGroup('caroufredsel_gallery');
	},
	'eval'				=> array('includeBlankOption' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
	'sql'				=> "varchar(255) NOT NULL default ''"
);

/**
 * Callback-Klasse für die carouFredSel-Felder in tl_content.
 *
 * Erbt anders als die Contao-3-Fassung direkt von Backend, weil die
 * DCA-Hilfsklasse tl_content in Contao 5 nicht mehr existiert.
 */
class tl_content_caroufredsel extends Backend
{
	/**
	 * Hängt einen Bearbeiten-Verweis an das Auswahlfeld der
	 * Karussell-Konfiguration an.
	 *
	 * Der Verweis führt direkt in die Einstellungen des gewählten
	 * Datensatzes im Backend-Modul "carouFredSel". Der Request-Token wird
	 * über den CSRF-Dienst erzeugt, weil die Konstante REQUEST_TOKEN in
	 * Contao 5 entfallen ist.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer; value enthält
	 *                          die ID der gewählten Karussell-Konfiguration
	 *
	 * @return string HTML des Verweises, oder ein Leerstring solange keine
	 *                Konfiguration gewählt ist
	 */
	public function editCarouFredSel(DataContainer $dc)
	{
		if ($dc->value < 1)
		{
			return '';
		}

		$container = System::getContainer();
		$url = $container->get('router')->generate('contao_backend', array
		(
			'do'  => 'caroufredsel',
			'act' => 'edit',
			'id'  => $dc->value,
			'rt'  => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
		));

		$title = sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['dk_cfsEdit'][1] ?? ''), $dc->value);

		return ' <a href="' . StringUtil::specialcharsUrl($url) . '" title="' . $title . '" style="padding-left:3px">' . Image::getHtml('edit.svg', $GLOBALS['TL_LANG']['tl_content']['dk_cfsEdit'][0] ?? '', 'style="vertical-align:top"') . '</a>';
	}

	/**
	 * Liefert alle Karussells auf derselben Seite als Auswahlliste für die
	 * Synchronisierung.
	 *
	 * Gefunden werden sichtbare Start-, Galerie- und Hintergrund-Elemente
	 * desselben Artikels mit Ausnahme des aktuellen Elements.
	 *
	 * @param DataContainer $dc Der aufrufende Datencontainer
	 *
	 * @return array Liste der Element-IDs; leer, wenn es keine weiteren
	 *               Karussells gibt oder der Datensatz noch nicht existiert
	 */
	public function getCarouFredSelCarousels(DataContainer $dc)
	{
		$carouFredSelCarousels = array();

		if ($dc->activeRecord === null)
		{
			return $carouFredSelCarousels;
		}

		$obj = Database::getInstance()
				->prepare("SELECT id
						   FROM   tl_content
						   WHERE  pid = ? AND id != ? AND type IN ('caroufredsel_start', 'caroufredsel_background', 'caroufredsel_gallery') AND invisible != 1 ")
				->execute($dc->activeRecord->pid, $dc->activeRecord->id);

		while ($obj->next())
		{
			$carouFredSelCarousels[] = $obj->id;
		}

		return $carouFredSelCarousels;
	}
}
