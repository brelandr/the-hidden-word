/**
 * Personal Bible notes for the reader (frameworks + autosave when signed in).
 */
(function () {
	'use strict';

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function qsa(root, sel) {
		return Array.prototype.slice.call(root.querySelectorAll(sel));
	}

	function initNotes(root) {
		if (!root || root.dataset.hwblNotesInit === '1') {
			return;
		}
		root.dataset.hwblNotesInit = '1';

		var panel = qs(root, '.hwbl-bible-reader__notes');
		if (!panel) {
			return;
		}

		var cfg = window.hwblBibleReader || {};
		var i18n = cfg.i18n || {};
		var loggedIn = !!cfg.loggedIn;
		var elTitle = qs(panel, '.hwbl-bible-reader__notes-title');
		var elHint = qs(panel, '.hwbl-bible-reader__notes-hint');
		var elScope = qs(panel, '.hwbl-bible-reader__notes-scope');
		var elFramework = qs(panel, '.hwbl-bible-reader__notes-framework');
		var elStatus = qs(panel, '.hwbl-bible-reader__notes-status');
		var elLogin = qs(panel, '.hwbl-bible-reader__notes-login');
		var elComposer = qs(panel, '.hwbl-bible-reader__notes-composer');
		var elContent = qs(root, '.hwbl-bible-reader__content');
		var elLayer = qs(panel, '.hwbl-bible-reader__notes-layer');
		var elLayerMine = qs(panel, '.hwbl-bible-reader__notes-layer-mine');
		var elLayerChurch = qs(panel, '.hwbl-bible-reader__notes-layer-church');
		var elChurchNotes = qs(panel, '.hwbl-bible-reader__church-notes');
		var saveTimer = null;
		var loadToken = 0;
		var chapterNotes = {};
		var churchNotes = [];
		var selectedVerse = parseInt(root.dataset.verse || '0', 10) || 0;
		var dirty = false;
		var layer = 'mine';
		var currentFramework = 'free';

		function setStatus(msg) {
			if (elStatus) {
				elStatus.textContent = msg || '';
			}
		}

		function currentScopeVerse() {
			if (elScope && elScope.value === 'chapter') {
				return 0;
			}
			return selectedVerse > 0 ? selectedVerse : 0;
		}

		function activeFieldsRoot() {
			return qs(panel, '.hwbl-bible-reader__notes-fields[data-framework="' + currentFramework + '"]');
		}

		function showFramework(fw) {
			currentFramework = fw || 'free';
			if (elFramework) {
				elFramework.value = currentFramework;
			}
			qsa(panel, '.hwbl-bible-reader__notes-fields').forEach(function (block) {
				block.hidden = block.getAttribute('data-framework') !== currentFramework;
			});
		}

		function collectSections() {
			var block = activeFieldsRoot();
			var sections = {};
			if (!block) {
				return sections;
			}
			qsa(block, '[data-section]').forEach(function (el) {
				sections[el.getAttribute('data-section')] = el.value || '';
			});
			return sections;
		}

		function fillSections(noteObj) {
			var fw = (noteObj && noteObj.framework) || 'free';
			var sections = (noteObj && noteObj.sections) || {};
			showFramework(fw);
			if (fw === 'free' && (!sections.body || !sections.body.length) && noteObj && noteObj.note) {
				sections = { body: noteObj.note };
			}
			qsa(panel, '.hwbl-bible-reader__notes-fields').forEach(function (block) {
				qsa(block, '[data-section]').forEach(function (el) {
					var key = el.getAttribute('data-section');
					el.value = sections[key] || '';
				});
			});
		}

		function updateTitle() {
			var chapter = parseInt(root.dataset.chapter || '0', 10);
			var verse = currentScopeVerse();
			var label = '';
			if (verse > 0) {
				label = (i18n.notesVerse || 'Note for verse') + ' ' + verse;
			} else if (chapter > 0) {
				label = i18n.notesChapter || 'Note for this chapter';
			} else {
				label = i18n.notesTitle || 'Your notes';
			}
			if (elTitle) {
				elTitle.textContent = label;
			}
			if (elHint) {
				if (!loggedIn) {
					elHint.textContent =
						i18n.notesLogin || 'Sign in to save personal notes on verses and chapters.';
				} else if (elScope && elScope.value === 'verse' && !selectedVerse) {
					elHint.textContent =
						i18n.notesPickVerse || 'Click a verse to take a note, or switch to chapter note.';
				} else {
					elHint.textContent =
						i18n.notesHint || 'Notes are private to your account and sync across devices.';
				}
			}
		}

		function markVersesWithNotes() {
			if (!elContent) {
				return;
			}
			elContent.querySelectorAll('.hwbl-bible-reader__verse').forEach(function (p) {
				var num = parseInt(p.getAttribute('data-verse') || '0', 10);
				if (num && chapterNotes[num]) {
					p.classList.add('has-note');
				} else {
					p.classList.remove('has-note');
				}
			});
			if (chapterNotes[0]) {
				panel.classList.add('has-chapter-note');
			} else {
				panel.classList.remove('has-chapter-note');
			}
		}

		function applyNoteToInput(noteObj) {
			dirty = false;
			fillSections(noteObj || { framework: 'free', sections: {} });
		}

		function renderChurchNotes() {
			if (!elChurchNotes) {
				return;
			}
			var verse = currentScopeVerse();
			var rows = churchNotes.filter(function (row) {
				return (parseInt(row.verse, 10) || 0) === verse || (verse > 0 && (parseInt(row.verse, 10) || 0) === 0);
			});
			if (!rows.length) {
				elChurchNotes.innerHTML = '<p class="description">' + (i18n.churchNotesEmpty || 'No church notes for this passage.') + '</p>';
				return;
			}
			elChurchNotes.innerHTML = rows
				.map(function (row) {
					return (
						'<article class="hwbl-bible-reader__church-note"><h4>' +
						(row.title || row.reference || 'Church note') +
						'</h4><div>' +
						(row.body || '').replace(/\n/g, '<br/>') +
						'</div></article>'
					);
				})
				.join('');
		}

		function setLayer(next) {
			layer = next;
			if (elLayerMine) {
				elLayerMine.classList.toggle('is-active', layer === 'mine');
			}
			if (elLayerChurch) {
				elLayerChurch.classList.toggle('is-active', layer === 'church');
			}
			qsa(panel, '.hwbl-bible-reader__notes-fields, .hwbl-bible-reader__field--notes-framework, .hwbl-bible-reader__field--notes-scope').forEach(function (el) {
				if (layer === 'church' && el.classList.contains('hwbl-bible-reader__notes-fields')) {
					el.hidden = true;
				}
			});
			if (elFramework) {
				elFramework.closest('label').hidden = layer === 'church';
			}
			if (elChurchNotes) {
				elChurchNotes.hidden = layer !== 'church';
			}
			if (layer === 'mine') {
				showFramework(currentFramework);
			} else {
				renderChurchNotes();
			}
		}

		function loadChapterNotes() {
			if (!loggedIn || !cfg.restUrl) {
				return;
			}
			var bookId = parseInt(root.dataset.book || '0', 10);
			var chapter = parseInt(root.dataset.chapter || '0', 10);
			if (!bookId || !chapter) {
				return;
			}
			var token = ++loadToken;
			var url =
				cfg.restUrl +
				'bible/notes?book_id=' +
				encodeURIComponent(String(bookId)) +
				'&chapter=' +
				encodeURIComponent(String(chapter));
			fetch(url, {
				credentials: 'same-origin',
				headers: {
					Accept: 'application/json',
					'X-WP-Nonce': cfg.nonce || '',
				},
			})
				.then(function (res) {
					if (!res.ok) {
						throw new Error('load failed');
					}
					return res.json();
				})
				.then(function (body) {
					if (token !== loadToken) {
						return;
					}
					chapterNotes = {};
					(body.notes || []).forEach(function (row) {
						chapterNotes[parseInt(row.verse, 10) || 0] = row;
					});
					markVersesWithNotes();
					if (!dirty) {
						applyNoteToInput(chapterNotes[currentScopeVerse()] || null);
						setStatus('');
					}
					updateTitle();
				})
				.catch(function () {
					if (token === loadToken) {
						setStatus(i18n.notesError || 'Could not load notes.');
					}
				});

			fetch(
				cfg.restUrl +
					'bible/pastor-notes?book_id=' +
					encodeURIComponent(String(bookId)) +
					'&chapter=' +
					encodeURIComponent(String(chapter)),
				{
					credentials: 'same-origin',
					headers: {
						Accept: 'application/json',
						'X-WP-Nonce': cfg.nonce || '',
					},
				}
			)
				.then(function (res) {
					return res.ok ? res.json() : { notes: [] };
				})
				.then(function (body) {
					churchNotes = body.notes || [];
					if (elLayer) {
						elLayer.hidden = !churchNotes.length;
					}
					if (churchNotes.length && layer === 'church') {
						renderChurchNotes();
					}
				})
				.catch(function () {
					churchNotes = [];
				});
		}

		function saveNote() {
			if (!loggedIn || !cfg.restUrl || layer !== 'mine') {
				return;
			}
			var bookId = parseInt(root.dataset.book || '0', 10);
			var chapter = parseInt(root.dataset.chapter || '0', 10);
			var verse = currentScopeVerse();
			if (!bookId || !chapter) {
				return;
			}
			if (elScope && elScope.value === 'verse' && !selectedVerse) {
				setStatus(i18n.notesPickVerse || 'Click a verse first.');
				return;
			}
			var sections = collectSections();
			setStatus(i18n.notesSaving || 'Saving…');
			fetch(cfg.restUrl + 'bible/notes', {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-WP-Nonce': cfg.nonce || '',
				},
				body: JSON.stringify({
					book_id: bookId,
					chapter: chapter,
					verse: verse,
					framework: currentFramework,
					sections: sections,
					note: sections.body || '',
				}),
			})
				.then(function (res) {
					if (!res.ok) {
						throw new Error('save failed');
					}
					return res.json();
				})
				.then(function (body) {
					dirty = false;
					if (body.deleted) {
						delete chapterNotes[verse];
					} else if (body.note) {
						chapterNotes[verse] = body.note;
					}
					markVersesWithNotes();
					setStatus(i18n.notesSaved || 'Saved');
				})
				.catch(function () {
					setStatus(i18n.notesError || 'Could not save note.');
				});
		}

		function scheduleSave() {
			dirty = true;
			clearTimeout(saveTimer);
			saveTimer = setTimeout(saveNote, 700);
		}

		function onVerseSelected(verseNum) {
			selectedVerse = verseNum || 0;
			if (elScope && selectedVerse > 0) {
				elScope.value = 'verse';
			}
			updateTitle();
			if (!dirty && layer === 'mine') {
				applyNoteToInput(chapterNotes[currentScopeVerse()] || null);
				setStatus('');
			}
			if (layer === 'church') {
				renderChurchNotes();
			}
			if (window.hwblBibleReaderApi && window.hwblBibleReaderApi.loadCrossRefs) {
				window.hwblBibleReaderApi.loadCrossRefs(selectedVerse);
			}
		}

		if (elLogin) {
			elLogin.hidden = loggedIn;
		}
		if (elComposer) {
			elComposer.hidden = !loggedIn;
		}

		qsa(panel, '[data-section]').forEach(function (el) {
			el.addEventListener('input', scheduleSave);
		});
		if (elScope) {
			elScope.addEventListener('change', function () {
				updateTitle();
				if (!dirty) {
					applyNoteToInput(chapterNotes[currentScopeVerse()] || null);
					setStatus('');
				}
			});
		}
		if (elFramework) {
			elFramework.addEventListener('change', function () {
				showFramework(elFramework.value);
				dirty = true;
				scheduleSave();
			});
		}
		if (elLayerMine) {
			elLayerMine.addEventListener('click', function () {
				setLayer('mine');
			});
		}
		if (elLayerChurch) {
			elLayerChurch.addEventListener('click', function () {
				setLayer('church');
			});
		}

		if (elContent) {
			elContent.addEventListener('click', function (event) {
				var verseNode = event.target.closest('.hwbl-bible-reader__verse');
				if (!verseNode) {
					return;
				}
				onVerseSelected(parseInt(verseNode.getAttribute('data-verse') || '0', 10));
			});
		}

		var observer = new MutationObserver(function () {
			selectedVerse = parseInt(root.dataset.verse || '0', 10) || selectedVerse;
			loadChapterNotes();
		});
		observer.observe(root, {
			attributes: true,
			attributeFilter: ['data-book', 'data-chapter', 'data-verse'],
		});

		showFramework('free');
		updateTitle();
		loadChapterNotes();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-reader').forEach(initNotes);
	});
})();
