/**
 * Basic fill-in-the-blanks memorization widget with local streak tracking.
 */
(function () {
	'use strict';

	var STREAK_KEY = 'thw_mem_streak';

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
		return (window.thwMemorization && window.thwMemorization.today) || new Date().toISOString().slice(0, 10);
	}

	function recordPractice(widget) {
		var streak = loadStreak();
		var today = todayKey();

		if (streak.lastDate === today) {
			renderStreak(widget, streak);
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
	}

	function renderStreak(widget, streak) {
		var el = widget.querySelector('.thw-memorization-streak');
		if (!el || !streak.count) {
			return;
		}

		var label = streak.count === 1
			? 'Day 1 streak — great start!'
			: 'Day ' + streak.count + ' streak — keep going!';

		el.textContent = label;
		el.hidden = false;

		if (streak.count >= 3 && window.thwMemorization && window.thwMemorization.streakUpsell) {
			var upsell = widget.querySelector('.thw-streak-upsell');
			if (!upsell) {
				upsell = document.createElement('p');
				upsell.className = 'thw-streak-upsell';
				upsell.textContent = window.thwMemorization.streakUpsell;
				el.parentNode.insertBefore(upsell, el.nextSibling);
			}
		}
	}

	function initWidget(widget) {
		var verse = widget.getAttribute('data-verse') || '';
		var container = widget.querySelector('.thw-memorization-text');
		if (!container || !verse) {
			return;
		}

		var tokens = tokenize(verse);
		var state = tokens.map(function (t, i) {
			return { text: t, hidden: false, index: i, word: isWord(t) };
		});

		function render() {
			container.innerHTML = '';
			state.forEach(function (item) {
				var span = document.createElement('span');
				span.className = 'thw-word' + (item.hidden ? ' is-hidden' : '') + (item.word ? ' is-clickable' : '');
				span.textContent = item.hidden ? '______' : item.text;
				span.dataset.index = String(item.index);
				if (item.word && !item.hidden) {
					span.addEventListener('click', function () {
						item.hidden = true;
						recordPractice(widget);
						render();
					});
				}
				if (item.hidden) {
					span.addEventListener('click', function () {
						item.hidden = false;
						render();
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

		render();
		renderStreak(widget, loadStreak());

		widget._thwMemorizationInit = resetState;

		var hideBtn = widget.querySelector('.thw-hide-random');
		var revealBtn = widget.querySelector('.thw-reveal-all');
		var resetBtn = widget.querySelector('.thw-reset-memorization');

		if (hideBtn) {
			hideBtn.addEventListener('click', function () {
				var words = state.filter(function (s) { return s.word && !s.hidden; });
				if (!words.length) {
					return;
				}
				var pick = words[Math.floor(Math.random() * words.length)];
				pick.hidden = true;
				recordPractice(widget);
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

	window.thwInitMemorization = initWidget;

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.thw-memorization').forEach(initWidget);
	});

	document.addEventListener('thw:translation-changed', function (e) {
		var widget = e.detail && e.detail.widget;
		if (widget && typeof widget._thwMemorizationInit === 'function') {
			widget._thwMemorizationInit();
		}
	});
})();
