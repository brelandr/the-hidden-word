/**
 * Tab switching for lesson UI.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.thw-tab-button');
		if (!btn) {
			return;
		}

		var lesson = btn.closest('.thw-lesson');
		if (!lesson) {
			return;
		}

		var tab = btn.getAttribute('data-tab');
		var buttons = lesson.querySelectorAll('.thw-tab-button');
		var panels = lesson.querySelectorAll('.thw-tab-panel');

		buttons.forEach(function (b) {
			b.classList.remove('is-active');
			b.setAttribute('aria-selected', 'false');
		});

		panels.forEach(function (p) {
			p.classList.remove('is-active');
			p.hidden = true;
		});

		btn.classList.add('is-active');
		btn.setAttribute('aria-selected', 'true');

		var panel = lesson.querySelector('[data-panel="' + tab + '"]');
		if (panel) {
			panel.classList.add('is-active');
			panel.hidden = false;
		}
	});
})();
