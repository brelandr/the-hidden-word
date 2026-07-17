/**
 * Tab switching, print, and copy verse for lesson UI.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.hwbl-tab-button');
		if (btn) {
			var lesson = btn.closest('.hwbl-lesson');
			if (!lesson) {
				return;
			}

			var tab = btn.getAttribute('data-tab');
			var buttons = lesson.querySelectorAll('.hwbl-tab-button');
			var panels = lesson.querySelectorAll('.hwbl-tab-panel');

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

		var printBtn = e.target.closest('.hwbl-print-lesson');
		if (printBtn) {
			window.print();
			return;
		}

		var copyBtn = e.target.closest('.hwbl-copy-verse');
		if (copyBtn) {
			var lessonRoot = copyBtn.closest('.hwbl-lesson');
			var status = lessonRoot ? lessonRoot.querySelector('.hwbl-copy-status') : null;
			var text = copyBtn.getAttribute('data-verse') || '';
			if (!text) {
				return;
			}

			function showStatus(msg) {
				if (status) {
					status.textContent = msg;
				}
			}

			var i18n = (window.hwblLessonTabs && window.hwblLessonTabs.i18n) || {};

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					showStatus(i18n.verseCopied || 'Verse copied.');
				}).catch(function () {
					showStatus(i18n.copyFailed || 'Could not copy verse.');
				});
			} else {
				showStatus(i18n.copyUnsupported || 'Copy not supported in this browser.');
			}
		}
	});
})();
