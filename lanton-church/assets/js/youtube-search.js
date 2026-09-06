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
		var tabs = Array.prototype.slice.call(root.querySelectorAll('.lanton-videos__tab'));
		var singular = count ? count.getAttribute('data-singular') || 'video' : 'video';
		var plural = count ? count.getAttribute('data-plural') || 'videos' : 'videos';
		var kind = 'all';

		// ---- Search + length filter ---------------------------------------
		function applyFilter() {
			var q = ((input && input.value) || '').trim().toLowerCase();
			var visible = 0;

			cards.forEach(function (card) {
				var hay = card.getAttribute('data-search') || '';
				var matchText = q === '' || hay.indexOf(q) !== -1;
				var matchKind = kind === 'all' || card.getAttribute('data-kind') === kind;
				var match = matchText && matchKind;
				card.hidden = !match;
				if (match) {
					visible++;
				}
			});

			// Don't leave a video playing inside a card the filter just hid —
			// the audio would keep going with nothing on screen to stop it.
			if (active && active.card && active.card.hidden) {
				stopActive();
			}

			if (count) {
				count.textContent = visible.toLocaleString() + ' ' + (visible === 1 ? singular : plural);
			}
			if (empty) {
				empty.hidden = visible !== 0;
				if (queryOut) {
					queryOut.textContent = (input && input.value) || '';
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

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				kind = tab.getAttribute('data-kind') || 'all';
				tabs.forEach(function (t) {
					var on = t === tab;
					t.classList.toggle('is-active', on);
					t.setAttribute('aria-selected', on ? 'true' : 'false');
				});
				applyFilter();
			});
		});

		// ---- Click to play (only one video at a time) ----------------------
		// The playing card holds an <iframe> where its thumbnail button was.
		// Starting another video puts the old thumbnail back, which destroys
		// that iframe — the only way to be certain the audio stops, since a
		// cross-origin embed can't otherwise be commanded to pause.
		var active = null; // { card, btn, frame }

		function stopActive() {
			if (!active) {
				return;
			}
			active.frame.replaceWith(active.btn);
			active.card.classList.remove('is-playing');
			active = null;
		}

		// Listen on the whole gallery, not just the grid, so the featured
		// "Most Recent Sermon" player shares the one-at-a-time rule too.
		root.addEventListener('click', function (e) {
			var btn = e.target.closest('.lanton-video__thumb');
			if (!btn) {
				return;
			}
			var id = btn.getAttribute('data-video-id');
			if (!id) {
				return;
			}

			stopActive();

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

			var card = btn.closest('.lanton-video');
			btn.replaceWith(frame);
			if (card) {
				card.classList.add('is-playing');
			}
			active = { card: card, btn: btn, frame: frame };
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
