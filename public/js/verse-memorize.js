(function () {
	'use strict';

	function initMemorizeForm(root) {
		if (!root || root.dataset.hwblMemorizeInit === '1') {
			return;
		}
		root.dataset.hwblMemorizeInit = '1';

		var cfg = window.hwblVerseMemorize || {};
		var i18n = cfg.i18n || {};
		var form = root.querySelector('.hwbl-verse-memorize__form');
		var input = root.querySelector('.hwbl-verse-memorize__reference');
		var select = root.querySelector('.hwbl-verse-memorize__translation');
		var status = root.querySelector('.hwbl-verse-memorize__status');
		var result = root.querySelector('.hwbl-verse-memorize__result');

		function setStatus(msg) {
			if (status) {
				status.textContent = msg || '';
			}
		}

		function initMemorizationWidgets(container) {
			if (!container) {
				return;
			}
			container.querySelectorAll('.hwbl-memorization').forEach(function (widget) {
				if (window.hwblInitMemorization && typeof window.hwblInitMemorization === 'function') {
					window.hwblInitMemorization(widget);
				}
			});
		}

		function submitReference(reference, translation) {
			setStatus(i18n.loading || 'Loading verse…');
			if (result) {
				result.hidden = true;
				result.innerHTML = '';
			}

			return fetch(cfg.restUrl + 'memorize-verse', {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-WP-Nonce': cfg.nonce || '',
				},
				body: JSON.stringify({
					reference: reference,
					translation: translation,
				}),
			})
				.then(function (res) {
					return res.json().then(function (payload) {
						if (!res.ok) {
							throw payload;
						}
						return payload;
					});
				})
				.then(function (payload) {
					setStatus('');
					if (result) {
						result.hidden = false;
						var note = '';
						if (payload.in_curriculum) {
							note =
								'<p class="hwbl-verse-memorize__note">' +
								(i18n.inCurriculum || 'This verse is already in the lesson catalog.') +
								'</p>';
						}
						result.innerHTML = note + (payload.html || '');
						initMemorizationWidgets(result);
						result.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				})
				.catch(function (err) {
					var msg = i18n.error || 'Could not load that verse.';
					if (err && err.error === 'invalid_reference') {
						msg = i18n.invalid || msg;
					} else if (err && err.error === 'verse_unavailable') {
						msg = i18n.unavailable || msg;
					} else if (err && err.message) {
						msg = err.message;
					}
					setStatus(msg);
				});
		}

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var reference = input ? input.value.trim() : '';
				var translation = select ? select.value : cfg.translation;
				if (!reference) {
					setStatus(i18n.invalid || 'Enter a verse reference like John 3:16.');
					return;
				}
				submitReference(reference, translation);
			});
		}

		root.querySelectorAll('.hwbl-verse-memorize__list-item').forEach(function (button) {
			button.addEventListener('click', function () {
				if (input) {
					input.value = button.getAttribute('data-reference') || '';
				}
				if (select && button.getAttribute('data-translation')) {
					select.value = button.getAttribute('data-translation');
				}
				submitReference(button.getAttribute('data-reference') || '', select ? select.value : cfg.translation);
			});
		});

		if (cfg.reference && input && !input.value) {
			input.value = cfg.reference;
		}
	}

	function initReaderMemorize(root) {
		if (!root || root.dataset.hwblReaderMemorizeInit === '1') {
			return;
		}
		root.dataset.hwblReaderMemorizeInit = '1';

		var cfg = window.hwblBibleReader || {};
		var i18n = cfg.i18n || {};
		var bar = root.querySelector('.hwbl-bible-reader__memorize-bar');
		var button = root.querySelector('.hwbl-bible-reader__memorize-btn');
		var panel = root.querySelector('.hwbl-bible-reader__memorize-panel');
		var content = root.querySelector('.hwbl-bible-reader__content');
		var selected = {
			bookId: parseInt(root.dataset.book || '0', 10),
			chapter: parseInt(root.dataset.chapter || '0', 10),
			verse: parseInt(root.dataset.verse || '0', 10),
		};

		function showBar() {
			if (bar) {
				bar.hidden = false;
			}
		}

		function hideBar() {
			if (bar) {
				bar.hidden = true;
			}
			if (panel) {
				panel.hidden = true;
				panel.innerHTML = '';
			}
		}

		function highlightVerse(verseNum) {
			if (!content) {
				return;
			}
			content.querySelectorAll('.hwbl-bible-reader__verse.is-selected').forEach(function (node) {
				node.classList.remove('is-selected');
			});
			var target = content.querySelector('[data-verse="' + verseNum + '"]');
			if (target) {
				target.classList.add('is-selected');
			}
		}

		function loadMemorizePanel() {
			if (!panel || !selected.verse) {
				return;
			}
			panel.hidden = false;
			panel.innerHTML = '<p class="hwbl-bible-reader__memorize-loading">' + (i18n.memorizeLoading || 'Loading…') + '</p>';

			fetch(cfg.restUrl + 'memorize-verse', {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-WP-Nonce': cfg.nonce || '',
				},
				body: JSON.stringify({
					book_id: selected.bookId,
					chapter: selected.chapter,
					verse: selected.verse,
					translation: root.dataset.translation || cfg.translation,
				}),
			})
				.then(function (res) {
					return res.json().then(function (payload) {
						if (!res.ok) {
							throw payload;
						}
						return payload;
					});
				})
				.then(function (payload) {
					panel.innerHTML = payload.html || '';
					if (window.hwblInitMemorization && typeof window.hwblInitMemorization === 'function') {
						panel.querySelectorAll('.hwbl-memorization').forEach(function (widget) {
							window.hwblInitMemorization(widget);
						});
					}
				})
				.catch(function (err) {
					var msg = i18n.memorizeError || 'Could not load memorization practice.';
					if (err && err.message) {
						msg = err.message;
					}
					panel.innerHTML = '<p class="hwbl-bible-reader__memorize-error">' + msg + '</p>';
				});
		}

		if (content) {
			content.addEventListener('click', function (event) {
				var verseNode = event.target.closest('.hwbl-bible-reader__verse');
				if (!verseNode) {
					return;
				}
				selected.bookId = parseInt(root.dataset.book || '0', 10);
				selected.chapter = parseInt(root.dataset.chapter || '0', 10);
				selected.verse = parseInt(verseNode.getAttribute('data-verse') || '0', 10);
				highlightVerse(selected.verse);
				showBar();
				if (panel) {
					panel.hidden = true;
					panel.innerHTML = '';
				}
			});
		}

		if (button) {
			button.addEventListener('click', function () {
				if (!selected.verse) {
					selected.verse = parseInt(root.dataset.verse || '0', 10);
				}
				if (!selected.verse) {
					return;
				}
				loadMemorizePanel();
			});
		}

		if (selected.verse) {
			showBar();
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-verse-memorize').forEach(initMemorizeForm);
		document.querySelectorAll('.hwbl-bible-reader').forEach(initReaderMemorize);
	});
})();
