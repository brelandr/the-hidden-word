/**
 * Tab switching, print, copy verse, and Add to journal for lesson UI.
 */
(function () {
	'use strict';

	function copyText(text) {
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
					return copyText(text);
				});
		}
		return copyText(text);
	}

	function siteAttribution(lesson) {
		var site = (lesson && lesson.getAttribute('data-site-name')) || '';
		site = String(site || '').trim();
		return site
			? '— via ' + site + ', Hidden Word Bible Lessons'
			: '— via Hidden Word Bible Lessons';
	}

	function dayOneUrl(text) {
		var journal = readJournalPref('dayone');
		var parts = [];
		if (journal) {
			parts.push('journal=' + encodeURIComponent(journal));
		}
		parts.push('entry=' + encodeURIComponent(String(text || '')));
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

	function buildLessonJournalPayload(lesson) {
		var titleEl = lesson.querySelector('.hwbl-lesson-title');
		var refEl = lesson.querySelector('.hwbl-lesson-reference');
		var copyBtn = lesson.querySelector('.hwbl-copy-verse');
		var title = titleEl ? String(titleEl.textContent || '').trim() : '';
		var ref = refEl ? String(refEl.textContent || '').trim() : '';
		var verse = copyBtn ? copyBtn.getAttribute('data-verse') || '' : '';
		var parts = [];
		if (title) {
			parts.push(title);
		}
		if (ref) {
			parts.push(ref);
		}
		if (verse) {
			parts.push(verse);
		}
		parts.push(siteAttribution(lesson));
		return {
			text: parts.filter(Boolean).join('\n\n'),
			title: title || ref || 'Lesson',
		};
	}

	function ensureLessonJournalMenu(lesson) {
		if (!lesson || lesson.querySelector('.hwbl-plan-add-journal-wrap')) {
			return;
		}
		var toolbar = lesson.querySelector('.hwbl-lesson-toolbar');
		if (!toolbar) {
			return;
		}
		lesson.setAttribute('data-site-name', lesson.getAttribute('data-site-name') || document.title.split('|')[0].trim());
		var wrap = document.createElement('span');
		wrap.className = 'hwbl-plan-add-journal-wrap';
		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'hwbl-btn hwbl-btn-secondary hwbl-add-to-journal';
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-haspopup', 'true');
		toggle.textContent = 'Add to journal';
		var menu = document.createElement('div');
		menu.className = 'hwbl-plan-add-journal-menu';
		menu.hidden = true;
		menu.setAttribute('role', 'menu');

		function addOption(label, kind) {
			var b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('role', 'menuitem');
			b.textContent = label;
			b.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				var payload = buildLessonJournalPayload(lesson);
				var status = lesson.querySelector('.hwbl-copy-status');
				menu.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
				if (!payload.text.trim()) {
					if (status) {
						status.textContent = 'Nothing to export yet.';
					}
					return;
				}
				if (kind === 'dayone') {
					openDeepLinkOrShare(dayOneUrl(payload.text), payload.text, payload.title);
					if (status) {
						status.textContent = 'Opening Day One…';
					}
				} else if (kind === 'quillday') {
					openDeepLinkOrShare(
						quillDayUrl(payload.text, payload.title),
						payload.text,
						payload.title
					);
					if (status) {
						status.textContent = 'Opening QuillDay…';
					}
				} else {
					shareOrCopy(payload.text, payload.title)
						.then(function () {
							if (status) {
								status.textContent = 'Shared.';
							}
						})
						.catch(function () {
							if (status) {
								status.textContent = 'Could not share.';
							}
						});
				}
			});
			menu.appendChild(b);
		}

		addOption('Day One', 'dayone');
		addOption('QuillDay', 'quillday');
		addOption('Other journal app', 'other');

		function prefOption(label, kind) {
			var b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('role', 'menuitem');
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
			document.querySelectorAll('.hwbl-plan-add-journal-menu').forEach(function (m) {
				m.hidden = true;
			});
			menu.hidden = !open;
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		wrap.appendChild(toggle);
		wrap.appendChild(menu);
		var status = toolbar.querySelector('.hwbl-copy-status');
		if (status) {
			toolbar.insertBefore(wrap, status);
		} else {
			toolbar.appendChild(wrap);
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-lesson').forEach(ensureLessonJournalMenu);
	});

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
			return;
		}

		if (!e.target.closest('.hwbl-plan-add-journal-wrap')) {
			document.querySelectorAll('.hwbl-plan-add-journal-menu').forEach(function (m) {
				m.hidden = true;
			});
		}
	});
})();
