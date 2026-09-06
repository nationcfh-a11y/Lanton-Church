/* =========================================================================
   Lanton Church of Christ — parallax.js
   Vanilla JS (no libraries): letter-by-letter hero headline, sequenced load
   fades, word-by-word verse, staggered scroll-reveal (IntersectionObserver +
   CSS keyframes), transparent->frosted nav, and multi-speed parallax.
   ========================================================================= */
(function () {
	"use strict";

	var reduceMotion =
		window.matchMedia &&
		window.matchMedia("(prefers-reduced-motion: reduce)").matches;

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }
	function $all(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	/* ----------------------------------------------------------------------
	   1. Nav: transparent over the hero, frosted glass once scrolled.
	   -------------------------------------------------------------------- */
	function initHeader() {
		var header = $(".site-header");
		if (!header) return;
		var hero = $(".lanton-hero");

		if (!hero) {
			// Inner pages: always frosted; just track scroll for the shadow.
			header.classList.add("is-solid");
			var onScrollInner = function () {
				header.classList.toggle("is-scrolled", window.scrollY > 12);
			};
			onScrollInner();
			window.addEventListener("scroll", onScrollInner, { passive: true });
			return;
		}

		header.classList.add("is-transparent");
		var update = function () {
			var threshold = hero.offsetHeight - header.offsetHeight - 40;
			if (window.scrollY > threshold) {
				header.classList.remove("is-transparent");
				header.classList.add("is-solid", "is-scrolled");
			} else {
				header.classList.add("is-transparent");
				header.classList.remove("is-solid", "is-scrolled");
			}
		};
		update();
		window.addEventListener("scroll", update, { passive: true });
		window.addEventListener("resize", update, { passive: true });
	}

	/* ----------------------------------------------------------------------
	   2. Hero headline: split into characters for a letter-by-letter reveal.
	   -------------------------------------------------------------------- */
	function prepLetters() {
		$all(".lanton-animate-letters").forEach(function (el) {
			if (el.dataset.split) return;
			// Split on whitespace but keep the separators so spacing survives.
			var tokens = el.textContent.split(/(\s+)/);
			el.textContent = "";
			var n = 0;
			tokens.forEach(function (token) {
				if (token === "") return;
				if (/^\s+$/.test(token)) {
					el.appendChild(document.createTextNode(token));
					return;
				}
				// Wrap each word so its letters can't break across lines.
				var word = document.createElement("span");
				word.className = "lanton-word-wrap";
				token.split("").forEach(function (ch) {
					var span = document.createElement("span");
					span.className = "lanton-char";
					span.textContent = ch;
					span.style.setProperty("--d", 280 + n * 45 + "ms");
					word.appendChild(span);
					n++;
				});
				el.appendChild(word);
			});
			el.dataset.split = "true";
		});
	}

	/* ----------------------------------------------------------------------
	   3. Word-by-word verse.
	   -------------------------------------------------------------------- */
	function wrapVerseWords(verse, text) {
		verse.textContent = "";
		var inner = document.createElement("span");
		inner.className = "lanton-verse__inner";
		text.trim().split(/\s+/).forEach(function (word, i) {
			var span = document.createElement("span");
			span.className = "lanton-word";
			span.textContent = word;
			span.style.transitionDelay = i * 70 + "ms";
			inner.appendChild(span);
			inner.appendChild(document.createTextNode(" "));
		});
		verse.appendChild(inner);
		verse.dataset.wrapped = "true";
	}

	function prepVerses() {
		$all(".lanton-verse").forEach(function (verse) {
			if (verse.dataset.wrapped) return;
			wrapVerseWords(verse, verse.textContent);
		});
	}

	/* ----------------------------------------------------------------------
	   4. Staggered scroll-reveal — each element delays after its siblings.
	   -------------------------------------------------------------------- */
	function initReveal() {
		var targets = $all(".lanton-reveal, .lanton-verse");
		if (!targets.length) return;

		// Assign a stagger delay based on order within the parent.
		$all(".lanton-reveal").forEach(function (el) {
			var sibs = Array.prototype.filter.call(el.parentElement.children, function (c) {
				return c.classList && c.classList.contains("lanton-reveal");
			});
			var idx = sibs.indexOf(el);
			el.style.setProperty("--lanton-delay", Math.max(0, idx) * 120 + "ms");
		});

		if (reduceMotion || !("IntersectionObserver" in window)) {
			targets.forEach(function (el) { el.classList.add("is-in"); });
			return;
		}

		function makeObserver(threshold) {
			return new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							entry.target.classList.add("is-in");
							entry.target.__lantonObs.unobserve(entry.target);
						}
					});
				},
				{ threshold: threshold, rootMargin: "0px 0px -8% 0px" }
			);
		}

		// An element taller than the viewport can never show 16% of itself, so
		// the usual threshold would leave it stuck at opacity 0 forever (e.g.
		// the full sermon gallery). Watch those for any intersection instead.
		var observer = makeObserver(0.16);
		var tallObserver = makeObserver(0);

		targets.forEach(function (el) {
			var tall = el.getBoundingClientRect().height > window.innerHeight * 0.9;
			var obs = tall ? tallObserver : observer;
			el.__lantonObs = obs;
			obs.observe(el);
		});
	}

	/* ----------------------------------------------------------------------
	   5. Hero load animation — sequence the fades after the letters.
	   -------------------------------------------------------------------- */
	function initHeroLoad() {
		var letters = $(".lanton-animate-letters");
		var fades = $all(".lh-hero .lanton-fade");
		fades.forEach(function (el, i) {
			el.style.setProperty("--d", (i === 0 ? 150 : 750 + (i - 1) * 200) + "ms");
		});
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				if (letters) letters.classList.add("is-in");
				fades.forEach(function (el) { el.classList.add("is-in"); });
			});
		});
	}

	/* ----------------------------------------------------------------------
	   6b. Scripture carousel — rotate "A word for you" verses every 10s.
	   The first verse is the one already in the HTML (fallback for no-JS);
	   the rest cycle in with the same word-by-word fade.
	   -------------------------------------------------------------------- */
	var VERSES = [
		{ text: "Come to me, all you who are weary and burdened, and I will give you rest.", ref: "Matthew 11:28" },
		{ text: "But whatever were gains to me I now consider loss for the sake of Christ.", ref: "Philippians 3:7" },
		{ text: "I have been crucified with Christ and I no longer live, but Christ lives in me.", ref: "Galatians 2:20" },
		{ text: "Repent and be baptized, every one of you, in the name of Jesus Christ for the forgiveness of your sins.", ref: "Acts 2:38" },
		{ text: "I am the light of the world. Whoever follows me will never walk in darkness, but will have the light of life.", ref: "John 8:12" }
	];

	function initVerseCarousel() {
		var verse = $(".lh-verse__text");
		var ref = $(".lh-verse__ref");
		if (!verse || !ref || VERSES.length < 2) return;

		var i = 0; // index 0 is already rendered in the markup
		var FADE = 550;

		// Reserve the height of the tallest verse so the band never resizes
		// as it cycles. Measured with an offscreen probe at the live width,
		// re-run on resize since line counts change with the viewport.
		function lockHeight() {
			verse.style.minHeight = "";
			var probe = document.createElement("p");
			probe.className = verse.className;
			probe.style.cssText = "position:absolute;visibility:hidden;pointer-events:none;" +
				"margin:0;min-height:0;box-sizing:border-box;width:" + verse.offsetWidth + "px";
			var inner = document.createElement("span");
			inner.className = "lanton-verse__inner";
			probe.appendChild(inner);
			verse.parentNode.appendChild(probe);
			var max = 0;
			VERSES.forEach(function (v) {
				inner.textContent = v.text;
				max = Math.max(max, probe.offsetHeight);
			});
			probe.parentNode.removeChild(probe);
			verse.style.minHeight = max + "px";
		}

		lockHeight();
		var resizeTimer;
		window.addEventListener("resize", function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(lockHeight, 150);
		}, { passive: true });

		function show(v) {
			verse.classList.remove("is-in");      // words fade out (staggered)
			ref.classList.add("is-leaving");       // ref fades out
			setTimeout(function () {
				wrapVerseWords(verse, v.text);
				ref.textContent = v.ref;
				void verse.offsetWidth;            // reflow so the fade-in replays
				verse.classList.add("is-in");
				ref.classList.remove("is-leaving");
			}, reduceMotion ? 0 : FADE);
		}

		setInterval(function () {
			i = (i + 1) % VERSES.length;
			show(VERSES[i]);
		}, 10000);
	}

	/* ----------------------------------------------------------------------
	   6. Multi-speed parallax — shift background-position on photo sections,
	      and gently float the offset mission image.
	   -------------------------------------------------------------------- */
	function initParallax() {
		if (reduceMotion) return;
		var bgSections = $all(".lanton-scripture, .lanton-events, .lanton-cta");
		var floaters = $all(".lanton-mission__media");
		if (!bgSections.length && !floaters.length) return;

		var speeds = [0.22, 0.32, 0.18];
		bgSections.forEach(function (el, i) { el.dataset.speed = speeds[i % speeds.length]; });

		var ticking = false;
		var vh = window.innerHeight;

		function update() {
			bgSections.forEach(function (el) {
				var rect = el.getBoundingClientRect();
				if (rect.bottom < -200 || rect.top > vh + 200) return;
				var speed = parseFloat(el.dataset.speed) || 0.2;
				var delta = rect.top + rect.height / 2 - vh / 2;
				var offset = -delta * speed * 0.12;
				el.style.backgroundPositionY = "calc(50% + " + offset.toFixed(1) + "px)";
			});
			floaters.forEach(function (el) {
				var rect = el.getBoundingClientRect();
				if (rect.bottom < -200 || rect.top > vh + 200) return;
				var delta = rect.top + rect.height / 2 - vh / 2;
				el.style.transform = "translateY(" + (-delta * 0.05).toFixed(1) + "px)";
			});
			ticking = false;
		}

		function onScroll() {
			if (!ticking) { ticking = true; requestAnimationFrame(update); }
		}

		window.addEventListener("scroll", onScroll, { passive: true });
		window.addEventListener("resize", function () { vh = window.innerHeight; onScroll(); }, { passive: true });
		update();
	}

	function init() {
		initHeader();
		prepLetters();
		prepVerses();
		initReveal();
		initVerseCarousel();
		initHeroLoad();
		initParallax();
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
