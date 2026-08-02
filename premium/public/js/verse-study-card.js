(function () {
	'use strict';

	function cfg() {
		return window.hwblVerseStudy || {};
	}

	function i18n() {
		return cfg().i18n || {};
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function fetchJson(url, options) {
		var conf = cfg();
		var headers = { Accept: 'application/json' };
		if (conf.nonce) {
			headers['X-WP-Nonce'] = conf.nonce;
		}
		var opts = options || {};
		if (opts.headers) {
			headers = Object.assign({}, headers, opts.headers);
		}
		return fetch(
			url,
			Object.assign({ credentials: 'same-origin', headers: headers }, opts, {
				headers: headers,
			})
		).then(function (res) {
			return res.json().then(function (payload) {
				if (!res.ok) {
					throw payload;
				}
				return payload;
			});
		});
	}

	function getTradition(root) {
		var select = root.querySelector('.hwbl-verse-study__tradition');
		if (select && select.value) {
			return select.value;
		}
		var prefs = window.hwblUserPreferences;
		if (prefs && prefs.userTraditionAllowed && prefs.userTraditionAllowed()) {
			return prefs.readStoredTradition() || '';
		}
		var conf = cfg();
		if (conf.userTradition) {
			try {
				return window.localStorage.getItem(conf.traditionStorageKey || 'thw_ai_tradition_preset') || '';
			} catch (e) {
				return '';
			}
		}
		return '';
	}

	function renderCardHtml(payload) {
		var labels = i18n();
		var html = '';
		if (payload.reference || payload.text) {
			html += '<div class="hwbl-verse-study__verse">';
			if (payload.reference) {
				html += '<p class="hwbl-verse-study__ref">' + escapeHtml(payload.reference) + '</p>';
			}
			if (payload.text) {
				html += '<p class="hwbl-verse-study__text">' + escapeHtml(payload.text) + '</p>';
			}
			if (payload.cached) {
				html +=
					'<p class="hwbl-verse-study__cached description">' +
					escapeHtml(labels.cached || 'Saved study') +
					'</p>';
			}
			html += '</div>';
		}

		function section(label, body) {
			if (!body) {
				return '';
			}
			return (
				'<section class="hwbl-verse-study__section">' +
				'<h4 class="hwbl-verse-study__label">' +
				escapeHtml(label) +
				'</h4>' +
				'<p class="hwbl-verse-study__body">' +
				escapeHtml(body) +
				'</p>' +
				'</section>'
			);
		}

		html += section(labels.plainWords || 'In plain words', payload.plainWords);
		html += section(labels.context || 'In context', payload.context);

		if (payload.keywords && payload.keywords.length) {
			html += '<section class="hwbl-verse-study__section"><h4 class="hwbl-verse-study__label">' +
				escapeHtml(labels.keywords || 'Key words') +
				'</h4><ul class="hwbl-verse-study__list">';
			payload.keywords.forEach(function (row) {
				html +=
					'<li><strong>' +
					escapeHtml(row.term || '') +
					'</strong> — ' +
					escapeHtml(row.note || '') +
					'</li>';
			});
			html += '</ul></section>';
		}

		if (payload.crossReferences && payload.crossReferences.length) {
			html +=
				'<section class="hwbl-verse-study__section"><h4 class="hwbl-verse-study__label">' +
				escapeHtml(labels.xrefs || 'Cross-references') +
				'</h4><ul class="hwbl-verse-study__list">';
			payload.crossReferences.forEach(function (row) {
				html +=
					'<li><strong>' +
					escapeHtml(row.reference || '') +
					'</strong>' +
					(row.why ? ' — ' + escapeHtml(row.why) : '') +
					'</li>';
			});
			html += '</ul></section>';
		}

		html += section(labels.liveIt || 'Live it', payload.liveIt);

		if (payload.inCurriculum && payload.lessonUrl) {
			html +=
				'<p class="hwbl-verse-study__lesson"><a class="hwbl-btn hwbl-btn-secondary" href="' +
				escapeHtml(payload.lessonUrl) +
				'">' +
				escapeHtml(labels.lesson || 'Open related lesson') +
				'</a></p>';
		}

		html +=
			'<p class="hwbl-verse-study__disclaimer description">' +
			escapeHtml(labels.disclaimer || '') +
			'</p>';
		return html;
	}

	function setStatus(root, message, isError) {
		var el = root.querySelector('.hwbl-verse-study__status');
		if (!el) {
			return;
		}
		el.textContent = message || '';
		el.classList.toggle('is-error', !!isError);
	}

	function showResult(root, payload) {
		var el = root.querySelector('.hwbl-verse-study__result');
		if (!el) {
			return;
		}
		el.hidden = false;
		el.innerHTML = renderCardHtml(payload || {});
	}

	function readCoords(root) {
		var bookSelect = root.querySelector('.hwbl-verse-study__book');
		var chapterInput = root.querySelector('.hwbl-verse-study__chapter');
		var verseInput = root.querySelector('.hwbl-verse-study__verse');
		return {
			bookId: bookSelect
				? parseInt(bookSelect.value || '0', 10)
				: parseInt(root.dataset.bookId || '0', 10),
			chapter: chapterInput
				? parseInt(chapterInput.value || '0', 10)
				: parseInt(root.dataset.chapter || '0', 10),
			verse: verseInput
				? parseInt(verseInput.value || '0', 10)
				: parseInt(root.dataset.verse || '0', 10),
			translation: root.dataset.translation || '',
		};
	}

	function loadStudyCard(root, overrides) {
		var conf = cfg();
		var labels = i18n();
		var coords = Object.assign(readCoords(root), overrides || {});

		if (!coords.bookId || !coords.chapter || !coords.verse) {
			setStatus(root, labels.verseRequired || 'Choose a verse first.', true);
			return Promise.resolve();
		}
		if (!conf.restUrl) {
			setStatus(root, labels.error || 'Study card unavailable.', true);
			return Promise.resolve();
		}
		if (!conf.loggedIn) {
			setStatus(root, labels.loginRequired || 'Sign in to generate a study card.', true);
			return Promise.resolve();
		}

		setStatus(root, labels.loading || 'Loading…', false);
		var result = root.querySelector('.hwbl-verse-study__result');
		if (result) {
			result.hidden = true;
			result.innerHTML = '';
		}

		return fetchJson(conf.restUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				book_id: coords.bookId,
				chapter: coords.chapter,
				verse: coords.verse,
				translation: coords.translation || '',
				tradition: getTradition(root),
			}),
		})
			.then(function (payload) {
				setStatus(root, '', false);
				showResult(root, payload);
			})
			.catch(function (err) {
				var msg = labels.error || 'Could not build a study card.';
				if (err && err.code === 'thw_ai_login') {
					msg = labels.loginRequired || msg;
				} else if (err && err.message) {
					msg = err.message;
				}
				setStatus(root, msg, true);
			});
	}

	function fillBooks(root) {
		var select = root.querySelector('.hwbl-verse-study__book');
		var conf = cfg();
		if (!select || !conf.booksUrl) {
			return Promise.resolve();
		}
		return fetchJson(conf.booksUrl)
			.then(function (payload) {
				var books = payload.books || [];
				var current = String(root.dataset.bookId || '43');
				select.innerHTML = '';
				books.forEach(function (book) {
					var opt = document.createElement('option');
					opt.value = String(book.id);
					opt.textContent = book.name || String(book.id);
					if (String(book.id) === current) {
						opt.selected = true;
					}
					select.appendChild(opt);
				});
			})
			.catch(function () {
				/* Picker stays empty; user can still use data attributes via autofetch. */
			});
	}

	function initRoot(root) {
		if (!root || root.dataset.hwblVerseStudyInit === '1') {
			return;
		}
		root.dataset.hwblVerseStudyInit = '1';

		var tradition = root.querySelector('.hwbl-verse-study__tradition');
		if (tradition) {
			tradition.addEventListener('change', function () {
				var prefs = window.hwblUserPreferences;
				if (prefs && prefs.saveTradition && tradition.value) {
					prefs.saveTradition(tradition.value);
				}
			});
		}

		var btn = root.querySelector('.hwbl-verse-study__btn');
		if (btn) {
			btn.addEventListener('click', function () {
				loadStudyCard(root);
			});
		}

		var ready = Promise.resolve();
		if (root.dataset.picker === '1') {
			ready = fillBooks(root);
		}
		ready.then(function () {
			if (root.dataset.autofetch === '1') {
				loadStudyCard(root);
			}
		});
	}

	function initAll() {
		document.querySelectorAll('.hwbl-verse-study').forEach(initRoot);
	}

	window.hwblVerseStudyApi = {
		renderCardHtml: renderCardHtml,
		loadStudyCard: loadStudyCard,
		initRoot: initRoot,
	};

	document.addEventListener('DOMContentLoaded', initAll);
})();
