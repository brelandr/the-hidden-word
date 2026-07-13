/**
 * Basic fill-in-the-blanks memorization widget.
 */
(function () {
	'use strict';

	function tokenize(text) {
		return text.split(/(\s+)/).filter(function (t) { return t.length > 0; });
	}

	function isWord(token) {
		return /\w/.test(token);
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

		render();

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
				state = tokens.map(function (t, i) {
					return { text: t, hidden: false, index: i, word: isWord(t) };
				});
				render();
			});
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.thw-memorization').forEach(initWidget);
	});
})();
