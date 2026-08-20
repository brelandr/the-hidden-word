(function () {
	'use strict';

	function cfg() {
		return window.hwblTranslationCompare || {};
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
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
			headers: { 'X-WP-Nonce': nonce },
		})
			.then(function (res) {
				return res.ok ? res.json() : null;
			})
			.then(function (parsed) {
				if (!parsed || !parsed.book_id) {
					return null;
				}
				var bookId = parsed.book_id;
				var chapter = parsed.chapter;
				var verse = parsed.verse_start || parsed.verse || 0;
				function loadTranslation(slug) {
					return fetch(
						base +
							'bible/chapter?book_id=' +
							bookId +
							'&chapter=' +
							chapter +
							'&translation=' +
							encodeURIComponent(slug) +
							'&verse=' +
							verse,
						{
							headers: { 'X-WP-Nonce': nonce },
						}
					).then(function (res) {
						return res.ok ? res.json() : null;
					});
				}
				return Promise.all([loadTranslation(left), loadTranslation(right)]).then(
					function (rows) {
						return {
							parsed: parsed,
							verse: verse,
							rows: rows,
						};
					}
				);
			});
	}

	function extractVerseText(payload, verseNum) {
		if (!payload || !payload.verses) {
			return '';
		}
		var verse = payload.verses.find(function (row) {
			return parseInt(row.number, 10) === parseInt(verseNum, 10);
		});
		return verse ? verse.text : payload.verses[0] ? payload.verses[0].text : '';
	}

	function fillColumns(root, left, right, ref) {
		var leftCol = root.querySelector('[data-col="left"]');
		var rightCol = root.querySelector('[data-col="right"]');
		var title = root.querySelector('.hwbl-translation-compare__title');
		if (title) {
			title.textContent = ref;
		}

		var parsed = parseReference(ref);
		if (!parsed) {
			if (leftCol) {
				leftCol.textContent = 'Could not parse reference.';
			}
			return Promise.resolve();
		}

		if (leftCol) {
			leftCol.innerHTML =
				'<strong>' + escapeHtml(left.toUpperCase()) + '</strong><p>Loading…</p>';
		}
		if (rightCol) {
			rightCol.innerHTML =
				'<strong>' + escapeHtml(right.toUpperCase()) + '</strong><p>Loading…</p>';
		}

		return fetchVerse(left, right, ref).then(function (bundle) {
			if (!bundle || !bundle.rows) {
				if (leftCol) {
					leftCol.innerHTML =
						'<strong>' +
						escapeHtml(left.toUpperCase()) +
						'</strong><p>Could not load verse.</p>';
				}
				return;
			}
			var leftText = extractVerseText(bundle.rows[0], parsed.verse);
			var rightText = extractVerseText(bundle.rows[1], parsed.verse);
			if (leftCol) {
				leftCol.innerHTML =
					'<strong>' +
					escapeHtml(left.toUpperCase()) +
					'</strong><blockquote>' +
					escapeHtml(leftText) +
					'</blockquote>';
			}
			if (rightCol) {
				rightCol.innerHTML =
					'<strong>' +
					escapeHtml(right.toUpperCase()) +
					'</strong><blockquote>' +
					escapeHtml(rightText) +
					'</blockquote>';
			}
		});
	}

	function ensureModal() {
		var existing = document.getElementById('hwbl-translation-compare-modal');
		if (existing) {
			return existing;
		}
		var modal = document.createElement('div');
		modal.id = 'hwbl-translation-compare-modal';
		modal.className = 'hwbl-translation-compare-modal';
		modal.hidden = true;
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');
		modal.setAttribute('aria-label', 'Translation comparison');
		modal.innerHTML =
			'<div class="hwbl-translation-compare-modal__backdrop" data-close="1"></div>' +
			'<div class="hwbl-translation-compare-modal__dialog">' +
			'<header class="hwbl-translation-compare-modal__header">' +
			'<h3 class="hwbl-translation-compare__title"></h3>' +
			'<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-translation-compare-modal__close" data-close="1">Close</button>' +
			'</header>' +
			'<div class="hwbl-translation-compare" data-left="" data-right="" data-ref="">' +
			'<div class="hwbl-translation-compare__col" data-col="left"></div>' +
			'<div class="hwbl-translation-compare__col" data-col="right"></div>' +
			'</div>' +
			'</div>';
		document.body.appendChild(modal);
		modal.addEventListener('click', function (event) {
			if (event.target && event.target.getAttribute('data-close') === '1') {
				closeModal();
			}
		});
		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && !modal.hidden) {
				closeModal();
			}
		});
		return modal;
	}

	function closeModal() {
		var modal = document.getElementById('hwbl-translation-compare-modal');
		if (modal) {
			modal.hidden = true;
		}
	}

	function openCompare(options) {
		options = options || {};
		var ref = String(options.ref || '').trim();
		var left = String(options.left || 'kjv').toLowerCase();
		var right = String(options.right || 'web').toLowerCase();
		if (!ref) {
			return;
		}
		var modal = ensureModal();
		var root = modal.querySelector('.hwbl-translation-compare');
		if (root) {
			root.setAttribute('data-left', left);
			root.setAttribute('data-right', right);
			root.setAttribute('data-ref', ref);
		}
		modal.hidden = false;
		fillColumns(root, left, right, ref);
	}

	function initEmbedded(root) {
		if (!root || root._hwblCompareInit) {
			return;
		}
		root._hwblCompareInit = true;
		var left = root.getAttribute('data-left') || 'kjv';
		var right = root.getAttribute('data-right') || 'web';
		var ref = root.getAttribute('data-ref') || '';
		if (!ref) {
			var leftCol = root.querySelector('[data-col="left"]');
			if (leftCol) {
				leftCol.textContent = 'Add ref="John 3:16" to the shortcode.';
			}
			return;
		}
		fillColumns(root, left, right, ref);
	}

	window.hwblTranslationCompareApi = {
		open: openCompare,
		close: closeModal,
		init: initEmbedded,
	};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-translation-compare').forEach(function (root) {
			if (root.closest('#hwbl-translation-compare-modal')) {
				return;
			}
			initEmbedded(root);
		});

		// Deep-link: ?hwbl_compare_ref=John+3:16&hwbl_compare_left=kjv&hwbl_compare_right=web
		try {
			var params = new URLSearchParams(window.location.search || '');
			var ref = params.get('hwbl_compare_ref') || params.get('compare_ref');
			if (ref) {
				openCompare({
					ref: ref,
					left: params.get('hwbl_compare_left') || params.get('left') || 'kjv',
					right: params.get('hwbl_compare_right') || params.get('right') || 'web',
				});
			}
		} catch (e) {
			// Ignore URLSearchParams failures on ancient browsers.
		}
	});
})();
