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

use Contao\File;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Contao\System;

/**
 * Gemeinsame Galerie-Logik für das Inhaltselement und das Frontend-Modul.
 *
 * In der Contao-3-Fassung war dieser Code doppelt vorhanden (Element und
 * Modul); der Trait führt beide Fassungen zusammen. Die Bilderzeugung läuft
 * nicht mehr über das in Contao 5 entfernte Controller::addImageToTemplate(),
 * sondern über den FigureBuilder (contao.image.studio), der seit Contao 4.11
 * existiert. Figure::applyLegacyTemplateData() liefert dabei dieselben
 * Template-Eigenschaften (src, alt, href, imgSize, arrSize, addImage …),
 * die die mitgelieferten Templates erwarten.
 */
trait GalleryTrait
{
	/**
	 * Dateien-Collection aus dem multiSRC-Feld
	 * @var \Contao\Model\Collection|null
	 */
	protected $objFiles;

	/**
	 * Liest das Dateifeld ein und prüft, ob die Galerie Bilder hat.
	 *
	 * Deserialisiert dk_cfsMultiSRC in $this->multiSRC und lädt die
	 * zugehörigen FilesModel-Datensätze nach $this->objFiles.
	 *
	 * @return bool true, wenn mindestens ein Datei-Datensatz vorhanden ist;
	 *              false, wenn die Auswahl leer ist und das Element keine
	 *              Ausgabe erzeugen soll
	 */
	protected function validateGallerySource(): bool
	{
		$this->multiSRC = StringUtil::deserialize($this->dk_cfsMultiSRC);

		if (!is_array($this->multiSRC) || empty($this->multiSRC))
		{
			return false;
		}

		$this->objFiles = FilesModel::findMultipleByUuids($this->multiSRC);

		return $this->objFiles !== null;
	}

	/**
	 * Sammelt alle Galerie-Bilder aus Einzeldateien und Ordnern und bringt
	 * sie in die eingestellte Reihenfolge.
	 *
	 * Nicht existierende Dateien, Doppelgänger, Unterordner und Dateien, die
	 * keine GD-Bilder sind, werden übersprungen. Die Sortierung "custom"
	 * folgt der Reihenfolge im Dateibaum-Feld (multiSRC) — das ersetzt das
	 * frühere orderSRC-Feld, das es seit Contao 4.10 nicht mehr gibt; bei
	 * Altdaten muss die Reihenfolge deshalb einmal neu gespeichert werden.
	 *
	 * @return array Nummerierte Liste von Einträgen mit dem Schlüssel
	 *               'model' (FilesModel des Bildes), bereits sortiert und
	 *               auf dk_cfsNumberOfItems gekürzt; leer, wenn kein Bild
	 *               übrig bleibt
	 */
	protected function collectGalleryImages(): array
	{
		$images = array();
		$auxDate = array();
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$objFiles = $this->objFiles;

		// Alle Bilder einsammeln
		while ($objFiles->next())
		{
			// Bereits verarbeitete oder fehlende Dateien überspringen
			if (isset($images[$objFiles->path]) || !file_exists($projectDir . '/' . $objFiles->path))
			{
				continue;
			}

			// Einzeldateien
			if ($objFiles->type == 'file')
			{
				$objFile = new File($objFiles->path);

				if (!$objFile->isGdImage)
				{
					continue;
				}

				$images[$objFiles->path] = array('uuid' => $objFiles->uuid, 'model' => $objFiles->current());
				$auxDate[] = $objFile->mtime;
			}

			// Ordner
			else
			{
				$objSubfiles = FilesModel::findByPid($objFiles->uuid);

				if ($objSubfiles === null)
				{
					continue;
				}

				while ($objSubfiles->next())
				{
					// Unterordner überspringen
					if ($objSubfiles->type == 'folder')
					{
						continue;
					}

					$objFile = new File($objSubfiles->path);

					if (!$objFile->isGdImage)
					{
						continue;
					}

					$images[$objSubfiles->path] = array('uuid' => $objSubfiles->uuid, 'model' => $objSubfiles->current());
					$auxDate[] = $objFile->mtime;
				}
			}
		}

		// Sortieren
		switch ($this->dk_cfsSortBy)
		{
			default:
			case 'name_asc':
				uksort($images, static fn ($a, $b) => strnatcasecmp(basename((string) $a), basename((string) $b)));
				break;

			case 'name_desc':
				uksort($images, static fn ($a, $b) => -strnatcasecmp(basename((string) $a), basename((string) $b)));
				break;

			case 'date_asc':
				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_ASC);
				break;

			case 'date_desc':
				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_DESC);
				break;

			case 'meta': // Abwärtskompatibilität
			case 'custom':
				// Reihenfolge des Dateibaum-Feldes übernehmen: erst alle
				// Positionen als Platzhalter anlegen, dann die passenden
				// Bilder einsortieren und Übriggebliebene hinten anhängen
				$arrOrder = array();

				foreach ($this->multiSRC as $uuid)
				{
					$arrOrder[(string) $uuid] = null;
				}

