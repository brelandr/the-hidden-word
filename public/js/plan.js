/**
 * Reading plan front-end: start / advance / explain / study / journal.
 */
(function () {
	'use strict';

	function statusEl(root) {
		return root.querySelector('.hwbl-plan__status');
	}

	function setStatus(root, msg) {
		var el = statusEl(root);
		if (el) {
			el.textContent = msg || '';
		}
	}

	function getTradition(root) {
		var fromRoot = (root.getAttribute('data-tradition') || '').trim();
		try {
			if (window.HWBLUserPreferences && typeof window.HWBLUserPreferences.readStoredTradition === 'function') {
				return window.HWBLUserPreferences.readStoredTradition() || fromRoot;
			}
			return window.localStorage.getItem('thw_ai_tradition_preset') || fromRoot;
		} catch (e) {
			return fromRoot;
		}
	}

	function getStudyStyle(root) {
		var select = root.querySelector('.hwbl-plan-study-style');
		return select && select.value ? select.value : 'devotional';
	}

	function saveStudyStyle(root, style) {
		var config = window.hwblUserPrefs || {};
		if (!config.loggedIn || !config.restUrl) {
			try {
				window.localStorage.setItem(config.studyStyleStorageKey || 'hwbl_study_style', style);
			} catch (e) {
				/* ignore */
			}
			return;
		}
		postAbsolute(root, config.restUrl, { studyStyle: style }).catch(function () {
			/* preference remains selected locally */
		});
	}

	function rest(root, path, method, body) {
		var base = root.getAttribute('data-rest-url') || '';
		var nonce = root.getAttribute('data-nonce') || '';
		var url = path ? base.replace(/\/?$/, '') + path : base;
		var opts = {
			method: method || 'GET',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
		};
		if (body !== undefined && method && method !== 'GET') {
			opts.body = JSON.stringify(body);
		}
		return fetch(url, opts).then(function (res) {
			return res.json().then(function (payload) {
				if (!res.ok) {
					var msg =
						(payload && payload.message) ||
						'Request failed (' + res.status + ')';
					var err = new Error(msg);
					err.status = res.status;
					err.body = payload;
					throw err;
				}
				return payload;
			});
		});
	}

	function postAbsolute(root, url, body) {
		var nonce = root.getAttribute('data-nonce') || '';
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
			body: JSON.stringify(body || {}),
		}).then(function (res) {
			return res.json().then(function (payload) {
				if (!res.ok) {
					var msg =
						(payload && payload.message) ||
						'Request failed (' + res.status + ')';
					var err = new Error(msg);
					err.status = res.status;
					err.body = payload;
					throw err;
				}
				return payload;
			});
		});
	}

	function onStart(root) {
		setStatus(root, 'Starting…');
		rest(root, '/start', 'POST', {})
			.then(function () {
				window.location.reload();
			})
			.catch(function (err) {
				setStatus(root, err.message || 'Could not start plan.');
			});
	}

	function onAdvance(root) {
		setStatus(root, 'Saving…');
		rest(root, '/advance', 'POST', {})
			.then(function (body) {
				if (body && body.completed) {
					setStatus(root, 'Plan complete — well done!');
					window.setTimeout(function () {
						window.location.reload();
					}, 800);
					return;
				}
				window.location.reload();
			})
			.catch(function (err) {
				setStatus(root, err.message || 'Could not advance.');
			});
	}

	function daySection(root) {
		return root.querySelector('.hwbl-plan-day');
	}

	function escapeHtml(s) {
		return String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function updateNavUi(root) {
		var day = daySection(root);
		if (!day) {
			return;
		}
		var nav = day.querySelector('.hwbl-plan-day__nav');
		var note = day.querySelector('.hwbl-plan-day__review-note');
		var advance = root.querySelector('.hwbl-plan-advance');
		var prevBtn = day.querySelector('.hwbl-plan-day-prev');
		var nextBtn = day.querySelector('.hwbl-plan-day-next');
		var todayBtn = day.querySelector('.hwbl-plan-day-today');
		var preview = root.getAttribute('data-preview') === '1';
		var viewing = parseInt(root.getAttribute('data-viewing-day') || '0', 10);
		var current = parseInt(root.getAttribute('data-current-day') || '0', 10);
		var length = parseInt(root.getAttribute('data-plan-length') || '0', 10);

		if (nav) {
			nav.hidden = preview || length < 2;
		}
		if (prevBtn) {
			prevBtn.disabled = viewing <= 1;
		}
		if (nextBtn) {
			nextBtn.disabled = length > 0 ? viewing >= length : true;
		}
		if (todayBtn) {
			todayBtn.hidden = !current || viewing === current;
		}
		if (note) {
			if (!preview && current > 0 && viewing > 0 && viewing !== current) {
				note.hidden = false;
				note.textContent =
					'Reviewing day ' +
					viewing +
					' (your progress stays on day ' +
					current +
					').';
			} else {
				note.hidden = true;
				note.textContent = '';
			}
		}
		if (advance) {
			advance.hidden = preview || !current || viewing !== current;
		}

		root.querySelectorAll('.hwbl-plan__day-btn').forEach(function (btn) {
			var n = parseInt(btn.getAttribute('data-day') || '0', 10);
			btn.classList.toggle('is-viewing', n === viewing);
		});
	}

	function applyDayPayload(root, payload) {
		var day = daySection(root);
		if (!day || !payload) {
			return;
		}
		var dayNum = parseInt(payload.day || '0', 10);
		root.setAttribute('data-viewing-day', String(dayNum));
		if (payload.plan_length) {
			root.setAttribute('data-plan-length', String(payload.plan_length));
		}
		day.setAttribute('data-day', String(dayNum));
		day.setAttribute('data-book-id', String(payload.book_id || 0));
		day.setAttribute('data-chapter', String(payload.chapter || 0));
		day.setAttribute('data-verse', String(payload.verse || 0));
		day.setAttribute('data-translation', String(payload.translation || ''));
		day.setAttribute('data-verse-ref', String(payload.verse_ref || ''));

		var title = day.querySelector('.hwbl-plan-day__title');
		if (title) {
			title.textContent =
				'Day ' + dayNum + ': ' + (payload.title || 'Reading');
		}

		var verseWrap = day.querySelector('.hwbl-plan-day__verse-wrap');
		if (verseWrap) {
			if (payload.verse_ref || payload.verse_text) {
				var html = '<figure class="hwbl-plan-day__verse">';
				if (payload.verse_ref) {
					html +=
						'<figcaption class="hwbl-plan-day__ref"><strong>' +
						escapeHtml(payload.verse_ref) +
						'</strong>';
					if (payload.translation) {
						html +=
							'<span class="hwbl-plan-day__translation">' +
							escapeHtml(String(payload.translation).toUpperCase()) +
							'</span>';
					}
					html += '</figcaption>';
				}
				if (payload.verse_text) {
					html +=
						'<blockquote class="hwbl-plan-day__verse-text">' +
						escapeHtml(payload.verse_text) +
						'</blockquote>';
				}
				html += '</figure>';
				verseWrap.innerHTML = html;
			} else {
				verseWrap.innerHTML = '';
			}
		}

		var body = day.querySelector('.hwbl-plan-day__body');
		if (body) {
			body.innerHTML = payload.body || '';
		}
		var framing = day.querySelector('.hwbl-plan-day__framing');
		if (framing) {
			framing.textContent = payload.framing || '';
			framing.hidden = !payload.framing;
		}
		var wordStudy = day.querySelector('.hwbl-plan-day__word-study');
		if (wordStudy) {
			wordStudy.hidden = !(payload.strongs_words && payload.strongs_words.length);
			wordStudy.open = false;
			wordStudy.removeAttribute('data-loaded');
			var wordBody = wordStudy.querySelector('.hwbl-plan-day__word-study-body');
			if (wordBody) {
				wordBody.innerHTML = '';
			}
		}
		var compare = day.querySelector('.hwbl-plan-day__compare');
		var compareButton = day.querySelector('.hwbl-plan-compare');
		if (compare) {
			compare.hidden = true;
			compare.innerHTML = '';
		}
		if (compareButton) {
			compareButton.hidden = !payload.verse_ref;
			compareButton.setAttribute('aria-expanded', 'false');
		}

		var studyBody = day.querySelector('.hwbl-plan-day__study-body');
		var studyStatus = day.querySelector('.hwbl-plan-day__study-status');
		if (studyBody) {
			studyBody.hidden = true;
			studyBody.innerHTML = '';
		}
		if (studyStatus) {
			studyStatus.textContent = 'Loading study…';
		}

		var explain = day.querySelector('.hwbl-plan-day__explain');
		var explainBody = day.querySelector('.hwbl-plan-day__explain-body');
		var explainStatus = day.querySelector('.hwbl-plan-day__explain-status');
		if (explain) {
			explain.hidden = !(payload.book_id && payload.verse);
		}
		if (explainBody) {
			explainBody.hidden = true;
			explainBody.innerHTML = '';
		}
		setExplainActionsVisible(day, false);
		if (explainStatus) {
			explainStatus.textContent = payload.book_id && payload.verse ? 'Loading explanation…' : '';
		}

		var lesson = day.querySelector('.hwbl-plan-day__lesson-link');
		if (lesson) {
			var link = lesson.querySelector('a');
			if (payload.lesson_url && link) {
				lesson.hidden = false;
				link.href = payload.lesson_url;
			} else {
				lesson.hidden = true;
			}
		}

		var journalInput = day.querySelector('.hwbl-plan-day__journal-input');
		var journalStatus = day.querySelector('.hwbl-plan-day__journal-status');
		if (journalInput) {
			journalInput.value = '';
		}
		if (journalStatus) {
			journalStatus.textContent = '';
		}

		updateNavUi(root);
		loadJournal(root);
		onStudy(root);
		onExplain(root);

		try {
			day.scrollIntoView({ behavior: 'smooth', block: 'start' });
		} catch (e) {
			/* ignore */
		}
	}

	function showDay(root, dayNum) {
		dayNum = parseInt(dayNum || '0', 10);
		if (dayNum < 1) {
			return;
		}
		var preview = root.getAttribute('data-preview') === '1';
		if (preview) {
			setStatus(root, 'Start the plan to move between days.');
			return;
		}
		var viewing = parseInt(root.getAttribute('data-viewing-day') || '0', 10);
		if (viewing === dayNum) {
			updateNavUi(root);
			return;
		}
		setStatus(root, 'Loading day ' + dayNum + '…');
		rest(root, '/days/' + encodeURIComponent(String(dayNum)), 'GET')
			.then(function (payload) {
				setStatus(root, '');
				applyDayPayload(root, payload);
			})
			.catch(function (err) {
				setStatus(root, err.message || 'Could not load that day.');
			});
	}

	function setExplainActionsVisible(day, visible) {
		var actions = day.querySelector('.hwbl-plan-day__explain-actions');
		if (actions) {
			actions.hidden = !visible;
		}
		if (visible) {
			bindExplainJournal(rootFromDay(day), day);
		}
	}

	function rootFromDay(day) {
		return day ? day.closest('.hwbl-plan') : null;
	}

	function bindExplainJournal(root, day) {
		if (!root || !day || !window.HWBLJournalExport) {
			return;
		}
		var actions = day.querySelector('.hwbl-plan-day__explain-actions');
		if (!actions) {
			return;
		}
		var mount = actions.querySelector('.hwbl-journal-export-mount');
		if (!mount) {
			return;
		}
		var title =
			plainTextFromEl(root.querySelector('.hwbl-plan__title')) ||
			'Verse explanation';
		window.HWBLJournalExport.mountMenu(
			mount,
			function () {
				return buildExplainCopy(root);
			},
			title,
			root,
			function (msg) {
				setShareStatus(root, msg);
			}
		);
	}

	function onExplain(root) {
		var day = daySection(root);
		if (!day) {
			return;
		}
		var status = day.querySelector('.hwbl-plan-day__explain-status');
		var body = day.querySelector('.hwbl-plan-day__explain-body');
		var url = root.getAttribute('data-explain-url') || '';
		var bookId = parseInt(day.getAttribute('data-book-id') || '0', 10);
		var verse = parseInt(day.getAttribute('data-verse') || '0', 10);
		setExplainActionsVisible(day, false);
		if (!url || !bookId || !verse) {
			if (status) {
				status.textContent = '';
			}
			return;
		}
		if (status) {
			status.textContent = 'Loading explanation…';
		}
		postAbsolute(root, url, {
			book_id: bookId,
			chapter: parseInt(day.getAttribute('data-chapter') || '0', 10),
			verse: verse,
			translation: day.getAttribute('data-translation') || '',
			scope: 'verse',
			tradition: getTradition(root),
		})
			.then(function (payload) {
				if (status) {
					status.textContent = '';
				}
				if (body && payload.content) {
					body.hidden = false;
					body.innerHTML = payload.content;
					setExplainActionsVisible(day, true);
				} else if (status) {
					status.textContent = 'Explanation unavailable.';
				}
			})
			.catch(function (err) {
				if (status) {
					if (err && err.status === 401) {
						status.textContent =
							'Sign in once to generate this explanation. After that, everyone can read it here.';
					} else {
						status.textContent = err.message || 'Could not load explanation.';
					}
				}
			});
	}

	function plainTextFromEl(el) {
		if (!el) {
			return '';
		}
		return String(el.innerText || el.textContent || '')
			.replace(/\s+\n/g, '\n')
			.trim();
	}

	function setShareStatus(root, msg) {
		var day = daySection(root);
		var el = day ? day.querySelector('.hwbl-plan-day__share-status') : null;
		if (el) {
			el.textContent = msg || '';
		}
	}

	function copyToClipboard(text) {
		text = String(text || '').trim();
		if (!text) {
			return Promise.reject(new Error('Nothing to copy.'));
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		return Promise.reject(new Error('Copy not supported in this browser.'));
	}

	function shareOrCopy(text, title) {
		text = String(text || '').trim();
		if (!text) {
			return Promise.reject(new Error('Nothing to share.'));
		}
		if (navigator.share) {
			return navigator
				.share({ title: title || document.title, text: text })
				.catch(function () {
					return copyToClipboard(text);
				});
		}
		return copyToClipboard(text);
	}

	function siteAttribution(root) {
		var site =
			(root && root.getAttribute('data-site-name')) ||
			document.body.getAttribute('data-site-name') ||
			'';
		site = String(site || '').trim();
		return site
			? '— via ' + site + ', Hidden Word Bible Lessons'
			: '— via Hidden Word Bible Lessons';
	}

	function withJournalAttribution(root, text) {
		text = String(text || '').trim();
		var line = siteAttribution(root);
		return text ? text + '\n\n' + line : line;
	}

	function dayOneUrl(text, tags) {
		var journal = readJournalPref('dayone');
		var parts = [];
		if (journal) {
			parts.push('journal=' + encodeURIComponent(journal));
		}
		parts.push('entry=' + encodeURIComponent(String(text || '')));
		if (tags && tags.length) {
			parts.push('tags=' + encodeURIComponent(tags.join(',')));
		}
		return 'dayone://post?' + parts.join('&');
	}

	function quillDayUrl(text, title) {
		var journal = readJournalPref('quillday');
		var parts = [];
		if (journal) {
			parts.push('journal=' + encodeURIComponent(journal));
		}
		parts.push('body=' + encodeURIComponent(String(text || '')));
		if (title) {
			parts.push('title=' + encodeURIComponent(String(title)));
		}
		return 'quillday://new?' + parts.join('&');
	}

	function readJournalPref(kind) {
		try {
			var key = kind === 'dayone' ? 'hwbl_dayone_journal' : 'hwbl_quillday_journal';
			return String(window.localStorage.getItem(key) || '').trim();
		} catch (e) {
			return '';
		}
	}

	function writeJournalPref(kind, value) {
		try {
			var key = kind === 'dayone' ? 'hwbl_dayone_journal' : 'hwbl_quillday_journal';
			value = String(value || '').trim();
			if (value) {
				window.localStorage.setItem(key, value);
			} else {
				window.localStorage.removeItem(key);
			}
		} catch (e) {
			/* ignore */
		}
	}

	function promptJournalPref(kind) {
		var label = kind === 'dayone' ? 'Day One' : 'QuillDay';
		var current = readJournalPref(kind);
		var next = window.prompt(
			'Preferred ' + label + ' journal name (exact match). Leave blank for the app default.',
			current
		);
		if (next === null) {
			return current;
		}
		writeJournalPref(kind, next);
		return String(next || '').trim();
	}

	function openDeepLinkOrShare(url, text, title) {
		try {
			window.location.href = url;
		} catch (e) {
			/* ignore */
		}
		window.setTimeout(function () {
			if (document.visibilityState === 'visible') {
				shareOrCopy(text, title).catch(function () {
					/* ignore */
				});
			}
		}, 1200);
	}

	function closeJournalMenus(scope) {
		(scope || document).querySelectorAll('.hwbl-plan-add-journal-menu').forEach(function (menu) {
			menu.hidden = true;
			var wrap = menu.parentElement;
			var btn = wrap ? wrap.querySelector('.hwbl-plan-add-journal, .hwbl-add-to-journal') : null;
			if (btn) {
				btn.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function buildAddToJournalMenu(getText, title, root) {
		var wrap = document.createElement('span');
		wrap.className = 'hwbl-plan-add-journal-wrap';
		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'hwbl-btn hwbl-btn-secondary hwbl-plan-add-journal';
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-haspopup', 'true');
		toggle.textContent = 'Add to journal';
		var menu = document.createElement('div');
		menu.className = 'hwbl-plan-add-journal-menu';
		menu.hidden = true;
		menu.setAttribute('role', 'menu');

		function option(label, kind) {
			var b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('role', 'menuitem');
			b.textContent = label;
			b.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				var raw = typeof getText === 'function' ? getText() : String(getText || '');
				var text = withJournalAttribution(root, raw);
				menu.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
				if (!String(raw || '').trim()) {
					setShareStatus(root, 'Nothing to export yet.');
					return;
				}
				if (kind === 'dayone') {
					openDeepLinkOrShare(dayOneUrl(text, ['HiddenWord']), text, title);
					setShareStatus(root, 'Opening Day One…');
				} else if (kind === 'quillday') {
					openDeepLinkOrShare(quillDayUrl(text, title), text, title);
					setShareStatus(root, 'Opening QuillDay…');
				} else {
					shareOrCopy(text, title || 'Journal')
						.then(function () {
							setShareStatus(root, 'Shared.');
						})
						.catch(function (err) {
							setShareStatus(root, (err && err.message) || 'Could not share.');
						});
				}
			});
			menu.appendChild(b);
		}

		option('Day One', 'dayone');
		option('QuillDay', 'quillday');
		option('Other journal app', 'other');

		function prefOption(label, kind) {
			var b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('role', 'menuitem');
			b.className = 'hwbl-plan-add-journal-pref';
			function refreshLabel() {
				var current = readJournalPref(kind);
				b.textContent = current
					? label + ': ' + current + ' (change)'
					: label + ' journal (set)';
			}
			refreshLabel();
			b.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				promptJournalPref(kind);
				refreshLabel();
			});
			menu.appendChild(b);
		}
		prefOption('QuillDay', 'quillday');
		prefOption('Day One', 'dayone');

		toggle.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			var open = menu.hidden;
			closeJournalMenus(root || document);
			menu.hidden = !open;
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		wrap.appendChild(toggle);
		wrap.appendChild(menu);
		return wrap;
	}

	function buildJournalEntryExportText(item, root) {
		var planTitle = plainTextFromEl(root.querySelector('.hwbl-plan__title'));
		var day = daySection(root);
		var dayNum = day ? day.getAttribute('data-day') || '' : '';
		var parts = [];
		if (planTitle) {
			parts.push(planTitle);
		}
		if (dayNum) {
			parts.push('Day ' + dayNum);
		}
		parts.push('My reflection:\n' + String(item.entry || '').trim());
		if (item.ai_reply && !item.flagged_crisis) {
			parts.push('Guidance:\n' + String(item.ai_reply || '').trim());
		}
		return parts.filter(Boolean).join('\n\n');
	}

	function buildVerseCopy(root) {
		var day = daySection(root);
		if (!day) {
			return '';
		}
		var ref = day.getAttribute('data-verse-ref') || '';
		var translation = (day.getAttribute('data-translation') || '').toUpperCase();
		var verseEl = day.querySelector('.hwbl-plan-day__verse-text');
		var verse = plainTextFromEl(verseEl);
		var parts = [];
		if (verse) {
			parts.push(verse);
		}
		var attr = [ref, translation].filter(Boolean).join(' · ');
		if (attr) {
			parts.push(attr);
		}
		return parts.join('\n\n');
	}

	function buildStudyCopy(root) {
		var day = daySection(root);
		if (!day) {
			return '';
		}
		var title = plainTextFromEl(day.querySelector('.hwbl-plan-day__title'));
		var study = plainTextFromEl(day.querySelector('.hwbl-plan-day__study-body'));
		var body = plainTextFromEl(day.querySelector('.hwbl-plan-day__body'));
		var parts = [];
		if (title) {
			parts.push(title);
		}
		if (study) {
			parts.push(study);
		} else if (body) {
			parts.push(body);
		}
		return parts.join('\n\n');
	}

	function buildAskReplyCopy(root) {
		var day = daySection(root);
		if (!day) {
			return '';
		}
		var replies = day.querySelectorAll('.hwbl-plan-day__journal-reply');
		if (!replies.length) {
			return '';
		}
		return plainTextFromEl(replies[replies.length - 1]);
	}

	function buildDayCopy(root) {
		var day = daySection(root);
		if (!day) {
			return '';
		}
		var planTitle = plainTextFromEl(root.querySelector('.hwbl-plan__title'));
		var dayTitle = plainTextFromEl(day.querySelector('.hwbl-plan-day__title'));
		var verse = buildVerseCopy(root);
		var body = plainTextFromEl(day.querySelector('.hwbl-plan-day__body'));
		var study = plainTextFromEl(day.querySelector('.hwbl-plan-day__study-body'));
		var ask = buildAskReplyCopy(root);
		var dayNum = day.getAttribute('data-day') || '';
		var url = window.location.href;
		try {
			var u = new URL(window.location.href);
			if (dayNum) {
				u.searchParams.set('hwbl_plan_day', dayNum);
			}
			url = u.toString();
		} catch (e) {
			/* ignore */
		}
		var parts = [];
		if (planTitle) {
			parts.push(planTitle);
		}
		if (dayTitle) {
			parts.push(dayTitle);
		}
		if (verse) {
			parts.push(verse);
		}
		if (body) {
			parts.push(body);
		}
		if (study) {
			parts.push('Study\n' + study);
		}
		if (ask) {
			parts.push('Ask reply\n' + ask);
		}
		parts.push(url);
		return parts.join('\n\n');
	}

	function buildExplainCopy(root) {
		var day = daySection(root);
		if (!day) {
			return '';
		}
		var ref = day.getAttribute('data-verse-ref') || '';
		var explain = plainTextFromEl(day.querySelector('.hwbl-plan-day__explain-body'));
		var parts = [];
		if (ref) {
			parts.push(ref);
		}
		if (explain) {
			parts.push(explain);
		}
		return parts.join('\n\n');
	}

	function bindShareActions(root) {
		var day = daySection(root);
		if (!day || day.getAttribute('data-share-bound') === '1') {
			return;
		}
		day.setAttribute('data-share-bound', '1');

		function handle(kind) {
			var text = '';
			var okMsg = 'Copied.';
			var failEmpty = 'Nothing to copy yet.';
			if (kind === 'verse') {
				text = buildVerseCopy(root);
			} else if (kind === 'study') {
				text = buildStudyCopy(root);
				failEmpty = 'Study is still loading.';
			} else if (kind === 'ask') {
				text = buildAskReplyCopy(root);
				failEmpty = 'No Ask reply yet.';
			} else if (kind === 'day') {
				text = buildDayCopy(root);
			} else if (kind === 'share-day') {
				text = buildDayCopy(root);
				okMsg = 'Shared.';
				shareOrCopy(text, plainTextFromEl(root.querySelector('.hwbl-plan__title')))
					.then(function () {
						setShareStatus(root, okMsg);
					})
					.catch(function (err) {
						setShareStatus(root, (err && err.message) || failEmpty);
					});
				return;
			} else if (kind === 'explain') {
				text = buildExplainCopy(root);
				failEmpty = 'Explanation is still loading.';
			} else if (kind === 'share-explain') {
				text = buildExplainCopy(root);
				okMsg = 'Shared.';
				shareOrCopy(text, 'Verse explanation')
					.then(function () {
						setShareStatus(root, okMsg);
					})
					.catch(function (err) {
						setShareStatus(
							root,
							(err && err.message) || 'Explanation is still loading.'
						);
					});
				return;
			}

			copyToClipboard(text)
				.then(function () {
					setShareStatus(root, okMsg);
				})
				.catch(function (err) {
					setShareStatus(root, (err && err.message) || failEmpty);
				});
		}

		day.addEventListener('click', function (event) {
			var btn = event.target.closest(
				'.hwbl-plan-copy-verse, .hwbl-plan-copy-study, .hwbl-plan-copy-ask, .hwbl-plan-copy-day, .hwbl-plan-share-day, .hwbl-plan-copy-explain, .hwbl-plan-share-explain'
			);
			if (!btn || !day.contains(btn)) {
				return;
			}
			if (btn.classList.contains('hwbl-plan-copy-verse')) {
				handle('verse');
			} else if (btn.classList.contains('hwbl-plan-copy-study')) {
				handle('study');
			} else if (btn.classList.contains('hwbl-plan-copy-ask')) {
				handle('ask');
			} else if (btn.classList.contains('hwbl-plan-copy-day')) {
				handle('day');
			} else if (btn.classList.contains('hwbl-plan-share-day')) {
				handle('share-day');
			} else if (btn.classList.contains('hwbl-plan-copy-explain')) {
				handle('explain');
			} else if (btn.classList.contains('hwbl-plan-share-explain')) {
				handle('share-explain');
			}
		});

		var journalWrap = day.querySelector('.hwbl-plan-add-journal-wrap');
		if (journalWrap && !journalWrap.querySelector('.hwbl-plan-add-journal-menu')) {
			var title = plainTextFromEl(root.querySelector('.hwbl-plan__title')) || 'Reading plan';
			var menu = buildAddToJournalMenu(
				function () {
					var study = buildStudyCopy(root);
					var verse = buildVerseCopy(root);
					var parts = [];
					if (study) {
						parts.push(study);
					}
					if (verse) {
						parts.push(verse);
					}
					return parts.join('\n\n') || buildDayCopy(root);
				},
				title,
				root
			);
			journalWrap.replaceWith(menu);
		}

		document.addEventListener('click', function () {
			closeJournalMenus(day);
		});
	}

	function onStudy(root) {
		var day = daySection(root);
		if (!day) {
			return;
		}
		var dayNum = day.getAttribute('data-day') || '';
		var status = day.querySelector('.hwbl-plan-day__study-status');
		var body = day.querySelector('.hwbl-plan-day__study-body');
		var loggedIn = root.getAttribute('data-logged-in') === '1';
		var tradition = getTradition(root);
		var style = getStudyStyle(root);
		var listen = day.querySelector('.hwbl-plan-listen');
		if (listen) {
			listen.hidden = true;
			listen.textContent = 'Listen';
		}
		if ('speechSynthesis' in window) {
			window.speechSynthesis.cancel();
		}
		var path =
			'/days/' +
			encodeURIComponent(dayNum) +
			'/study?tradition=' +
			encodeURIComponent(tradition) +
			'&style=' +
			encodeURIComponent(style);

		if (status) {
			status.textContent = 'Loading study…';
		}

		var req = loggedIn
			? rest(root, '/days/' + encodeURIComponent(dayNum) + '/study', 'POST', {
					tradition: tradition,
					style: style,
			  })
			: rest(root, path, 'GET').then(function (payload) {
					if (payload && payload.content) {
						return payload;
					}
					// Guest miss: nothing cached yet.
					return null;
			  });

		req
			.then(function (payload) {
				if (!payload || !payload.content) {
					if (status) {
						status.textContent = loggedIn
							? 'Study unavailable right now.'
							: 'Sign in once to generate this study. After that, everyone can read it here.';
					}
					return;
				}
				if (status) {
					status.textContent = '';
				}
				if (body) {
					body.hidden = false;
					body.innerHTML = payload.content;
					if (listen) {
						listen.hidden = !('speechSynthesis' in window);
					}
				}
			})
			.catch(function (err) {
				if (status) {
					if (err && err.status === 401) {
						status.textContent =
							'Sign in once to generate this study. After that, everyone can read it here.';
					} else {
						status.textContent = err.message || 'Could not load study.';
					}
				}
			});
	}

	function bindLearningTools(root) {
		var day = daySection(root);
		if (!day || day.getAttribute('data-learning-tools-bound') === '1') {
			return;
		}
		day.setAttribute('data-learning-tools-bound', '1');
		var styleTimer = 0;
		var styleSelect = day.querySelector('.hwbl-plan-study-style');
		if (styleSelect) {
			if (root.getAttribute('data-logged-in') !== '1') {
				try {
					var storedStyle = window.localStorage.getItem('hwbl_study_style');
					if (storedStyle && styleSelect.querySelector('option[value="' + storedStyle + '"]')) {
						styleSelect.value = storedStyle;
					}
				} catch (e) {
					/* ignore */
				}
			}
			styleSelect.addEventListener('change', function () {
				window.clearTimeout(styleTimer);
				styleTimer = window.setTimeout(function () {
					saveStudyStyle(root, styleSelect.value || 'devotional');
				}, 350);
				onStudy(root);
			});
		}
		day.addEventListener('click', function (event) {
			var compareBtn = event.target.closest('.hwbl-plan-compare');
			if (compareBtn) {
				var panel = day.querySelector('.hwbl-plan-day__compare');
				if (!panel) {
					return;
				}
				if (!panel.hidden) {
					panel.hidden = true;
					compareBtn.setAttribute('aria-expanded', 'false');
					return;
				}
				panel.hidden = false;
				compareBtn.setAttribute('aria-expanded', 'true');
				panel.textContent = 'Loading translations…';
				rest(root, '/days/' + encodeURIComponent(day.getAttribute('data-day') || '') + '/compare', 'GET')
					.then(function (payload) {
						panel.innerHTML = '';
						(payload.translations || []).forEach(function (item) {
							var col = document.createElement('div');
							col.className = 'hwbl-translation-compare__col';
							var label = document.createElement('strong');
							label.textContent = item.translation_label || String(item.translation_code || '').toUpperCase();
							var quote = document.createElement('blockquote');
							quote.textContent = item.text || '';
							col.appendChild(label);
							col.appendChild(quote);
							panel.appendChild(col);
						});
						var cue = document.createElement('p');
						cue.textContent = 'What stands out to you across these translations?';
						panel.appendChild(cue);
					})
					.catch(function (err) {
						panel.textContent = err.message || 'Could not compare translations.';
					});
				return;
			}
			var listenBtn = event.target.closest('.hwbl-plan-listen');
			if (listenBtn && 'speechSynthesis' in window) {
				if (window.speechSynthesis.speaking) {
					window.speechSynthesis.cancel();
					listenBtn.textContent = 'Listen';
				} else {
					var utterance = new SpeechSynthesisUtterance(plainTextFromEl(day.querySelector('.hwbl-plan-day__study-body')));
					utterance.rate = 0.92;
					utterance.onend = function () { listenBtn.textContent = 'Listen'; };
					listenBtn.textContent = 'Stop';
					window.speechSynthesis.speak(utterance);
				}
			}
		});
		var details = day.querySelector('.hwbl-plan-day__word-study');
		if (details) {
			details.addEventListener('toggle', function () {
				if (!details.open || details.getAttribute('data-loaded') === '1') {
					return;
				}
				var panel = details.querySelector('.hwbl-plan-day__word-study-body');
				if (panel) {
					panel.textContent = 'Loading word study…';
				}
				rest(root, '/days/' + encodeURIComponent(day.getAttribute('data-day') || '') + '/word-study', 'GET')
					.then(function (payload) {
						details.setAttribute('data-loaded', '1');
						if (!panel) {
							return;
						}
						panel.innerHTML = '';
						(payload.words || []).forEach(function (item) {
							var article = document.createElement('article');
							var heading = document.createElement('strong');
							heading.textContent = (item.word || '') + ' — ' + (item.number || '');
							var text = document.createElement('p');
							text.textContent = item.definition || item.gloss || '';
							var refs = document.createElement('p');
							refs.textContent = (item.cross_references || []).join(' · ');
							article.appendChild(heading);
							article.appendChild(text);
							article.appendChild(refs);
							panel.appendChild(article);
						});
					})
					.catch(function (err) {
						if (panel) {
							panel.textContent = err.message || 'Could not load word study.';
						}
					});
			});
		}
	}

	function renderJournalEntries(root, day, entries) {
		var list = day.querySelector('.hwbl-plan-day__journal-list');
		if (!list) {
			return;
		}
		list.innerHTML = '';
		(entries || []).forEach(function (item) {
			var article = document.createElement('article');
			article.className =
				'hwbl-plan-day__journal-entry' +
				(item.flagged_crisis ? ' hwbl-plan-day__journal-entry--crisis' : '');
			article.setAttribute('data-entry-id', String(item.id || ''));

			var entry = document.createElement('div');
			entry.className = 'hwbl-plan-day__journal-entry-text';
			entry.textContent = item.entry || '';
			article.appendChild(entry);

			if (item.flagged_crisis && item.ai_reply_html) {
				var crisis = document.createElement('div');
				crisis.className = 'hwbl-plan-day__journal-reply';
				crisis.innerHTML = item.ai_reply_html;
				article.appendChild(crisis);
			} else if (item.ai_reply) {
				var reply = document.createElement('div');
				reply.className = 'hwbl-plan-day__journal-reply';
				reply.innerHTML = item.ai_reply_html || '';
				if (!reply.innerHTML) {
					reply.textContent = item.ai_reply;
				}
				article.appendChild(reply);
			} else {
				var askBtn = document.createElement('button');
				askBtn.type = 'button';
				askBtn.className = 'hwbl-btn hwbl-btn-secondary hwbl-plan-journal-ask-existing';
				askBtn.textContent = 'Ask for biblical guidance';
				article.appendChild(askBtn);
			}

			var exportRow = document.createElement('div');
			exportRow.className = 'hwbl-plan-day__journal-export';
			exportRow.appendChild(
				buildAddToJournalMenu(
					function () {
						return buildJournalEntryExportText(item, root);
					},
					plainTextFromEl(root.querySelector('.hwbl-plan__title')) || 'Journal',
					root
				)
			);
			article.appendChild(exportRow);

			list.appendChild(article);
		});
	}

	function loadJournal(root) {
		var day = daySection(root);
		if (!day || root.getAttribute('data-logged-in') !== '1') {
			return;
		}
		var dayNum = day.getAttribute('data-day') || '';
		rest(root, '/days/' + encodeURIComponent(dayNum) + '/journal', 'GET')
			.then(function (payload) {
				renderJournalEntries(root, day, (payload && payload.entries) || []);
			})
			.catch(function () {
				/* silent on initial load */
			});
	}

	function saveJournal(root, andAsk) {
		var day = daySection(root);
		if (!day) {
			return;
		}
		var input = day.querySelector('.hwbl-plan-day__journal-input');
		var status = day.querySelector('.hwbl-plan-day__journal-status');
		var dayNum = day.getAttribute('data-day') || '';
		var text = input ? String(input.value || '').trim() : '';
		if (!text) {
			if (status) {
				status.textContent = 'Please write something first.';
			}
			return;
		}
		if (status) {
			status.textContent = andAsk ? 'Saving and asking…' : 'Saving…';
		}
		rest(root, '/days/' + encodeURIComponent(dayNum) + '/journal', 'POST', {
			entry: text,
		})
			.then(function (entry) {
				if (input) {
					input.value = '';
				}
				if (!andAsk || entry.flagged_crisis) {
					if (status) {
						status.textContent = entry.flagged_crisis
							? 'Saved — please see the helpline resources below.'
							: 'Saved.';
					}
					return loadJournal(root);
				}
				return rest(
					root,
					'/days/' +
						encodeURIComponent(dayNum) +
						'/journal/' +
						encodeURIComponent(entry.id) +
						'/ask',
					'POST',
					{ tradition: getTradition(root) }
				).then(function () {
					if (status) {
						status.textContent = 'Guidance ready.';
					}
					return loadJournal(root);
				});
			})
			.catch(function (err) {
				if (status) {
					status.textContent = err.message || 'Could not save.';
				}
			});
	}

	function askExisting(root, entryId) {
		var day = daySection(root);
		if (!day || !entryId) {
			return;
		}
		var status = day.querySelector('.hwbl-plan-day__journal-status');
		var dayNum = day.getAttribute('data-day') || '';
		if (status) {
			status.textContent = 'Asking…';
		}
		rest(
			root,
			'/days/' +
				encodeURIComponent(dayNum) +
				'/journal/' +
				encodeURIComponent(entryId) +
				'/ask',
			'POST',
			{ tradition: getTradition(root) }
		)
			.then(function () {
				if (status) {
					status.textContent = 'Guidance ready.';
				}
				return loadJournal(root);
			})
			.catch(function (err) {
				if (status) {
					status.textContent = err.message || 'Could not get guidance.';
				}
			});
	}

	function bind(root) {
		var startBtn = root.querySelector('.hwbl-plan-start');
		var advanceBtn = root.querySelector('.hwbl-plan-advance');
		var saveBtn = root.querySelector('.hwbl-plan-journal-save');
		var askBtn = root.querySelector('.hwbl-plan-journal-ask');
		var list = root.querySelector('.hwbl-plan-day__journal-list');
		var day = daySection(root);
		var prevBtn = day ? day.querySelector('.hwbl-plan-day-prev') : null;
		var nextBtn = day ? day.querySelector('.hwbl-plan-day-next') : null;
		var todayBtn = day ? day.querySelector('.hwbl-plan-day-today') : null;
		var outline = root.querySelector('.hwbl-plan__days');

		if (startBtn) {
			startBtn.addEventListener('click', function () {
				onStart(root);
			});
		}
		if (advanceBtn) {
			advanceBtn.addEventListener('click', function () {
				onAdvance(root);
			});
		}
		if (saveBtn) {
			saveBtn.addEventListener('click', function () {
				saveJournal(root, false);
			});
		}
		if (askBtn) {
			askBtn.addEventListener('click', function () {
				saveJournal(root, true);
			});
		}
		if (list) {
			list.addEventListener('click', function (event) {
				var btn = event.target.closest('.hwbl-plan-journal-ask-existing');
				if (!btn) {
					return;
				}
				var article = btn.closest('.hwbl-plan-day__journal-entry');
				var id = article ? article.getAttribute('data-entry-id') : '';
				askExisting(root, id);
			});
		}
		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				var viewing = parseInt(root.getAttribute('data-viewing-day') || '0', 10);
				showDay(root, viewing - 1);
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				var viewing = parseInt(root.getAttribute('data-viewing-day') || '0', 10);
				showDay(root, viewing + 1);
			});
		}
		if (todayBtn) {
			todayBtn.addEventListener('click', function () {
				var current = parseInt(root.getAttribute('data-current-day') || '0', 10);
				showDay(root, current || 1);
			});
		}
		if (outline) {
			outline.addEventListener('click', function (event) {
				var btn = event.target.closest('.hwbl-plan__day-btn');
				if (!btn || btn.disabled) {
					return;
				}
				showDay(root, btn.getAttribute('data-day'));
			});
		}

		updateNavUi(root);
		bindShareActions(root);
		bindLearningTools(root);
		loadJournal(root);
		onStudy(root);
		onExplain(root);

		try {
			var params = new URLSearchParams(window.location.search);
			var want = parseInt(params.get('hwbl_plan_day') || '0', 10);
			var viewing = parseInt(root.getAttribute('data-viewing-day') || '0', 10);
			if (want > 0 && want !== viewing && root.getAttribute('data-preview') !== '1') {
				showDay(root, want);
			}
		} catch (e) {
			/* ignore */
		}
	}

	function initPlanLists() {
		document.querySelectorAll('.hwbl-plan-list[data-filterable="1"]').forEach(function (root) {
			var select = root.querySelector('.hwbl-plan-list__filter-select');
			var items = root.querySelectorAll('.hwbl-plan-list__item');
			var empty = root.querySelector('.hwbl-plan-list__empty-filter');
			if (!select || !items.length) {
				return;
			}

			function applyFilter(topic) {
				topic = (topic || '').toLowerCase();
				var visible = 0;
				items.forEach(function (li) {
					var t = (li.getAttribute('data-topic') || '').toLowerCase();
					var show = !topic || t === topic;
					li.hidden = !show;
					if (show) {
						visible += 1;
					}
				});
				if (empty) {
					empty.hidden = visible > 0;
				}
			}

			var fromUrl = '';
			try {
				fromUrl = (
					new URLSearchParams(window.location.search).get('topic') || ''
				)
					.trim()
					.toLowerCase();
			} catch (e) {
				fromUrl = '';
			}
			if (fromUrl) {
				var hasOption = false;
				Array.prototype.forEach.call(select.options, function (opt) {
					if (opt.value === fromUrl) {
						hasOption = true;
					}
				});
				if (hasOption) {
					select.value = fromUrl;
				}
			}

			applyFilter(select.value);
			select.addEventListener('change', function () {
				var value = select.value || '';
				applyFilter(value);
				try {
					var url = new URL(window.location.href);
					if (value) {
						url.searchParams.set('topic', value);
					} else {
						url.searchParams.delete('topic');
					}
					window.history.replaceState({}, '', url.toString());
				} catch (e) {
					/* ignore */
				}
			});
		});
	}

	function init() {
		document.querySelectorAll('.hwbl-plan').forEach(bind);
		initPlanLists();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
