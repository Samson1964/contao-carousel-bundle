/**
 * carouFredSel-Bildkarussell für Contao — Swiper-Initialisierer
 *
 * Seit Version 3.0.0 läuft das Karussell über Swiper (contao-components/swiper)
 * statt über das aufgegebene jQuery-Plugin carouFredSel. Diese Datei stellt
 * window.caroufredselInit(cfg) bereit; der Aufruf je Element kommt aus dem
 * Template js_caroufredsel. Umgesetzt werden auch die Funktionen, die Swiper
 * nicht von Haus aus mitbringt: Play/Pause-Schalter, Fortschrittsbalken und
 * -kreis, verzögerter Autoplay-Start, zufälliges Startelement, die
 * Vollbild-Hintergrund-Slideshow sowie die Synchronisierung zweier Karussells.
 *
 * @author  Frank Hoppe
 * @license LGPL-3.0-or-later
 */
(function () {
	'use strict';

	// Initialisierte Karussells je Element-ID (für die Synchronisierung)
	var registry = {};

	// Noch nicht auflösbare Synchronisierungs-Wünsche: Ziel-ID → [Swiper, …]
	var pendingSync = {};

	/**
	 * Koppelt zwei Karussells in beide Richtungen über das Controller-Modul.
	 */
	function link(a, b) {
		a.controller.control = b;
		b.controller.control = a;
	}

	/**
	 * Versieht alle direkten Kinder des .swiper-wrapper mit der Klasse
	 * swiper-slide. So werden auch beliebige Inhaltselemente zwischen
	 * Start- und Stop-Element zu Kacheln, ohne dass deren Markup angepasst
	 * werden muss.
	 */
	function addSlideClasses(container) {
		var wrapper = container.querySelector('.swiper-wrapper');
		if (!wrapper) {
			return;
		}
		Array.prototype.forEach.call(wrapper.children, function (child) {
			child.classList.add('swiper-slide');
		});
	}

	/**
	 * Verbindet die Fortschrittsanzeige mit dem autoplayTimeLeft-Ereignis
	 * (verfügbar ab Swiper 9; das Bundle verlangt mindestens Swiper 11).
	 * 'bar' füllt den Balken über die Breite, 'pie' zeichnet einen
	 * Kreisausschnitt per conic-gradient. Die Farben kommen aus den
	 * CSS-Eigenschaften --cfs-bar-color bzw. --cfs-pie-color/-background
	 * der Templates.
	 */
	function initProgress(swiper, cfg) {
		var bar = document.getElementById('caroufredsel_bar_' + cfg.id);
		if (!bar) {
			return;
		}

		if (cfg.autoProgress === 'pie') {
			var styles = window.getComputedStyle(bar);
			var color = (styles.getPropertyValue('--cfs-pie-color') || '#eeeeee').trim();
			var background = (styles.getPropertyValue('--cfs-pie-background') || '#222222').trim();

			swiper.on('autoplayTimeLeft', function (s, time, progress) {
				var deg = Math.round((1 - progress) * 360);
				bar.style.background = 'conic-gradient(' + color + ' ' + deg + 'deg, ' + background + ' ' + deg + 'deg)';
			});
		} else {
			swiper.on('autoplayTimeLeft', function (s, time, progress) {
				bar.style.width = ((1 - progress) * 100) + '%';
			});
		}
	}

	/**
	 * Initialisiert ein Karussell samt optionaler Vorschauleiste.
	 *
	 * cfg entspricht dem JSON aus CarouFredSel::createTemplateData():
	 * { id, options, autoButton, autoProgress, randomStart, autoDelay,
	 *   sync, background, thumbs }
	 */
	window.caroufredselInit = function (cfg) {
		var el = document.getElementById('caroufredsel_' + cfg.id);
		if (!el || typeof Swiper === 'undefined') {
			return;
		}

		addSlideClasses(el);
		var options = cfg.options || {};

		// Vorschauleiste als eigenen Swiper starten und über das
		// Thumbs-Modul mit dem Hauptkarussell koppeln
		if (cfg.thumbs) {
			var thumbsEl = document.querySelector(cfg.thumbs.el);
			if (thumbsEl) {
				addSlideClasses(thumbsEl);
				var thumbsSwiper = new Swiper(thumbsEl, {
					direction: cfg.thumbs.vertical ? 'vertical' : 'horizontal',
					slidesPerView: cfg.thumbs.visible || 'auto',
					spaceBetween: 5,
					watchSlidesProgress: true,
					freeMode: true,
					navigation: {
						prevEl: cfg.thumbs.prevEl,
						nextEl: cfg.thumbs.nextEl,
						disabledClass: 'disabled'
					}
				});
				options.thumbs = {
					swiper: thumbsSwiper,
					slideThumbActiveClass: 'selected'
				};
			}
		}

		// Zufälliges Startelement: erst im Browser steht die Kachelzahl fest
		if (cfg.randomStart) {
			var count = el.querySelectorAll('.swiper-wrapper > .swiper-slide').length;
			if (count > 0) {
				options.initialSlide = Math.floor(Math.random() * count);
			}
		}

		// Seitenzahlen als Links rendern, damit die mitgelieferten Skins
		// (Selektor ".caroufredsel_pagi a") weiter greifen
		if (options.pagination) {
			options.pagination.renderBullet = function (index, className) {
				return '<a href="#" class="' + className + '"><span>' + (index + 1) + '</span></a>';
			};
		}

		var swiper = new Swiper(el, options);

		// Verzögerter Autoplay-Start (Feld autoDelay)
		if (cfg.autoDelay > 0 && swiper.autoplay) {
			swiper.autoplay.stop();
			window.setTimeout(function () {
				swiper.autoplay.start();
			}, cfg.autoDelay);
		}

		// Fortschrittsanzeige
		if (cfg.autoProgress && options.autoplay) {
			initProgress(swiper, cfg);
		}

		// Play/Pause-Schalter; die Klasse "paused" schaltet das Icon der Skins um
		if (cfg.autoButton) {
			var btn = document.getElementById('caroufredsel_button_' + cfg.id);
			if (btn && swiper.autoplay) {
				btn.addEventListener('click', function (e) {
					e.preventDefault();
					if (swiper.autoplay.running && !swiper.autoplay.paused) {
						swiper.autoplay.stop();
						btn.classList.add('paused');
					} else {
						swiper.autoplay.start();
						btn.classList.remove('paused');
					}
				});
			}
		}

		// Vollbild-Hintergrundmodus (CSS in caroufredsel.css)
		if (cfg.background) {
			el.classList.add('caroufredsel_background_mode');
		}

		// Synchronisierung: koppeln, sobald beide Karussells initialisiert sind
		registry[cfg.id] = swiper;

		if (cfg.sync) {
			if (registry[cfg.sync]) {
				link(swiper, registry[cfg.sync]);
			} else {
				(pendingSync[cfg.sync] = pendingSync[cfg.sync] || []).push(swiper);
			}
		}

		if (pendingSync[cfg.id]) {
			pendingSync[cfg.id].forEach(function (other) {
				link(other, swiper);
			});
			delete pendingSync[cfg.id];
		}
	};
})();