				foreach ($images as $k => $v)
				{
					$key = (string) $v['uuid'];

					if (array_key_exists($key, $arrOrder))
					{
						$arrOrder[$key] = $v;
						unset($images[$k]);
					}
				}

				// Übrig gebliebene Bilder hinten anhängen
				if (!empty($images))
				{
					$arrOrder = array_merge($arrOrder, array_values($images));
				}

				// Leere (nicht ersetzte) Platzhalter entfernen
				$images = array_values(array_filter($arrOrder));
				unset($arrOrder);
				break;

			case 'random':
				shuffle($images);
				break;
		}

		$images = array_values($images);

		// Gesamtzahl der Bilder begrenzen
		if ($this->dk_cfsNumberOfItems > 0)
		{
			$images = array_slice($images, 0, (int) $this->dk_cfsNumberOfItems);
		}

		return $images;
	}

	/**
	 * Erzeugt die Template-Zellen für Bilder und Vorschaubilder.
	 *
	 * Jede Zelle ist ein stdClass-Objekt mit den Legacy-Bilddaten des
	 * FigureBuilders, wie sie die Templates caroufredsel_gallery und
	 * caroufredsel_thumbnails erwarten. Im Backend wird eine feste
	 * Vorschaubreite von 160 Pixeln verwendet, damit die Galerievorschau
	 * kompakt bleibt (früher der maxWidth-Parameter von addImageToTemplate).
	 *
	 * Im Modus 'fixed' der Vorschauleiste wird die Kachelgröße aus der
	 * größten Bildabmessung errechnet; der Rundungsfehler wird abwechselnd
	 * auf- und abgerundet und so über alle Vorschaubilder verteilt.
	 *
	 * @param array $images     Bildliste aus collectGalleryImages()
	 * @param bool  $blnBackend true, wenn die Ausgabe für das Backend erzeugt wird
	 *
	 * @return array Zwei nummerierte Listen: [0] die Bildzellen,
	 *               [1] die Vorschaubild-Zellen (leer ohne Vorschauleiste)
	 */
	protected function buildGalleryCells(array $images, bool $blnBackend = false): array
	{
		$body = array();
		$bodyThumbnails = array();
		$studio = System::getContainer()->get('contao.image.studio');

		$size = StringUtil::deserialize($this->dk_cfsImageSize);
		if ($blnBackend)
		{
			$size = array(160, 0, 'proportional');
		}

		$intMaxImageWidth = 0;
		$intMaxImageHeight = 0;

		// Bilder erzeugen
		foreach ($images as $i => $image)
		{
			$figure = $studio->createFigureBuilder()
				->fromFilesModel($image['model'])
				->setSize($size)
				->enableLightbox((bool) $this->dk_cfsFullsize)
				->setLightboxGroupIdentifier('lb' . $this->id)
				->buildIfResourceExists();

			if ($figure === null)
			{
				continue;
			}

			$objCell = new \stdClass();
			$figure->applyLegacyTemplateData($objCell);
			$body[$i] = $objCell;

			// Größtes Bild ermitteln, falls die Vorschauleiste ihre
			// Kachelgröße selbst errechnen muss
			if ($this->dk_cfsUseThumbnails && $this->dk_cfsThumbnailsVisibleSelect == 'fixed')
			{
				$arrSize = $objCell->arrSize ?? array(0, 0);

				if ($arrSize[0] > $intMaxImageWidth)
				{
					$intMaxImageWidth = $arrSize[0];
				}
				if ($arrSize[1] > $intMaxImageHeight)
				{
					$intMaxImageHeight = $arrSize[1];
				}
			}
		}

		// Vorschaubilder erzeugen
		if ($this->dk_cfsUseThumbnails)
		{
			$thumbnailWidth = 0;
			$thumbnailHeight = 0;

			if ($this->dk_cfsThumbnailsVisibleSelect == 'fixed')
			{
				// Die Vorschauleiste soll dieselbe Breite/Höhe wie das
				// Hauptkarussell haben, deshalb wird die Kachelgröße je
				// Vorschaubild errechnet
				$thumbnailSize = array('', '', 'center_center');

				foreach ($images as $i => $image)
				{
					if ($this->dk_cfsThumbnailsPosition == 'left' || $this->dk_cfsThumbnailsPosition == 'right')
					{
						// Rundungsfehler abwechselnd verteilen
						$intThumbnailImageHeight = ($i % 2)
							? floor($intMaxImageHeight / $this->dk_cfsThumbnailsVisible)
							: ceil($intMaxImageHeight / $this->dk_cfsThumbnailsVisible);

						if ($thumbnailWidth > 0)
						{
							$thumbnailSize[0] = $thumbnailWidth;
						}
						$thumbnailSize[1] = $intThumbnailImageHeight;
					}
					else
					{
						$intThumbnailImageWidth = ($i % 2)
							? floor($intMaxImageWidth / $this->dk_cfsThumbnailsVisible)
							: ceil($intMaxImageWidth / $this->dk_cfsThumbnailsVisible);

						if ($thumbnailHeight > 0)
						{
							$thumbnailSize[1] = $thumbnailHeight;
						}
						$thumbnailSize[0] = $intThumbnailImageWidth;
					}

					$objThumbnail = $this->buildThumbnailCell($studio, $image, $thumbnailSize);

					if ($objThumbnail === null)
					{
						continue;
					}

					$bodyThumbnails[$i] = $objThumbnail;

					if (count($bodyThumbnails) == 1)
					{
						$thumbnailWidth = $objThumbnail->arrSize[0] ?? 0;
						$thumbnailHeight = $objThumbnail->arrSize[1] ?? 0;
					}
				}
			}
			else
			{
				foreach ($images as $i => $image)
				{
					$objThumbnail = $this->buildThumbnailCell($studio, $image, StringUtil::deserialize($this->dk_cfsThumbnailSize));

					if ($objThumbnail === null)
					{
						continue;
					}

					$bodyThumbnails[$i] = $objThumbnail;
				}
			}
		}

		return array($body, $bodyThumbnails);
	}

	/**
	 * Erzeugt eine einzelne Vorschaubild-Zelle über den FigureBuilder.
	 *
	 * @param object $studio Der Studio-Dienst (contao.image.studio)
	 * @param array  $image  Bildeintrag mit dem Schlüssel 'model'
	 * @param mixed  $size   Bildgrößen-Angabe im Contao-Format (Array oder serialisiert)
	 *
	 * @return \stdClass|null Die Zelle mit den Legacy-Bilddaten, oder null
	 *                        wenn die Bildquelle nicht mehr existiert
	 */
	protected function buildThumbnailCell(object $studio, array $image, $size): ?\stdClass
	{
		$figure = $studio->createFigureBuilder()
			->fromFilesModel($image['model'])
			->setSize($size)
			->buildIfResourceExists();

		if ($figure === null)
		{
			return null;
		}

		$objThumbnail = new \stdClass();
		$figure->applyLegacyTemplateData($objThumbnail);

		return $objThumbnail;
	}

	/**
	 * Überträgt die Einstellungen der Vorschauleiste und die Synchronisierung
	 * in die HTML-, CSS- und JavaScript-Templates.
	 *
	 * Der Block war in der Contao-3-Fassung wortgleich im Galerie-Element und
	 * im Galerie-Modul enthalten und ist hier zusammengeführt. Das
	 * JavaScript-Template erhält seit der Swiper-Umstellung nur noch
	 * strukturierte Werte (Position, sichtbare Anzahl, Ziel-ID der
	 * Synchronisierung); Breite und Höhe der Leiste laufen rein über CSS.
	 *
	 * @param FrontendTemplate $objTemplateCss Template für den CSS-Block
	 * @param FrontendTemplate $objTemplateJs  Template für den Initialisierungs-Aufruf
	 *
	 * @return void
	 */
	protected function applyThumbnailSettings(FrontendTemplate $objTemplateCss, FrontendTemplate $objTemplateJs): void
	{
		if ($this->dk_cfsUseThumbnails)
		{
			$this->Template->useThumbnails =
			$objTemplateCss->useThumbnails =
			$objTemplateJs->useThumbnails = $this->dk_cfsUseThumbnails;

			if ($this->dk_cfsThumbnailsVisibleSelect)
			{
				$this->Template->thumbnailsVisibleSelect =
				$objTemplateCss->thumbnailsVisibleSelect =
				$objTemplateJs->thumbnailsVisibleSelect = $this->dk_cfsThumbnailsVisibleSelect;
			}

			if ($this->dk_cfsThumbnailsVisibleSelect == 'fixed')
			{
				$objTemplateJs->thumbnailsVisible = (int) $this->dk_cfsThumbnailsVisible;
			}

			$this->Template->thumbnailsPosition =
			$objTemplateCss->thumbnailsPosition =
			$objTemplateJs->thumbnailsPosition = $this->dk_cfsThumbnailsPosition;

			$thumbnailsWidth = StringUtil::deserialize($this->dk_cfsThumbnailsWidth, true);
			if (isset($thumbnailsWidth['value']) && $thumbnailsWidth['value'] != '' && in_array($thumbnailsWidth['unit'] ?? '', array('px', '%'), true))
			{
				$objTemplateCss->thumbnailsWidth = 'width: ' . $thumbnailsWidth['value'] . $thumbnailsWidth['unit'] . ';';
			}

			$thumbnailsHeight = StringUtil::deserialize($this->dk_cfsThumbnailsHeight, true);
			if (isset($thumbnailsHeight['value']) && $thumbnailsHeight['value'] != '' && in_array($thumbnailsHeight['unit'] ?? '', array('px', '%'), true))
			{
				$objTemplateCss->thumbnailsHeight = 'height: ' . $thumbnailsHeight['value'] . $thumbnailsHeight['unit'] . ';';
			}
		}

		if ($this->dk_cfsSynchronise)
		{
			$objTemplateJs->synchronise = $this->dk_cfsSynchronise;
		}
	}
}
