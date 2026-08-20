/**
 * Bible concordance study widget.
 */
(function () {
	'use strict';

	function cfg() {
		return window.hwblBibleConcordance || {};
	}

	function i18n(key, fallback) {
		var strings = cfg().i18n || {};
		return strings[key] || fallback;
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function highlight(text, query) {
		var safe = escapeHtml(text);
		var q = String(query || '').trim();
		if (!q || /^[HhGg]?\d+$/.test(q)) {
			return safe;
		}
		var parts = q.split(/\s+/).filter(function (p) {
			return p.length > 1;
		});
		if (!parts.length) {
			parts = [q];
		}
		parts.forEach(function (part) {
			var re;
			try {
				re = new RegExp('(' + part.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
			} catch (e) {
				return;
			}
			safe = safe.replace(re, '<mark>$1</mark>');
		});
		return safe;
	}

	function pageSize() {
		return Math.max(1, parseInt(cfg().pageSize, 10) || 50);
	}

	function fetchConcordance(query, translation, testament, offset, mode) {
		var conf = cfg();
		var base = (conf.restUrl || '').replace(/\?$/, '');
		var url =
			base +
			(base.indexOf('?') === -1 ? '?' : '&') +
			'q=' +
			encodeURIComponent(query) +
			'&translation=' +
			encodeURIComponent(translation || '') +
			'&testament=' +
			encodeURIComponent(testament || '') +
			'&limit=' +
			encodeURIComponent(String(pageSize())) +
			'&offset=' +
			encodeURIComponent(String(offset || 0)) +
			'&mode=' +
			encodeURIComponent(mode || 'auto');

		return fetch(url, {
			headers: conf.nonce ? { 'X-WP-Nonce': conf.nonce } : {},
			credentials: 'same-origin',
		}).then(function (res) {
			return res.json().then(function (body) {
				if (!res.ok) {
					var err = new Error((body && body.message) || 'Search failed.');
					err.payload = body;
					throw err;
				}
				return body;
			});
		});
	}

	function readerHref(hit) {
		var conf = cfg();
		if (conf.readerUrl) {
			var sep = conf.readerUrl.indexOf('?') === -1 ? '?' : '&';
			return (
				conf.readerUrl +
				sep +
				'book=' +
				encodeURIComponent(hit.book_id) +
				'&chapter=' +
				encodeURIComponent(hit.chapter) +
				'&verse=' +
				encodeURIComponent(hit.verse)
			);
		}
		return (
			'#hwbl-bible-reader:' +
			hit.book_id +
			':' +
			hit.chapter +
			':' +
			hit.verse
		);
	}

	function openCompare(hit, leftTranslation) {
		var conf = cfg();
		var left = leftTranslation || '';
		var right = conf.compareRight || 'web';
		if (right === left) {
			right = left === 'kjv' ? 'web' : 'kjv';
		}
		var ref = hit.reference || '';
		if (
			window.hwblTranslationCompareApi &&
			typeof window.hwblTranslationCompareApi.open === 'function'
		) {
			window.hwblTranslationCompareApi.open({
				ref: ref,
				left: left,
				right: right,
			});
			return;
		}
		// Fallback: open reader when compare assets are unavailable.
		var reader = document.querySelector('.hwbl-bible-reader');
		if (reader && window.hwblBibleReaderApi && window.hwblBibleReaderApi.goTo) {
			window.hwblBibleReaderApi.goTo(hit.book_id, hit.chapter, hit.verse);
		}
	}

	function renderStrongsMeta(widget, payload) {
		var meta = widget.querySelector('.hwbl-bible-concordance__strongs-meta');
		if (!meta) {
			return;
		}
		var entry = payload.strongs;
		if (!entry || !entry.number) {
			meta.hidden = true;
			meta.innerHTML = '';
			return;
		}
		var bits = ['<strong>' + escapeHtml(entry.number) + '</strong>'];
		if (entry.lemma) {
			bits.push(escapeHtml(entry.lemma));
		}
		if (entry.transliteration) {
			bits.push('<em>' + escapeHtml(entry.transliteration) + '</em>');
		}
		if (entry.gloss) {
			bits.push('— ' + escapeHtml(entry.gloss));
		}
		meta.innerHTML = bits.join(' ');
		meta.hidden = false;
	}

	function renderCandidates(widget, payload) {
		var box = widget.querySelector('.hwbl-bible-concordance__candidates');
		if (!box) {
			return;
		}
		var list = payload.candidates || [];
		if (list.length < 2) {
			box.hidden = true;
			box.innerHTML = '';
			return;
		}
		box.innerHTML = '';
		var label = document.createElement('p');
		label.className = 'hwbl-bible-concordance__candidates-label';
		label.textContent = i18n('candidates', 'Matching Strong\'s numbers');
		box.appendChild(label);
		var ul = document.createElement('ul');
		ul.className = 'hwbl-bible-concordance__candidates-list';
		list.forEach(function (entry) {
			var li = document.createElement('li');
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'hwbl-btn hwbl-btn-secondary hwbl-bible-concordance__candidate';
			btn.textContent =
				entry.number +
				(entry.gloss ? ' — ' + entry.gloss : '') +
				(entry.transliteration ? ' (' + entry.transliteration + ')' : '');
			btn.addEventListener('click', function () {
				var input = widget.querySelector('.hwbl-bible-concordance__query');
				var mode = widget.querySelector('.hwbl-bible-concordance__mode');
				if (input) {
					input.value = entry.number;
				}
				if (mode) {
					mode.value = 'strongs';
				}
				widget._hwblConcordanceOffset = 0;
				runSearch(widget);
			});
			li.appendChild(btn);
			ul.appendChild(li);
		});
		box.appendChild(ul);
		box.hidden = false;
	}

	function updatePager(widget, payload) {
		var pager = widget.querySelector('.hwbl-bible-concordance__pager');
		var prev = widget.querySelector('.hwbl-bible-concordance__prev');
		var next = widget.querySelector('.hwbl-bible-concordance__next');
		var label = widget.querySelector('.hwbl-bible-concordance__page-label');
		if (!pager) {
			return;
		}
		var offset = parseInt(payload.offset, 10) || 0;
		var count = parseInt(payload.count, 10) || 0;
		var hasMore = !!payload.has_more;
		var total = payload.total != null ? parseInt(payload.total, 10) : null;
		var show = count > 0 && (offset > 0 || hasMore);
		pager.hidden = !show;
		if (prev) {
			prev.disabled = offset <= 0;
		}
		if (next) {
			next.disabled = !hasMore;
		}
		if (label) {
			if (!count) {
				label.textContent = '';
			} else {
				var start = offset + 1;
				var end = offset + count;
				var text = i18n('pageLabel', 'Showing %1$d–%2$d')
					.replace('%1$d', String(start))
					.replace('%2$d', String(end));
				if (total != null && !isNaN(total)) {
					text += ' / ' + total;
				}
				label.textContent = text;
			}
		}
	}

	function renderResults(widget, payload) {
		var list = widget.querySelector('.hwbl-bible-concordance__results');
		var status = widget.querySelector('.hwbl-bible-concordance__status');
		if (!list) {
			return;
		}

		list.innerHTML = '';
		renderStrongsMeta(widget, payload);
		renderCandidates(widget, payload);

		var results = payload.results || [];
		if (!results.length) {
			list.hidden = true;
			updatePager(widget, payload);
			if (status) {
				status.textContent =
					payload.message || i18n('empty', 'No matches found.');
			}
			return;
		}

		list.hidden = false;
		var translation =
			payload.translation ||
			(widget.querySelector('.hwbl-bible-concordance__translation') || {})
				.value ||
			'';

		results.forEach(function (hit) {
			var li = document.createElement('li');
			li.className = 'hwbl-bible-concordance__hit';

			var actions = document.createElement('div');
			actions.className = 'hwbl-bible-concordance__actions';

			var ref = document.createElement('a');
			ref.className = 'hwbl-bible-concordance__ref';
			ref.href = readerHref(hit);
			ref.textContent = hit.reference || '';
			ref.addEventListener('click', function (event) {
				var reader = document.querySelector('.hwbl-bible-reader');
				if (!reader || !window.hwblBibleReaderApi || !window.hwblBibleReaderApi.goTo) {
					return;
				}
				event.preventDefault();
				window.hwblBibleReaderApi.goTo(
					hit.book_id,
					hit.chapter,
					hit.verse
				);
				reader.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});

			var compareBtn = document.createElement('button');
			compareBtn.type = 'button';
			compareBtn.className =
				'hwbl-btn hwbl-btn-secondary hwbl-bible-concordance__compare';
			compareBtn.textContent = i18n('compare', 'Compare');
			compareBtn.addEventListener('click', function () {
				openCompare(hit, translation);
			});

			actions.appendChild(ref);
			actions.appendChild(compareBtn);

			var text = document.createElement('p');
			text.className = 'hwbl-bible-concordance__text';
			text.innerHTML = highlight(
				hit.snippet || hit.text || '',
				payload.mode === 'strongs' ? '' : payload.query
			);

			li.appendChild(actions);
			li.appendChild(text);
			list.appendChild(li);
		});

		updatePager(widget, payload);

		if (status) {
			var label = i18n('resultsLabel', '%d matches').replace(
				'%d',
				String(
					payload.total != null
						? payload.total
						: payload.count || results.length
				)
			);
			if (payload.backend === 'local') {
				label += ' — ' + i18n('localHint', 'Local Bible');
			} else if (payload.backend === 'biblia') {
				label += ' — ' + i18n('bibliaHint', 'Biblia.com');
			} else if (payload.backend === 'strongs') {
				label += ' — ' + i18n('strongsHint', "Strong's");
			}
			if (payload.has_more || payload.truncated) {
				label += ' ' + i18n('truncated', 'More matches available — use Next to continue.');
			}
			status.textContent = label;
		}
	}

	function runSearch(widget) {
		var input = widget.querySelector('.hwbl-bible-concordance__query');
		var select = widget.querySelector('.hwbl-bible-concordance__translation');
		var testament = widget.querySelector('.hwbl-bible-concordance__testament');
		var modeSelect = widget.querySelector('.hwbl-bible-concordance__mode');
		var status = widget.querySelector('.hwbl-bible-concordance__status');
		var query = input ? input.value.trim() : '';
		var translation = select ? select.value : '';
		var scope = testament ? testament.value : '';
		var mode = modeSelect ? modeSelect.value : 'auto';
		var offset = parseInt(widget._hwblConcordanceOffset, 10) || 0;

		if (!query) {
			if (status) {
				status.textContent = i18n(
					'placeholder',
					'Enter a word or phrase.'
				);
			}
			return;
		}

		if (status) {
			status.textContent = i18n('loading', 'Searching…');
		}

		fetchConcordance(query, translation, scope, offset, mode)
			.then(function (payload) {
				if (payload.error && !(payload.results || []).length && !(payload.candidates || []).length) {
					if (status) {
						status.textContent =
							payload.message || payload.error || i18n('empty', 'No matches found.');
					}
					var list = widget.querySelector('.hwbl-bible-concordance__results');
					if (list) {
						list.hidden = true;
						list.innerHTML = '';
					}
					renderStrongsMeta(widget, payload);
					renderCandidates(widget, payload);
					updatePager(widget, payload);
					return;
				}
				widget._hwblConcordanceOffset = parseInt(payload.offset, 10) || offset;
				renderResults(widget, payload);
			})
			.catch(function (err) {
				if (status) {
					status.textContent =
						(err && err.message) || i18n('empty', 'No matches found.');
				}
			});
	}

	function initWidget(widget) {
		if (!widget || widget._hwblConcordanceInit) {
			return;
		}
		widget._hwblConcordanceInit = true;
		widget._hwblConcordanceOffset = 0;

		var form = widget.querySelector('.hwbl-bible-concordance__form');
		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				widget._hwblConcordanceOffset = 0;
				runSearch(widget);
			});
		}

		var prev = widget.querySelector('.hwbl-bible-concordance__prev');
		var next = widget.querySelector('.hwbl-bible-concordance__next');
		if (prev) {
			prev.addEventListener('click', function () {
				var offset = parseInt(widget._hwblConcordanceOffset, 10) || 0;
				widget._hwblConcordanceOffset = Math.max(0, offset - pageSize());
				runSearch(widget);
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				var offset = parseInt(widget._hwblConcordanceOffset, 10) || 0;
				widget._hwblConcordanceOffset = offset + pageSize();
				runSearch(widget);
			});
		}

		var preset = (widget.getAttribute('data-q') || '').trim();
		if (preset) {
			runSearch(widget);
		}
	}

	/**
	 * Prefill and search from an external control (e.g. Bible reader).
	 */
	function openWithQuery(query, translation) {
		var widget =
			document.querySelector('.hwbl-bible-reader__concordance-panel .hwbl-bible-concordance') ||
			document.querySelector('.hwbl-bible-concordance');
		if (!widget) {
			return;
		}
		var panel = widget.closest('.hwbl-bible-reader__concordance-panel');
		if (panel) {
			panel.hidden = false;
		}
		var input = widget.querySelector('.hwbl-bible-concordance__query');
		var select = widget.querySelector('.hwbl-bible-concordance__translation');
		if (input && query) {
			input.value = query;
		}
		if (select && translation) {
			select.value = translation;
		}
		widget._hwblConcordanceOffset = 0;
		initWidget(widget);
		runSearch(widget);
		widget.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	window.hwblBibleConcordanceApi = {
		initWidget: initWidget,
		runSearch: runSearch,
		openWithQuery: openWithQuery,
	};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-concordance').forEach(initWidget);
	});
})();
