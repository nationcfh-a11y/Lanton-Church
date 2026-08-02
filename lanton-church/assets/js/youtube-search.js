/**
 * Lanton Church — YouTube gallery: instant client-side search + click-to-play.
 *
 * The PHP shortcode renders every video as a lightweight thumbnail "facade".
 * Here we:
 *   1. Filter the cards live as the visitor types (matches title + description).
 *   2. Swap a thumbnail for a real YouTube iframe only when it's clicked, so the
 *      page stays fast no matter how many videos there are.
 *
 * Vanilla JS, no dependencies. Respects prefers-reduced-motion implicitly
 * (no animations added here).
 */
(function () {
	'use strict';

	function init(root) {
		var input = root.querySelector('.lanton-videos__input');
		var grid = root.querySelector('.lanton-videos__grid');
		var count = root.querySelector('.lanton-videos__count');
		var empty = root.querySelector('.lanton-videos__empty');
		var queryOut = root.querySelector('.lanton-videos__query');
		if (!grid) {
			return;
		}

		var cards = Array.prototype.slice.call(grid.querySelectorAll('.lanton-video'));
		var singular = count ? count.getAttribute('data-singular') || 'video' : 'video';
		var plural = count ? count.getAttribute('data-plural') || 'videos' : 'videos';

		// ---- Search -------------------------------------------------------
		function applyFilter() {
			var q = (input.value || '').trim().toLowerCase();
			var visible = 0;

			cards.forEach(function (card) {
				var hay = card.getAttribute('data-search') || '';
				var match = q === '' || hay.indexOf(q) !== -1;
				card.hidden = !match;
				if (match) {
					visible++;
				}
			});

			if (count) {
				count.textContent = visible.toLocaleString() + ' ' + (visible === 1 ? singular : plural);
			}
			if (empty) {
				empty.hidden = visible !== 0;
				if (queryOut) {
					queryOut.textContent = input.value;
				}
			}
		}

		if (input) {
			input.addEventListener('input', applyFilter);
			// In case the browser restored a value on back/forward.
			if (input.value) {
				applyFilter();
			}
		}

		// ---- Click to play ------------------------------------------------
		grid.addEventListener('click', function (e) {
			var btn = e.target.closest('.lanton-video__thumb');
			if (!btn) {
				return;
			}
			var id = btn.getAttribute('data-video-id');
			if (!id) {
				return;
			}

			var frame = document.createElement('iframe');
			frame.className = 'lanton-video__player';
			frame.setAttribute('allowfullscreen', '');
			frame.setAttribute(
				'allow',
				'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'
			);
			frame.setAttribute('loading', 'lazy');
			frame.setAttribute('title', btn.getAttribute('aria-label') || 'YouTube video');
			frame.src =
				'https://www.youtube-nocookie.com/embed/' +
				encodeURIComponent(id) +
				'?autoplay=1&rel=0';

			btn.replaceWith(frame);
		});
	}

	function boot() {
		document.querySelectorAll('.lanton-videos').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
