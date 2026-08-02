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
		if (!q) {
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

	function fetchConcordance(query, translation, testament) {
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
			'&limit=50';

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

	function renderResults(widget, payload) {
		var list = widget.querySelector('.hwbl-bible-concordance__results');
		var status = widget.querySelector('.hwbl-bible-concordance__status');
		if (!list) {
			return;
		}

		list.innerHTML = '';
		var results = payload.results || [];
		if (!results.length) {
			list.hidden = true;
			if (status) {
				status.textContent =
					payload.message || i18n('empty', 'No matches found.');
			}
			return;
		}

		list.hidden = false;
		results.forEach(function (hit) {
			var li = document.createElement('li');
			li.className = 'hwbl-bible-concordance__hit';
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

			var text = document.createElement('p');
			text.className = 'hwbl-bible-concordance__text';
			text.innerHTML = highlight(hit.snippet || hit.text || '', payload.query);

			li.appendChild(ref);
			li.appendChild(text);
			list.appendChild(li);
		});

		if (status) {
			var label = i18n('resultsLabel', '%d matches').replace(
				'%d',
				String(payload.count || results.length)
			);
			if (payload.backend === 'local') {
				label += ' — ' + i18n('localHint', 'Local Bible');
			} else if (payload.backend === 'biblia') {
				label += ' — ' + i18n('bibliaHint', 'Biblia.com');
			}
			if (payload.truncated) {
				label +=
					' ' +
					i18n(
						'truncated',
						'Showing the first matches. Refine your search for more specific results.'
					).replace('%d', String(results.length));
			}
			status.textContent = label;
		}
	}

	function runSearch(widget) {
		var input = widget.querySelector('.hwbl-bible-concordance__query');
		var select = widget.querySelector('.hwbl-bible-concordance__translation');
		var testament = widget.querySelector('.hwbl-bible-concordance__testament');
		var status = widget.querySelector('.hwbl-bible-concordance__status');
		var query = input ? input.value.trim() : '';
		var translation = select ? select.value : '';
		var scope = testament ? testament.value : '';

		if (!query) {
			if (status) {
				status.textContent = i18n('placeholder', 'Enter a word or phrase.');
			}
			return;
		}

		if (status) {
			status.textContent = i18n('loading', 'Searching…');
		}

		fetchConcordance(query, translation, scope)
			.then(function (payload) {
				if (payload.error && !(payload.results || []).length) {
					if (status) {
						status.textContent =
							payload.message || payload.error || i18n('empty', 'No matches found.');
					}
					var list = widget.querySelector('.hwbl-bible-concordance__results');
					if (list) {
						list.hidden = true;
						list.innerHTML = '';
					}
					return;
				}
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

		var form = widget.querySelector('.hwbl-bible-concordance__form');
		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
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
