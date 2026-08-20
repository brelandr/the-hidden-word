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
