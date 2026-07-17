(function () {
	'use strict';

	function cfg() {
		return window.hwblTranslationCompare || {};
	}

	function parseReference(ref) {
		var match = String(ref || '').trim().match(/^(.+?)\s+(\d+):(\d+)/);
		if (!match) {
			return null;
		}
		return { reference: match[0], book: match[1], chapter: match[2], verse: match[3] };
	}

	function fetchVerse(left, right, ref) {
		var base = cfg().restUrl || '';
		var nonce = cfg().nonce || '';
		return fetch(base + 'bible/parse?reference=' + encodeURIComponent(ref), {
			headers: { 'X-WP-Nonce': nonce }
		}).then(function (res) {
			return res.ok ? res.json() : null;
		}).then(function (parsed) {
			if (!parsed || !parsed.book_id) {
				return null;
			}
			var bookId = parsed.book_id;
			var chapter = parsed.chapter;
			var verse = parsed.verse_start || parsed.verse || 0;
			function loadTranslation(slug) {
				return fetch(base + 'bible/chapter?book_id=' + bookId + '&chapter=' + chapter + '&translation=' + encodeURIComponent(slug) + '&verse=' + verse, {
					headers: { 'X-WP-Nonce': nonce }
				}).then(function (res) {
					return res.ok ? res.json() : null;
				});
			}
			return Promise.all([loadTranslation(left), loadTranslation(right)]);
		});
	}

	function extractVerseText(payload, verseNum) {
		if (!payload || !payload.verses) {
			return '';
		}
		var verse = payload.verses.find(function (row) {
			return parseInt(row.number, 10) === parseInt(verseNum, 10);
		});
		return verse ? verse.text : (payload.verses[0] ? payload.verses[0].text : '');
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-translation-compare').forEach(function (root) {
			var left = root.getAttribute('data-left') || 'kjv';
			var right = root.getAttribute('data-right') || 'web';
			var ref = root.getAttribute('data-ref') || '';
			var leftCol = root.querySelector('[data-col="left"]');
			var rightCol = root.querySelector('[data-col="right"]');

			if (!ref) {
				if (leftCol) {
					leftCol.textContent = 'Add ref="John 3:16" to the shortcode.';
				}
				return;
			}

			var parsed = parseReference(ref);
			if (!parsed) {
				if (leftCol) {
					leftCol.textContent = 'Could not parse reference.';
				}
				return;
			}

			if (leftCol) {
				leftCol.innerHTML = '<strong>' + left.toUpperCase() + '</strong><p>Loading…</p>';
			}
			if (rightCol) {
				rightCol.innerHTML = '<strong>' + right.toUpperCase() + '</strong><p>Loading…</p>';
			}

			fetchVerse(left, right, ref).then(function (rows) {
				if (!rows) {
					return;
				}
				var leftText = extractVerseText(rows[0], parsed.verse);
				var rightText = extractVerseText(rows[1], parsed.verse);
				if (leftCol) {
					leftCol.innerHTML = '<strong>' + left.toUpperCase() + '</strong><blockquote>' + leftText + '</blockquote>';
				}
				if (rightCol) {
					rightCol.innerHTML = '<strong>' + right.toUpperCase() + '</strong><blockquote>' + rightText + '</blockquote>';
				}
			});
		});
	});
})();
