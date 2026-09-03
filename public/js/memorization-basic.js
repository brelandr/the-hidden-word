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
		var flipTargetIndexes = {};
		var flipFlippedIndexes = {};

		function getWordTokens() {
			return state.filter(function (item) { return item.word; }).map(function (item) {
				return item.text.trim();
			});
		}

		function flipRatioFromCard() {
			var reps = parseInt(widget.getAttribute('data-srs-reps') || '0', 10) || 0;
			var interval = parseInt(widget.getAttribute('data-srs-interval') || '0', 10) || 0;
			var ease = parseFloat(widget.getAttribute('data-srs-ease') || '2.5');
			if (!isFinite(ease) || ease <= 0) {
				ease = 2.5;
			}
			var repScore = Math.min(1, Math.max(0, reps) / 6);
			var intervalScore = Math.min(1, Math.max(0, interval) / 21);
			var easeBoost = Math.max(-0.1, Math.min(0.15, (ease - 2.5) * 0.1));
			var mastery = Math.max(0, Math.min(1, repScore * 0.55 + intervalScore * 0.45 + easeBoost));
			var minRatio = 0.3;
			var maxRatio = 0.75;
			return Math.round((minRatio + (maxRatio - minRatio) * mastery) * 100) / 100;
		}

		function speakWord(word) {
			if (!word || typeof window.speechSynthesis === 'undefined') {
				return;
			}
			try {
				window.speechSynthesis.cancel();
				var utter = new window.SpeechSynthesisUtterance(word);
				utter.rate = 0.95;
				window.speechSynthesis.speak(utter);
			} catch (e) {
				// Ignore TTS failures.
			}
		}

		function pickFlipTargets(ratio) {
			var words = state.filter(function (item) { return item.word; });
			flipTargetIndexes = {};
			flipFlippedIndexes = {};
			if (!words.length) {
				return;
			}
			var useRatio = ratio == null ? flipRatioFromCard() : ratio;
			var clamped = Math.min(1, Math.max(0, useRatio));
			var count = Math.max(1, Math.round(words.length * clamped));
			var shuffled = shuffleArray(words);
			shuffled.slice(0, Math.min(count, shuffled.length)).forEach(function (item) {
				flipTargetIndexes[item.index] = true;
			});
		}

		function renderFlip() {
			container.innerHTML = '';
			state.forEach(function (item) {
				if (item.word && flipTargetIndexes[item.index]) {
					var flipped = !!flipFlippedIndexes[item.index];
					var word = item.text.trim();
					var card = document.createElement('button');
					card.type = 'button';
					card.className = 'hwbl-flip-card' + (flipped ? ' is-flipped' : '');
					card.setAttribute(
						'aria-label',
						flipped
							? word
							: i18n('flipReveal', 'Flip card to reveal word')
					);

					var inner = document.createElement('span');
					inner.className = 'hwbl-flip-card__inner';
					/* Size to the revealed word so it never wraps onto itself. */
					inner.style.minWidth = Math.max(2.75, Math.min(word.length + 1.5, 14)) + 'ch';

					var front = document.createElement('span');
					front.className = 'hwbl-flip-card__face hwbl-flip-card__front';
					front.textContent = '?';
					front.setAttribute('aria-hidden', 'true');

					var back = document.createElement('span');
					back.className = 'hwbl-flip-card__face hwbl-flip-card__back';
					back.textContent = word;
					back.setAttribute('aria-hidden', 'true');

					inner.appendChild(front);
					inner.appendChild(back);
					card.appendChild(inner);

					card.addEventListener('click', function () {
						if (flipFlippedIndexes[item.index]) {
							delete flipFlippedIndexes[item.index];
						} else {
							flipFlippedIndexes[item.index] = true;
							recordPractice(widget);
							speakWord(word);
						}
						renderFlip();
					});

					// Horizontal swipe to flip (touch).
					var touchStartX = null;
					card.addEventListener('touchstart', function (ev) {
						if (ev.changedTouches && ev.changedTouches[0]) {
							touchStartX = ev.changedTouches[0].clientX;
						}
					}, { passive: true });
					card.addEventListener('touchend', function (ev) {
						if (touchStartX == null || !ev.changedTouches || !ev.changedTouches[0]) {
							return;
						}
						var dx = ev.changedTouches[0].clientX - touchStartX;
						touchStartX = null;
						if (Math.abs(dx) < 36) {
							return;
						}
						ev.preventDefault();
						if (flipFlippedIndexes[item.index]) {
							delete flipFlippedIndexes[item.index];
						} else {
							flipFlippedIndexes[item.index] = true;
							recordPractice(widget);
							speakWord(word);
						}
						renderFlip();
					}, { passive: false });

					container.appendChild(card);
					return;
				}

				var span = document.createElement('span');
				span.className = 'hwbl-word';
				span.textContent = item.text;
				container.appendChild(span);
			});
		}

		function flipAllCards() {
			Object.keys(flipTargetIndexes).forEach(function (index) {
				flipFlippedIndexes[index] = true;
			});
			renderFlip();
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

		function hidePracticePanels() {
			var recall = widget.querySelector('.hwbl-memorization-recall');
			var scramble = widget.querySelector('.hwbl-memorization-scramble');
			var reference = widget.querySelector('.hwbl-memorization-reference');
			var controls = widget.querySelector('.hwbl-memorization-controls');
			var flipControls = widget.querySelector('.hwbl-memorization-flip-controls');
			var audioWrap = widget.querySelector('.hwbl-memorization-audio-wrap');
			container.hidden = true;
			if (controls) {
				controls.hidden = true;
			}
			if (flipControls) {
				flipControls.hidden = true;
			}
			if (recall) {
				recall.hidden = true;
			}
			if (scramble) {
				scramble.hidden = true;
			}
			if (reference) {
				reference.hidden = true;
			}
			if (audioWrap) {
				audioWrap.classList.remove('is-flip-paired');
			}
		}

		function setMode(mode) {
			widget.dataset.mode = mode;
			var hint = widget.querySelector('.hwbl-memorization-hint');
			var recall = widget.querySelector('.hwbl-memorization-recall');
			var scramble = widget.querySelector('.hwbl-memorization-scramble');
			var reference = widget.querySelector('.hwbl-memorization-reference');
			var controls = widget.querySelector('.hwbl-memorization-controls');
			var modeBtns = widget.querySelectorAll('.hwbl-mode-btn');

			modeBtns.forEach(function (btn) {
				var active = btn.getAttribute('data-mode') === mode;
				btn.classList.toggle('is-active', active);
				btn.setAttribute('aria-selected', active ? 'true' : 'false');
			});

			if (mode === 'recall') {
				hidePracticePanels();
				if (recall) {
					recall.hidden = false;
				}
				if (hint) {
					hint.textContent = i18n('recallPrompt', 'Type the verse from memory, then check your answer.');
				}
				return;
			}

			if (mode === 'reference') {
				hidePracticePanels();
				if (reference) {
					reference.hidden = false;
				}
				if (hint) {
					hint.textContent = i18n('modeReference', 'Enter the book, chapter, and verse (for example Romans chapter 1, verse 1).');
				}
				return;
			}

			if (mode === 'scramble') {
				hidePracticePanels();
				if (scramble) {
					scramble.hidden = false;
				}
				resetScramble();
				if (hint) {
					hint.textContent = i18n('modeScramble', 'Click shuffled words in verse order.');
				}
				return;
			}

			if (mode === 'flip') {
				hidePracticePanels();
				container.hidden = false;
				var flipControls = widget.querySelector('.hwbl-memorization-flip-controls');
				if (flipControls) {
					flipControls.hidden = false;
				}
				pickFlipTargets();
				renderFlip();
				if (hint) {
					hint.textContent = i18n(
						'modeFlip',
						'Flip cards (or swipe) to reveal missing words. Listen to the chapter, then check yourself.'
					);
				}
				var audioWrap = widget.querySelector('.hwbl-memorization-audio-wrap');
				if (audioWrap) {
					audioWrap.classList.add('is-flip-paired');
				}
				return;
			}

			if (mode === 'review') {
				hidePracticePanels();
				if (recall) {
					recall.hidden = false;
				}
				if (hint) {
					hint.textContent = i18n('reviewPrompt', 'Daily review: type the verse from memory, then rate your recall.');
				}
				return;
			}

			hidePracticePanels();
			container.hidden = false;
			if (controls) {
				controls.hidden = false;
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

		function normalizeBookName(value) {
			return String(value || '')
				.toLowerCase()
				.replace(/^(the)\s+/, '')
				.replace(/[^a-z0-9\s]/g, ' ')
				.replace(/\s+/g, ' ')
				.trim();
		}

		function parseTypedReference(value) {
			var raw = String(value || '').trim();
			if (!raw) {
				return null;
			}

			var chapterVerse = raw.match(/^(.+?)\s+(\d+)\s*:\s*(\d+)\s*$/i);
			if (chapterVerse) {
				return {
					book: chapterVerse[1].trim(),
					chapter: parseInt(chapterVerse[2], 10),
					verse: parseInt(chapterVerse[3], 10)
				};
			}

			var spoken = raw.match(/^(.+?)\s+chapter\s+(\d+)\s*,?\s*verse\s+(\d+)\s*$/i);
			if (spoken) {
				return {
					book: spoken[1].trim(),
					chapter: parseInt(spoken[2], 10),
					verse: parseInt(spoken[3], 10)
				};
			}

			return null;
		}

		function getExpectedReference() {
			return {
				book: widget.getAttribute('data-book-name') || '',
				chapter: parseInt(widget.getAttribute('data-chapter') || '0', 10),
				verse: parseInt(widget.getAttribute('data-verse-num') || '0', 10),
				reference: widget.getAttribute('data-reference') || ''
			};
		}

		function checkReferenceAnswer() {
			var result = widget.querySelector('.hwbl-memorization-reference-result');
			var bookInput = widget.querySelector('.hwbl-memorization-reference-book');
			var chapterInput = widget.querySelector('.hwbl-memorization-reference-chapter');
			var verseInput = widget.querySelector('.hwbl-memorization-reference-verse');
			var fullInput = widget.querySelector('.hwbl-memorization-reference-full');
			var expected = getExpectedReference();

			if (!expected.book || !expected.chapter || !expected.verse) {
				if (result) {
					result.textContent = i18n('referenceEmpty', 'Enter a book, chapter, and verse to check.');
				}
				return;
			}

			var typed = parseTypedReference(fullInput ? fullInput.value : '');
			var book = typed ? typed.book : (bookInput ? bookInput.value : '');
			var chapter = typed ? typed.chapter : parseInt(chapterInput && chapterInput.value ? chapterInput.value : '0', 10);
			var verseNum = typed ? typed.verse : parseInt(verseInput && verseInput.value ? verseInput.value : '0', 10);

			if (!book || !chapter || !verseNum) {
				if (result) {
					result.textContent = i18n('referenceEmpty', 'Enter a book, chapter, and verse to check.');
				}
				return;
			}

			var bookOk = normalizeBookName(book) === normalizeBookName(expected.book);
			var chapterOk = chapter === expected.chapter;
			var verseOk = verseNum === expected.verse;
			var quality = 1;

			if (bookOk && chapterOk && verseOk) {
				quality = 5;
				if (result) {
					result.textContent = i18n('referenceGood', 'Correct — you know the reference!');
				}
			} else if (bookOk || (chapterOk && verseOk)) {
				quality = 3;
				if (result) {
					result.textContent = i18n('referencePartial', 'Close — check the book, chapter, and verse again.');
				}
			} else if (result) {
				result.textContent = i18n('referencePartial', 'Close — check the book, chapter, and verse again.');
			}

			recordPractice(widget);
			promptQuality('reference', quality);
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

		var flipNew = widget.querySelector('.hwbl-flip-new');
		if (flipNew) {
			flipNew.addEventListener('click', function () {
				pickFlipTargets();
				renderFlip();
			});
		}

		var flipAll = widget.querySelector('.hwbl-flip-all');
		if (flipAll) {
			flipAll.addEventListener('click', function () {
				flipAllCards();
			});
		}

		var flipDone = widget.querySelector('.hwbl-flip-done');
		if (flipDone) {
			flipDone.addEventListener('click', function () {
				recordPractice(widget);
				if (typeof window.hwblShowReviewQuality === 'function') {
					window.hwblShowReviewQuality(widget, function (quality) {
						finishReview(quality, 'flip');
					});
					return;
				}
				promptQuality('flip', 4);
			});
		}

		var referenceCheck = widget.querySelector('.hwbl-memorization-reference-check');
		if (referenceCheck) {
			referenceCheck.addEventListener('click', checkReferenceAnswer);
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

		bindMemorizationJournal(widget);
	}

	function bindMemorizationJournal(widget) {
		if (!window.HWBLJournalExport || !widget) {
			return;
		}
		var mount = widget.querySelector('.hwbl-journal-export-mount');
		if (!mount) {
			return;
		}
		var lesson = widget.closest('.hwbl-lesson');
		var ref = widget.getAttribute('data-reference') || '';
		var verse = widget.getAttribute('data-verse') || '';
		var title = ref || 'Memorization';
		var feedback = widget.querySelector('.hwbl-memorization-review-feedback');
		window.HWBLJournalExport.mountMenu(
			mount,
			function () {
				var parts = [];
				if (ref) {
					parts.push(ref);
				}
				if (verse) {
					parts.push(verse);
				}
				return parts.join('\n\n');
			},
			title,
			lesson || widget,
			function (msg) {
				if (feedback) {
					feedback.hidden = false;
					feedback.textContent = msg || '';
				}
			}
		);
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
