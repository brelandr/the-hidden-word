/**
 * Shared "Add to journal" menu (Day One, QuillDay, system share).
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

	function plainTextFromEl(el) {
		if (!el) {
			return '';
		}
		return String(el.innerText || el.textContent || '')
			.replace(/\s+\n/g, '\n')
			.trim();
	}

	function siteAttribution(contextEl) {
		var site = '';
		if (contextEl) {
			site = contextEl.getAttribute('data-site-name') || '';
			if (!site && contextEl.closest) {
				var parent = contextEl.closest('[data-site-name]');
				if (parent) {
					site = parent.getAttribute('data-site-name') || '';
				}
			}
		}
		site = String(
			site || document.body.getAttribute('data-site-name') || ''
		).trim();
		return site
			? '— via ' + site + ', Hidden Word Bible Lessons'
			: '— via Hidden Word Bible Lessons';
	}

	function withAttribution(contextEl, text) {
		text = String(text || '').trim();
		var line = siteAttribution(contextEl);
		return text ? text + '\n\n' + line : line;
	}

	function readJournalPref(kind) {
		try {
			var key =
				kind === 'dayone' ? 'hwbl_dayone_journal' : 'hwbl_quillday_journal';
			return String(window.localStorage.getItem(key) || '').trim();
		} catch (e) {
			return '';
		}
	}

	function writeJournalPref(kind, value) {
		try {
			var key =
				kind === 'dayone' ? 'hwbl_dayone_journal' : 'hwbl_quillday_journal';
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
			'Preferred ' +
				label +
				' journal name (exact match). Leave blank for the app default.',
			current
		);
		if (next === null) {
			return current;
		}
		writeJournalPref(kind, next);
		return String(next || '').trim();
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

	function closeMenus(scope) {
		(scope || document)
			.querySelectorAll('.hwbl-plan-add-journal-menu')
			.forEach(function (menu) {
				menu.hidden = true;
				var wrap = menu.parentElement;
				var btn = wrap
					? wrap.querySelector('.hwbl-plan-add-journal, .hwbl-add-to-journal')
					: null;
				if (btn) {
					btn.setAttribute('aria-expanded', 'false');
				}
			});
	}

	function buildMenu(getText, title, contextEl, onStatus) {
		var wrap = document.createElement('span');
		wrap.className = 'hwbl-plan-add-journal-wrap';
		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'hwbl-btn hwbl-btn-secondary hwbl-plan-add-journal';
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-haspopup', 'true');
		toggle.textContent = 'Add to journal';
		var menu = document.createElement('div');
		menu.className = 'hwbl-plan-add-journal-menu';
		menu.hidden = true;
		menu.setAttribute('role', 'menu');

		function setStatus(msg) {
			if (typeof onStatus === 'function') {
				onStatus(msg);
			}
		}

		function option(label, kind) {
			var b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('role', 'menuitem');
			b.textContent = label;
			b.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				var raw =
					typeof getText === 'function'
						? getText()
						: String(getText || '');
				var text = withAttribution(contextEl, raw);
				menu.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
				if (!String(raw || '').trim()) {
					setStatus('Nothing to export yet.');
					return;
				}
				if (kind === 'dayone') {
					openDeepLinkOrShare(dayOneUrl(text), text, title);
					setStatus('Opening Day One…');
				} else if (kind === 'quillday') {
					openDeepLinkOrShare(quillDayUrl(text, title), text, title);
					setStatus('Opening QuillDay…');
				} else {
					shareOrCopy(text, title)
						.then(function () {
							setStatus('Shared.');
						})
						.catch(function (err) {
							setStatus(
								(err && err.message) || 'Could not share.'
							);
						});
				}
			});
			menu.appendChild(b);
		}

		option('Day One', 'dayone');
		option('QuillDay', 'quillday');
		option('Other journal app', 'other');

		function prefOption(label, kind) {
			var b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('role', 'menuitem');
			b.className = 'hwbl-plan-add-journal-pref';
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
			closeMenus(contextEl || document);
			menu.hidden = !open;
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		wrap.appendChild(toggle);
		wrap.appendChild(menu);
		return wrap;
	}

	function mountMenu(container, getText, title, contextEl, onStatus) {
		if (!container) {
			return null;
		}
		container.innerHTML = '';
		var menu = buildMenu(getText, title, contextEl, onStatus);
		container.appendChild(menu);
		return menu;
	}

	function ensureExplainActions(panel, getText, title, contextEl, onStatus) {
		if (!panel) {
			return null;
		}
		var actions = panel.querySelector('.hwbl-journal-export-actions');
		if (!actions) {
			actions = document.createElement('div');
			actions.className = 'hwbl-journal-export-actions';
			panel.appendChild(actions);
		}
		actions.hidden = false;
		var mount = actions.querySelector('.hwbl-journal-export-mount');
		if (!mount) {
			mount = document.createElement('span');
			mount.className = 'hwbl-journal-export-mount';
			actions.appendChild(mount);
		}
		return mountMenu(mount, getText, title, contextEl, onStatus);
	}

	window.HWBLJournalExport = {
		plainTextFromEl: plainTextFromEl,
		withAttribution: withAttribution,
		buildMenu: buildMenu,
		mountMenu: mountMenu,
		ensureExplainActions: ensureExplainActions,
		closeMenus: closeMenus,
		shareOrCopy: shareOrCopy,
	};

	document.addEventListener('click', function (e) {
		if (!e.target.closest('.hwbl-plan-add-journal-wrap')) {
			closeMenus();
		}
	});
})();
