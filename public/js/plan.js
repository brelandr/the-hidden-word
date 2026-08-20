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
		var path =
			'/days/' +
			encodeURIComponent(dayNum) +
			'/study?tradition=' +
			encodeURIComponent(tradition);

		if (status) {
			status.textContent = 'Loading study…';
		}

		var req = loggedIn
			? rest(root, '/days/' + encodeURIComponent(dayNum) + '/study', 'POST', {
					tradition: tradition,
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

	function renderJournalEntries(day, entries) {
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
				renderJournalEntries(day, (payload && payload.entries) || []);
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
		loadJournal(root);
		onStudy(root);
		onExplain(root);
	}

	function init() {
		document.querySelectorAll('.hwbl-plan').forEach(bind);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
