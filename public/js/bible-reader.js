(function () {
	'use strict';

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function fetchJson(url) {
		var headers = { Accept: 'application/json' };
		if (window.hwblBibleReader && hwblBibleReader.nonce) {
			headers['X-WP-Nonce'] = hwblBibleReader.nonce;
		}
		return fetch(url, { credentials: 'same-origin', headers: headers }).then(function (res) {
			if (!res.ok) {
				throw new Error('request_failed');
			}
			return res.json();
		});
	}

	function initReader(root) {
		if (!root || root.dataset.hwblReaderInit === '1') {
			return;
		}
		root.dataset.hwblReaderInit = '1';

		var cfg = window.hwblBibleReader || {};
		var i18n = cfg.i18n || {};
		var features = cfg.features || {};
		var prefs = window.hwblUserPreferences || null;
		var preferred =
			(prefs && prefs.getPreferredTranslation && prefs.getPreferredTranslation()) ||
			cfg.preferredTranslation ||
			'';
		var initialTranslation = root.dataset.translation || cfg.translation || 'bsb';
		if (
			preferred &&
			cfg.translations &&
			Object.prototype.hasOwnProperty.call(cfg.translations, preferred)
		) {
			initialTranslation = preferred;
		}
		var state = {
			translation: initialTranslation,
			bookId: parseInt(root.dataset.book || cfg.bookId || '1', 10),
			chapter: parseInt(root.dataset.chapter || cfg.chapter || '1', 10),
			verse: parseInt(root.dataset.verse || cfg.verse || '0', 10),
			narrator: cfg.narrator || 'david',
			books: [],
			audioMap: {},
			navigation: {},
		};
		root.dataset.translation = state.translation;

		var elTranslation = qs(root, '.hwbl-bible-reader__translation');
		var elBook = qs(root, '.hwbl-bible-reader__book');
		var elChapter = qs(root, '.hwbl-bible-reader__chapter');
		var elNarrator = qs(root, '.hwbl-bible-reader__narrator');
		var elAudioWrap = qs(root, '.hwbl-bible-reader__field--audio');
		var elAudio = qs(root, '.hwbl-bible-reader__audio');
		var elPrev = qs(root, '.hwbl-bible-reader__prev');
		var elNext = qs(root, '.hwbl-bible-reader__next');
		var elStatus = qs(root, '.hwbl-bible-reader__status');
		var elReference = qs(root, '.hwbl-bible-reader__reference');
		var elContent = qs(root, '.hwbl-bible-reader__content');
		var elCopyright = qs(root, '.hwbl-bible-reader__copyright');
		var elGotoForm = qs(root, '.hwbl-bible-reader__goto');
		var elGotoInput = qs(root, '.hwbl-bible-reader__goto-input');
		var elSearchForm = qs(root, '.hwbl-bible-reader__search');
		var elSearchInput = qs(root, '.hwbl-bible-reader__search-input');
		var elSearchResults = qs(root, '.hwbl-bible-reader__search-results');
		var elChips = qs(root, '.hwbl-bible-reader__context-chips');
		var elCrossrefs = qs(root, '.hwbl-bible-reader__crossrefs');
		var elCrossrefsList = qs(root, '.hwbl-bible-reader__crossrefs-list');
		var audioCues = [];
		var audioCueSource = '';
		var lastAudioVerse = 0;

		function setStatus(msg) {
			if (elStatus) {
				elStatus.textContent = msg || '';
			}
		}

		function fillTranslations() {
			if (!elTranslation || !cfg.translations) {
				return;
			}
			elTranslation.innerHTML = '';
			Object.keys(cfg.translations).forEach(function (slug) {
				var opt = document.createElement('option');
				opt.value = slug;
				opt.textContent = cfg.translations[slug];
				if (slug === state.translation) {
					opt.selected = true;
				}
				elTranslation.appendChild(opt);
			});
		}

		function fillBooks() {
			if (!elBook) {
				return;
			}
			elBook.innerHTML = '';
			state.books.forEach(function (book) {
				var opt = document.createElement('option');
				opt.value = String(book.id);
				opt.textContent = book.name;
				if (book.id === state.bookId) {
					opt.selected = true;
				}
				elBook.appendChild(opt);
			});
		}

		function fillChapters() {
			if (!elChapter) {
				return;
			}
			var book = state.books.find(function (b) {
				return b.id === state.bookId;
			});
			var count = book ? book.chapters : 1;
			elChapter.innerHTML = '';
			for (var i = 1; i <= count; i += 1) {
				var opt = document.createElement('option');
				opt.value = String(i);
				opt.textContent = String(i);
				if (i === state.chapter) {
					opt.selected = true;
				}
				elChapter.appendChild(opt);
			}
		}

		function updateAudio() {
			if (!elAudio) {
				return;
			}
			var url = state.audioMap[state.narrator];
			if (!url) {
				var keys = Object.keys(state.audioMap);
				if (keys.length) {
					state.narrator = keys[0];
					url = state.audioMap[state.narrator];
					if (elNarrator) {
						elNarrator.value = state.narrator;
					}
				}
			}
			if (url) {
				elAudio.src = url;
			} else {
				elAudio.removeAttribute('src');
			}
		}

		function fillNarrators() {
			if (!elNarrator || !elAudioWrap) {
				return;
			}
			elNarrator.innerHTML = '';
			var keys = Object.keys(state.audioMap);
			if (!keys.length) {
				elAudioWrap.classList.add('is-hidden');
				if (elAudio) {
					elAudio.removeAttribute('src');
				}
				return;
			}
			elAudioWrap.classList.remove('is-hidden');
			keys.forEach(function (key) {
				var opt = document.createElement('option');
				opt.value = key;
				opt.textContent = key.charAt(0).toUpperCase() + key.slice(1);
				if (key === state.narrator) {
					opt.selected = true;
				}
				elNarrator.appendChild(opt);
			});
			updateAudio();
		}

		function scrollToVerse(verseNum) {
			if (!elContent || !verseNum) {
				return;
			}
			var target = elContent.querySelector('[data-verse="' + verseNum + '"]');
			if (target) {
				target.classList.add('is-highlight');
				target.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}

		function renderChapter(payload) {
			state.navigation = payload.navigation || {};
			state.audioMap = payload.audio || {};
			var highlight = payload.highlight_verse || state.verse || 0;

			if (elReference) {
				elReference.textContent = payload.reference || '';
			}
			if (elContent) {
				elContent.innerHTML = '';
				(payload.headings || []).forEach(function (heading) {
					var h = document.createElement('h3');
					h.className = 'hwbl-bible-reader__heading';
					h.textContent = heading;
					elContent.appendChild(h);
				});
				(payload.verses || []).forEach(function (verse) {
					var p = document.createElement('p');
					p.className = 'hwbl-bible-reader__verse';
					p.setAttribute('data-verse', String(verse.number));
					if (highlight && verse.number === highlight) {
						p.classList.add('is-highlight');
					}
					var num = document.createElement('sup');
					num.className = 'hwbl-bible-reader__verse-num';
					num.textContent = String(verse.number);
					p.appendChild(num);
					p.appendChild(document.createTextNode(verse.text));
					elContent.appendChild(p);
				});
			}
			if (elCopyright) {
				elCopyright.textContent = payload.copyright || '';
			}
			if (elPrev) {
				elPrev.disabled = !state.navigation.prev;
			}
			if (elNext) {
				elNext.disabled = !state.navigation.next;
			}
			fillNarrators();

			root.dataset.translation = state.translation;
			root.dataset.book = String(state.bookId);
			root.dataset.chapter = String(state.chapter);
			root.dataset.verse = highlight ? String(highlight) : '';

			var memorizeBar = qs(root, '.hwbl-bible-reader__memorize-bar');
			var memorizePanel = qs(root, '.hwbl-bible-reader__memorize-panel');
			if (memorizePanel) {
				memorizePanel.hidden = true;
				memorizePanel.innerHTML = '';
			}
			if (memorizeBar) {
				memorizeBar.hidden = !highlight;
				var shareBtn = qs(root, '.hwbl-bible-reader__share-btn');
				if (shareBtn && highlight) {
					var verseEl = elContent
						? elContent.querySelector(
								'.hwbl-bible-reader__verse[data-verse="' + highlight + '"]'
						  )
						: null;
					var verseText = verseEl
						? (verseEl.textContent || '').replace(/^\s*\d+\s*/, '').trim()
						: '';
					var book = state.books.find(function (b) {
						return b.id === state.bookId;
					});
					var ref =
						(book && book.name ? book.name : 'Book ' + state.bookId) +
						' ' +
						state.chapter +
						':' +
						highlight;
					shareBtn.setAttribute('data-verse', verseText);
					shareBtn.setAttribute('data-ref', ref);
					shareBtn.hidden = !verseText;
					var shareMsg =
						ref +
						'\n' +
						verseText +
						'\n' +
						(window.location.origin || '');
					var wa = qs(root, '.hwbl-bible-reader__share-wa');
					var sms = qs(root, '.hwbl-bible-reader__share-sms');
					if (wa) {
						wa.href = 'https://wa.me/?text=' + encodeURIComponent(shareMsg);
						wa.hidden = !verseText;
					}
					if (sms) {
						sms.href = 'sms:?&body=' + encodeURIComponent(shareMsg);
						sms.hidden = !verseText;
					}
				} else if (shareBtn) {
					shareBtn.hidden = true;
					var waHide = qs(root, '.hwbl-bible-reader__share-wa');
					var smsHide = qs(root, '.hwbl-bible-reader__share-sms');
					if (waHide) {
						waHide.hidden = true;
					}
					if (smsHide) {
						smsHide.hidden = true;
					}
				}
			}

			if (highlight) {
				window.requestAnimationFrame(function () {
					scrollToVerse(highlight);
				});
			}

			loadContextChips();
			if (highlight) {
				loadCrossRefs(highlight);
			} else if (elCrossrefs) {
				elCrossrefs.hidden = true;
			}
			bindAudioCues(payload);
		}

		function loadContextChips() {
			if (!elChips || !cfg.restUrl) {
				return;
			}
			fetchJson(
				cfg.restUrl +
					'bible/book-intro?book_id=' +
					encodeURIComponent(String(state.bookId)) +
					'&chapter=' +
					encodeURIComponent(String(state.chapter))
			)
				.then(function (body) {
					var intro = body.intro;
					if (!intro || !(intro.chips || []).length) {
						elChips.hidden = true;
						elChips.innerHTML = '';
						return;
					}
					elChips.hidden = false;
					var churchBadge = intro.church_intro
						? '<p class="hwbl-bible-reader__chip-church">' +
						  (intro.church_series
								? 'Church intro · ' + intro.church_series
								: 'Church intro') +
						  '</p>'
						: '';
					elChips.innerHTML =
						churchBadge +
						'<div class="hwbl-bible-reader__chips">' +
						(intro.chips || [])
							.map(function (chip) {
								return (
									'<button type="button" class="hwbl-bible-reader__chip' +
									(chip.key === 'church' ? ' is-church' : '') +
									'" data-chip="' +
									chip.key +
									'" title="' +
									(chip.value || '').replace(/"/g, '&quot;') +
									'"><span>' +
									chip.label +
									'</span> ' +
									(chip.key === 'church' ? '' : chip.value || '') +
									'</button>'
								);
							})
							.join('') +
						'</div>' +
						(intro.blurb
							? '<p class="hwbl-bible-reader__chip-blurb" hidden data-default-blurb="1">' +
							  intro.blurb +
							  '</p>'
							: '');
					var blurb = elChips.querySelector('.hwbl-bible-reader__chip-blurb');
					elChips.querySelectorAll('.hwbl-bible-reader__chip').forEach(function (btn) {
						btn.addEventListener('click', function () {
							if (!blurb) {
								return;
							}
							var key = btn.getAttribute('data-chip');
							if (key === 'church' && intro.church_body) {
								blurb.textContent = intro.church_body;
							} else if (intro.pack_blurb && key !== 'church') {
								blurb.textContent = intro.chips
									.filter(function (c) {
										return c.key === key;
									})
									.map(function (c) {
										return c.value;
									})[0] || intro.pack_blurb;
							}
							blurb.hidden = !blurb.hidden;
						});
					});
					if (intro.church_intro && blurb) {
						blurb.hidden = false;
					}
				})
				.catch(function () {
					elChips.hidden = true;
				});
		}

		function loadCrossRefs(verseNum) {
			if (!elCrossrefs || !elCrossrefsList || !cfg.restUrl || !verseNum) {
				return;
			}
			fetchJson(
				cfg.restUrl +
					'bible/cross-refs?book_id=' +
					encodeURIComponent(String(state.bookId)) +
					'&chapter=' +
					encodeURIComponent(String(state.chapter)) +
					'&verse=' +
					encodeURIComponent(String(verseNum))
			)
				.then(function (body) {
					var refs = body.refs || [];
					elCrossrefsList.innerHTML = '';
					if (!refs.length) {
						elCrossrefs.hidden = true;
						return;
					}
					elCrossrefs.hidden = false;
					refs.forEach(function (ref) {
						var li = document.createElement('li');
						var a = document.createElement('a');
						a.href = '#';
						a.textContent = ref.reference || ref.book_id + ' ' + ref.chapter + ':' + ref.verse;
						a.addEventListener('click', function (event) {
							event.preventDefault();
							goTo(ref.book_id, ref.chapter, ref.verse);
						});
						li.appendChild(a);
						elCrossrefsList.appendChild(li);
					});
				})
				.catch(function () {
					elCrossrefs.hidden = true;
				});
		}

		function setAudioHighlight(verseNum) {
			if (!elContent || !verseNum || verseNum === lastAudioVerse) {
				return;
			}
			lastAudioVerse = verseNum;
			elContent.querySelectorAll('.hwbl-bible-reader__verse.is-audio').forEach(function (node) {
				node.classList.remove('is-audio');
			});
			var target = elContent.querySelector('[data-verse="' + verseNum + '"]');
			if (target) {
				target.classList.add('is-audio');
				target.classList.add('is-highlight');
			}
		}

		function bindAudioCues(payload) {
			audioCues = [];
			audioCueSource = '';
			lastAudioVerse = 0;
			if (!elAudio || !cfg.restUrl) {
				return;
			}

			function fetchCues() {
				var duration = elAudio.duration && isFinite(elAudio.duration) ? elAudio.duration : 0;
				fetchJson(
					cfg.restUrl +
						'bible/audio-cues?book_id=' +
						encodeURIComponent(String(state.bookId)) +
						'&chapter=' +
						encodeURIComponent(String(state.chapter)) +
						'&translation=' +
						encodeURIComponent(state.translation) +
						(duration ? '&duration=' + encodeURIComponent(String(duration)) : '')
				)
					.then(function (body) {
						audioCues = body.cues || [];
						audioCueSource = body.source || '';
					})
					.catch(function () {
						audioCues = [];
					});
			}

			fetchCues();

			if (elAudio.dataset.hwblCueBound === '1') {
				return;
			}
			elAudio.dataset.hwblCueBound = '1';
			elAudio.addEventListener('timeupdate', function () {
				if (!audioCues.length) {
					return;
				}
				var t = elAudio.currentTime || 0;
				var hit = null;
				for (var i = 0; i < audioCues.length; i++) {
					var cue = audioCues[i];
					if (t >= cue.start && t < cue.end) {
						hit = cue.verse;
						break;
					}
				}
				if (hit) {
					setAudioHighlight(hit);
				}
			});
			elAudio.addEventListener('loadedmetadata', function () {
				fetchCues();
			});
		}

		function loadBooks() {
			var url = cfg.restUrl + 'bible/books?translation=' + encodeURIComponent(state.translation);
			return fetchJson(url).then(function (data) {
				state.books = data.books || [];
				if (state.books.length && !state.books.some(function (b) { return b.id === state.bookId; })) {
					state.bookId = state.books[0].id;
				}
				fillBooks();
				fillChapters();
			});
		}

		function loadChapter() {
			setStatus(i18n.loading || 'Loading…');
			var url =
				cfg.restUrl +
				'bible/chapter?book_id=' +
				encodeURIComponent(String(state.bookId)) +
				'&chapter=' +
				encodeURIComponent(String(state.chapter)) +
				'&translation=' +
				encodeURIComponent(state.translation);
			if (state.verse) {
				url += '&verse=' + encodeURIComponent(String(state.verse));
			}
			return fetchJson(url)
				.then(function (payload) {
					setStatus('');
					renderChapter(payload);
				})
				.catch(function () {
					setStatus(i18n.error || 'Could not load chapter.');
				});
		}

		function goTo(bookId, chapter, verse) {
			state.bookId = bookId;
			state.chapter = chapter;
			state.verse = verse || 0;
			fillBooks();
			fillChapters();
			loadChapter();
		}

		function parseReference(text) {
			var url = cfg.restUrl + 'bible/parse?reference=' + encodeURIComponent(text);
			return fetchJson(url);
		}

		function renderSearchResults(results) {
			if (!elSearchResults) {
				return;
			}
			elSearchResults.innerHTML = '';
			if (!results || !results.length) {
				elSearchResults.hidden = false;
				var empty = document.createElement('li');
				empty.className = 'hwbl-bible-reader__search-empty';
				empty.textContent = i18n.searchEmpty || 'No results found.';
				elSearchResults.appendChild(empty);
				return;
			}
			elSearchResults.hidden = false;
			results.forEach(function (row) {
				var li = document.createElement('li');
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'hwbl-bible-reader__search-hit';
				btn.innerHTML = '<strong>' + row.passage + '</strong><span>' + (row.preview || '') + '</span>';
				btn.addEventListener('click', function () {
					goTo(row.book_id, row.chapter, row.verse || 0);
				});
				li.appendChild(btn);
				elSearchResults.appendChild(li);
			});
		}

		fillTranslations();
		loadBooks().then(loadChapter);

		if (elTranslation) {
			elTranslation.addEventListener('change', function () {
				state.translation = elTranslation.value;
				root.dataset.translation = state.translation;
				state.verse = 0;
				if (prefs && prefs.saveTranslation) {
					prefs.saveTranslation(state.translation);
				}
				loadBooks().then(loadChapter);
			});
		}
		if (elBook) {
			elBook.addEventListener('change', function () {
				state.bookId = parseInt(elBook.value, 10);
				state.chapter = 1;
				state.verse = 0;
				fillChapters();
				loadChapter();
			});
		}
		if (elChapter) {
			elChapter.addEventListener('change', function () {
				state.chapter = parseInt(elChapter.value, 10);
				state.verse = 0;
				loadChapter();
			});
		}
		if (elNarrator) {
			elNarrator.addEventListener('change', function () {
				state.narrator = elNarrator.value;
				updateAudio();
			});
		}
		if (elPrev) {
			elPrev.addEventListener('click', function () {
				if (state.navigation.prev) {
					goTo(state.navigation.prev.book_id, state.navigation.prev.chapter, 0);
				}
			});
		}
		if (elNext) {
			elNext.addEventListener('click', function () {
				if (state.navigation.next) {
					goTo(state.navigation.next.book_id, state.navigation.next.chapter, 0);
				}
			});
		}
		if (elGotoForm) {
			elGotoForm.addEventListener('submit', function (event) {
				event.preventDefault();
				var text = elGotoInput ? elGotoInput.value.trim() : '';
				if (!text) {
					return;
				}
				parseReference(text)
					.then(function (parsed) {
						goTo(parsed.book_id, parsed.chapter, parsed.verse || 0);
					})
					.catch(function () {
						setStatus(i18n.gotoInvalid || 'Could not understand that reference.');
					});
			});
		}
		if (elSearchForm && features.search) {
			elSearchForm.addEventListener('submit', function (event) {
				event.preventDefault();
				var q = elSearchInput ? elSearchInput.value.trim() : '';
				if (!q) {
					return;
				}
				setStatus(i18n.loading || 'Loading…');
				var url =
					cfg.restUrl +
					'bible/search?q=' +
					encodeURIComponent(q) +
					'&translation=' +
					encodeURIComponent(state.translation);
				fetchJson(url)
					.then(function (data) {
						setStatus('');
						renderSearchResults(data.results || []);
					})
					.catch(function () {
						setStatus(i18n.searchEmpty || 'No results found.');
					});
			});
		}

		var elConcordanceBtn = qs(root, '.hwbl-bible-reader__concordance-btn');
		var elConcordancePanel = qs(root, '.hwbl-bible-reader__concordance-panel');
		if (elConcordanceBtn && elConcordancePanel && features.concordance) {
			elConcordanceBtn.addEventListener('click', function () {
				elConcordancePanel.hidden = !elConcordancePanel.hidden;
				if (!elConcordancePanel.hidden) {
					var widget = elConcordancePanel.querySelector('.hwbl-bible-concordance');
					if (widget && window.hwblBibleConcordanceApi) {
						window.hwblBibleConcordanceApi.initWidget(widget);
						var select = widget.querySelector('.hwbl-bible-concordance__translation');
						if (select && state.translation) {
							var opt = select.querySelector('option[value="' + state.translation + '"]');
							if (opt) {
								select.value = state.translation;
							}
						}
					}
				}
			});
		}

		root._hwblGoTo = goTo;
		if (!window.hwblBibleReaderApi) {
			window.hwblBibleReaderApi = {};
		}
		window.hwblBibleReaderApi.goTo = function (bookId, chapter, verse) {
			var active = document.querySelector('.hwbl-bible-reader');
			if (active && typeof active._hwblGoTo === 'function') {
				active._hwblGoTo(bookId, chapter, verse || 0);
			}
		};
		window.hwblBibleReaderApi.loadCrossRefs = function (verse) {
			loadCrossRefs(verse || state.verse);
		};
		window.hwblBibleReaderApi.compareVerse = function (verse) {
			state.verse = verse || state.verse;
			var compareBtn = qs(root, '.hwbl-bible-reader__compare-btn, [data-hwbl-compare]');
			if (compareBtn) {
				compareBtn.click();
			} else if (window.hwblTranslationComparison && window.hwblTranslationComparison.open) {
				window.hwblTranslationComparison.open({
					book_id: state.bookId,
					chapter: state.chapter,
					verse: state.verse,
					translation: state.translation,
				});
			} else {
				setStatus(i18n.compareHint || 'Open Compare from the reader toolbar to view parallel translations.');
			}
		};
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-reader').forEach(initReader);
	});
})();
