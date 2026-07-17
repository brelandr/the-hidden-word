/**
 * Basic fill-in-the-blanks memorization widget with local streak tracking.
 */
(function () {
	'use strict';

	var STREAK_KEY = 'hwbl_mem_streak';

	function cfg() {
		return window.hwblMemorization || {};
	}

	function i18n(key, fallback) {
		var strings = cfg().i18n || {};
		return strings[key] || fallback;
	}

	function tokenize(text) {
		return text.split(/(\s+)/).filter(function (t) { return t.length > 0; });
	}

	function isWord(token) {
		return /\w/.test(token);
	}

	function loadStreak() {
		try {
			var raw = localStorage.getItem(STREAK_KEY);
			return raw ? JSON.parse(raw) : { count: 0, lastDate: '' };
		} catch (err) {
			return { count: 0, lastDate: '' };
		}
	}

	function saveStreak(streak) {
		try {
			localStorage.setItem(STREAK_KEY, JSON.stringify(streak));
		} catch (err) {
			// Ignore quota errors.
		}
	}

	function todayKey() {
		return cfg().today || new Date().toISOString().slice(0, 10);
	}

	function recordPractice(widget) {
		var streak = loadStreak();
		var today = todayKey();

		if (streak.lastDate === today) {
			renderStreak(widget, streak);
			syncServerPractice();
			return;
		}

		var yesterday = new Date(today + 'T12:00:00');
		yesterday.setDate(yesterday.getDate() - 1);
		var yesterdayKey = yesterday.toISOString().slice(0, 10);

		if (streak.lastDate === yesterdayKey) {
			streak.count += 1;
		} else {
			streak.count = 1;
		}
		streak.lastDate = today;
		saveStreak(streak);
		renderStreak(widget, streak);
		syncServerPractice();
	}

	function syncServerPractice() {
		if (!cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
			return;
		}

		fetch(cfg().restUrl + 'memorize/practice', {
			method: 'POST',
			headers: { 'X-WP-Nonce': cfg().nonce }
		}).then(function (res) {
			return res.ok ? res.json() : null;
		}).then(function (data) {
			if (data && data.streak && data.streak.current) {
				document.querySelectorAll('.hwbl-memorization').forEach(function (widget) {
					renderStreak(widget, {
						count: data.streak.current,
						lastDate: data.streak.last_date || todayKey()
					});
				});
			}
		}).catch(function () {
			// Offline fallback keeps localStorage streak.
		});
	}

	function loadServerStreak(widget) {
		if (!cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
			renderStreak(widget, loadStreak());
			return;
		}

		fetch(cfg().restUrl + 'memorize/progress', {
			headers: { 'X-WP-Nonce': cfg().nonce }
		}).then(function (res) {
			return res.ok ? res.json() : null;
		}).then(function (data) {
			if (data && data.streak && data.streak.current) {
				renderStreak(widget, {
					count: data.streak.current,
					lastDate: data.streak.last_date || todayKey()
				});
				return;
			}
			maybeClaimLocalStreak(widget);
		}).catch(function () {
			renderStreak(widget, loadStreak());
		});
	}

	function maybeClaimLocalStreak(widget) {
		var local = loadStreak();
		if (!local.count || !cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
			renderStreak(widget, local);
			return;
		}

		fetch(cfg().restUrl + 'memorize/claim-streak', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg().nonce
			},
			body: JSON.stringify({
				count: local.count,
				last_date: local.lastDate
			})
		}).then(function (res) {
			return res.ok ? res.json() : null;
		}).then(function (data) {
			if (data && data.streak && data.streak.current) {
				renderStreak(widget, {
					count: data.streak.current,
					lastDate: data.streak.last_date || local.lastDate
				});
			} else {
				renderStreak(widget, local);
			}
		}).catch(function () {
			renderStreak(widget, local);
		});
	}

	function renderStreak(widget, streak) {
		var el = widget.querySelector('.hwbl-memorization-streak');
		if (!el || !streak.count) {
			return;
		}

		var label = streak.count === 1
			? i18n('streakDayOne', 'Day 1 streak — great start!')
			: i18n('streakDays', 'Day %d streak — keep going!').replace('%d', String(streak.count));

		el.textContent = label;
		el.hidden = false;

		if (streak.count >= 3 && cfg().streakUpsell) {
			var upsell = widget.querySelector('.hwbl-streak-upsell');
			if (!upsell) {
				upsell = document.createElement('p');
				upsell.className = 'hwbl-streak-upsell';
				upsell.textContent = cfg().streakUpsell;
				el.parentNode.insertBefore(upsell, el.nextSibling);
			}
		}
	}

	function initWidget(widget) {
		var verse = widget.getAttribute('data-verse') || '';
		var container = widget.querySelector('.hwbl-memorization-text');
		if (!container || !verse) {
			return;
		}

		if (!widget.getAttribute('role')) {
			widget.setAttribute('role', 'region');
			widget.setAttribute('aria-label', i18n('practiceRegion', 'Memorization practice'));
		}

		var tokens = tokenize(verse);
		var state = tokens.map(function (t, i) {
			return { text: t, hidden: false, index: i, word: isWord(t) };
		});

		function render() {
			container.innerHTML = '';
			state.forEach(function (item) {
				var span = document.createElement('span');
				span.className = 'hwbl-word' + (item.hidden ? ' is-hidden' : '') + (item.word ? ' is-clickable' : '');
				span.textContent = item.hidden ? '______' : item.text;
				span.dataset.index = String(item.index);
				if (item.word) {
					span.setAttribute('role', 'button');
					span.setAttribute('tabindex', '0');
					if (item.hidden) {
						span.setAttribute('aria-label', i18n('revealWord', 'Reveal hidden word'));
					} else {
						span.setAttribute('aria-label', i18n('hideWord', 'Hide word: %s').replace('%s', item.text.trim()));
					}
				}
				function toggleWord() {
					if (!item.word) {
						return;
					}
					if (!item.hidden) {
						item.hidden = true;
						recordPractice(widget);
					} else {
						item.hidden = false;
					}
					render();
				}
				if (item.word) {
					span.addEventListener('click', toggleWord);
					span.addEventListener('keydown', function (ev) {
						if (ev.key === 'Enter' || ev.key === ' ') {
							ev.preventDefault();
							toggleWord();
						}
					});
				}
				container.appendChild(span);
			});
		}

		function resetState() {
			verse = widget.getAttribute('data-verse') || '';
			tokens = tokenize(verse);
			state = tokens.map(function (t, i) {
				return { text: t, hidden: false, index: i, word: isWord(t) };
			});
			render();
		}

		function shuffleArray(list) {
			var copy = list.slice();
			for (var i = copy.length - 1; i > 0; i--) {
				var j = Math.floor(Math.random() * (i + 1));
				var tmp = copy[i];
				copy[i] = copy[j];
				copy[j] = tmp;
			}
			return copy;
		}

		var scrambleWords = [];
		var scramblePicked = [];

		function getWordTokens() {
			return state.filter(function (item) { return item.word; }).map(function (item) {
				return item.text.trim();
			});
		}

		function resetScramble() {
			scrambleWords = shuffleArray(getWordTokens());
			scramblePicked = [];
			renderScramble();
		}

		function renderScramble() {
			var pool = widget.querySelector('.hwbl-memorization-scramble-pool');
			var build = widget.querySelector('.hwbl-memorization-scramble-build');
			var result = widget.querySelector('.hwbl-memorization-scramble-result');
			if (!pool || !build) {
				return;
			}

			pool.innerHTML = '';
			scrambleWords.forEach(function (word, idx) {
				if (scramblePicked.indexOf(idx) !== -1) {
					return;
				}
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'hwbl-btn hwbl-btn-secondary hwbl-scramble-word';
				btn.textContent = word;
				btn.setAttribute('aria-label', i18n('scrambleWord', 'Add word: %s').replace('%s', word));
				btn.addEventListener('click', function () {
					scramblePicked.push(idx);
					build.textContent = scramblePicked.map(function (pickIdx) {
						return scrambleWords[pickIdx];
					}).join(' ');
					renderScramble();
				});
				pool.appendChild(btn);
			});

			if (result && scramblePicked.length === 0) {
				result.textContent = '';
			}
		}

		function renderFirstLetter() {
			container.innerHTML = '';
			state.forEach(function (item) {
				var span = document.createElement('span');
				span.className = 'hwbl-word';
				if (item.word) {
					span.textContent = item.text.trim().charAt(0) + '…';
				} else {
					span.textContent = item.text;
				}
				container.appendChild(span);
			});
		}

		function setMode(mode) {
			widget.dataset.mode = mode;
			var hint = widget.querySelector('.hwbl-memorization-hint');
			var recall = widget.querySelector('.hwbl-memorization-recall');
			var scramble = widget.querySelector('.hwbl-memorization-scramble');
			var controls = widget.querySelector('.hwbl-memorization-controls');
			var modeBtns = widget.querySelectorAll('.hwbl-mode-btn');

			modeBtns.forEach(function (btn) {
				var active = btn.getAttribute('data-mode') === mode;
				btn.classList.toggle('is-active', active);
				btn.setAttribute('aria-selected', active ? 'true' : 'false');
			});

			if (mode === 'recall') {
				container.hidden = true;
				if (scramble) {
					scramble.hidden = true;
				}
				if (controls) {
					controls.hidden = true;
				}
				if (recall) {
					recall.hidden = false;
				}
				if (hint) {
					hint.textContent = i18n('recallPrompt', 'Type the verse from memory, then check your answer.');
				}
				return;
			}

			if (mode === 'scramble') {
				container.hidden = true;
				if (recall) {
					recall.hidden = true;
				}
				if (controls) {
					controls.hidden = true;
				}
				if (scramble) {
					scramble.hidden = false;
				}
				resetScramble();
				if (hint) {
					hint.textContent = i18n('modeScramble', 'Click shuffled words in verse order.');
				}
				return;
			}

			if (mode === 'review') {
				container.hidden = true;
				if (scramble) {
					scramble.hidden = true;
				}
				if (controls) {
					controls.hidden = true;
				}
				if (recall) {
					recall.hidden = false;
				}
				if (hint) {
					hint.textContent = i18n('reviewPrompt', 'Daily review: type the verse from memory, then rate your recall.');
				}
				return;
			}

			container.hidden = false;
			if (controls) {
				controls.hidden = false;
			}
			if (recall) {
				recall.hidden = true;
			}
			if (scramble) {
				scramble.hidden = true;
			}

			if (mode === 'first-letter') {
				renderFirstLetter();
				if (hint) {
					hint.textContent = i18n('modeFirstLetter', 'First-letter hints');
				}
			} else {
				render();
				if (hint) {
					hint.textContent = i18n('modeHide', 'Click words to hide them and test your memory.');
				}
			}
		}

		function normalizeText(value) {
			return String(value || '').toLowerCase().replace(/[^\w\s]/g, ' ').replace(/\s+/g, ' ').trim();
		}

		function submitReview(quality, mode) {
			var lessonId = parseInt(widget.getAttribute('data-lesson-id') || '0', 10);
			if (!lessonId || !cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
				return;
			}

			fetch(cfg().restUrl + 'memorize/review', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg().nonce
				},
				body: JSON.stringify({
					lesson_id: lessonId,
					quality: quality,
					mode: mode
				})
			}).catch(function () {
				// Ignore sync failures; local practice still works offline.
			});
		}

		function finishReview(quality, mode) {
			recordPractice(widget);
			if (cfg().loggedIn && typeof window.hwblSubmitReviewQuality === 'function') {
				window.hwblSubmitReviewQuality(widget, quality, mode);
			} else {
				submitReview(quality, mode);
			}
		}

		function promptQuality(mode, suggestedQuality) {
			if (cfg().loggedIn && widget.dataset.mode === 'review' && typeof window.hwblShowReviewQuality === 'function') {
				window.hwblShowReviewQuality(widget, function (quality) {
					finishReview(quality, mode);
				});
				return;
			}
			finishReview(suggestedQuality, mode);
		}

		function enrollLesson() {
			var lessonId = parseInt(widget.getAttribute('data-lesson-id') || '0', 10);
			if (!lessonId || !cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
				return;
			}
			fetch(cfg().restUrl + 'memorize/enroll', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg().nonce
				},
				body: JSON.stringify({ lesson_id: lessonId })
			}).catch(function () {
				// Enrollment is best-effort.
			});
		}

		render();
		loadServerStreak(widget);
		enrollLesson();
		setMode(cfg().loggedIn ? 'review' : 'hide');

		widget._thwMemorizationInit = resetState;
		widget._hwblSetMode = setMode;

		widget.querySelectorAll('.hwbl-mode-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				setMode(btn.getAttribute('data-mode') || 'hide');
			});
		});

		var recallCheck = widget.querySelector('.hwbl-memorization-recall-check');
		if (recallCheck) {
			recallCheck.addEventListener('click', function () {
				var input = widget.querySelector('.hwbl-memorization-recall-input');
				var result = widget.querySelector('.hwbl-memorization-recall-result');
				if (!input || !result) {
					return;
				}
				var expected = normalizeText(verse);
				var actual = normalizeText(input.value);
				var quality = 0;
				if (actual === expected) {
					quality = 5;
					result.textContent = i18n('recallGood', 'Great recall!');
				} else if (actual && (expected.indexOf(actual) !== -1 || actual.indexOf(expected) !== -1)) {
					quality = 3;
					result.textContent = i18n('recallPartial', 'Keep practicing — some words differ.');
				} else {
					quality = 1;
					result.textContent = i18n('recallPartial', 'Keep practicing — some words differ.');
				}
				recordPractice(widget);
				promptQuality('recall', quality);
			});
		}

		var scrambleCheck = widget.querySelector('.hwbl-memorization-scramble-check');
		if (scrambleCheck) {
			scrambleCheck.addEventListener('click', function () {
				var result = widget.querySelector('.hwbl-memorization-scramble-result');
				var expected = getWordTokens();
				var actual = scramblePicked.map(function (pickIdx) {
					return scrambleWords[pickIdx];
				});
				var quality = 0;
				if (actual.length === expected.length && actual.every(function (word, idx) {
					return normalizeText(word) === normalizeText(expected[idx]);
				})) {
					quality = 5;
					if (result) {
						result.textContent = i18n('scrambleGood', 'Correct order — well done!');
					}
				} else {
					quality = 2;
					if (result) {
						result.textContent = i18n('scramblePartial', 'Not quite — try reshuffling and practice again.');
					}
				}
				recordPractice(widget);
				promptQuality('scramble', quality);
			});
		}

		var scrambleReset = widget.querySelector('.hwbl-memorization-scramble-reset');
		if (scrambleReset) {
			scrambleReset.addEventListener('click', function () {
				resetScramble();
			});
		}

		var hideBtn = widget.querySelector('.hwbl-hide-random');
		var revealBtn = widget.querySelector('.hwbl-reveal-all');
		var resetBtn = widget.querySelector('.hwbl-reset-memorization');

		if (hideBtn) {
			hideBtn.addEventListener('click', function () {
				var words = state.filter(function (s) { return s.word && !s.hidden; });
				if (!words.length) {
					return;
				}
				var pick = words[Math.floor(Math.random() * words.length)];
				pick.hidden = true;
				recordPractice(widget);
				promptQuality('hide', 4);
				render();
			});
		}

		if (revealBtn) {
			revealBtn.addEventListener('click', function () {
				state.forEach(function (s) { s.hidden = false; });
				render();
			});
		}

		if (resetBtn) {
			resetBtn.addEventListener('click', function () {
				resetState();
			});
		}
	}

	window.hwblInitMemorization = initWidget;

	window.hwblInitMemorizationReviewMode = function (widget) {
		if (widget && typeof widget._hwblSetMode === 'function') {
			widget._hwblSetMode('review');
		}
	};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-memorization').forEach(initWidget);
	});

	document.addEventListener('hwbl:translation-changed', function (e) {
		var widget = e.detail && e.detail.widget;
		if (widget && typeof widget._thwMemorizationInit === 'function') {
			widget._thwMemorizationInit();
		}
	});
})();
