/**
 * Tab switching, print, and copy verse for lesson UI.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.thw-tab-button');
		if (btn) {
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
			return;
		}

		var printBtn = e.target.closest('.thw-print-lesson');
		if (printBtn) {
			window.print();
			return;
		}

		var copyBtn = e.target.closest('.thw-copy-verse');
		if (copyBtn) {
			var lessonRoot = copyBtn.closest('.thw-lesson');
			var status = lessonRoot ? lessonRoot.querySelector('.thw-copy-status') : null;
			var text = copyBtn.getAttribute('data-verse') || '';
			if (!text) {
				return;
			}

			function showStatus(msg) {
				if (status) {
					status.textContent = msg;
				}
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					showStatus('Verse copied.');
				}).catch(function () {
					showStatus('Could not copy verse.');
				});
			} else {
				showStatus('Copy not supported in this browser.');
			}
		}
	});
})();
