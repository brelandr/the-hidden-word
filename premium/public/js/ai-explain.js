(function () {
	'use strict';

	function getExplainConfig() {
		return window.thwAiExplain || null;
	}

	function getStorageKey(config) {
		return (config && config.traditionStorageKey) || 'thw_ai_tradition_preset';
	}

	function readStoredTradition(config) {
		try {
			return window.localStorage.getItem(getStorageKey(config)) || '';
		} catch (e) {
			return '';
		}
	}

	function writeStoredTradition(config, value) {
		if (!value) {
			return;
		}
		var prefs = window.hwblUserPreferences;
		if (prefs && prefs.saveTradition) {
			prefs.saveTradition(value);
			return;
		}
		try {
			window.localStorage.setItem(getStorageKey(config), value);
		} catch (e) {
			// Ignore storage failures (private mode, etc.).
		}
	}

	function syncTraditionSelects(config) {
		if (!config || !config.userTradition) {
			return;
		}
		var stored = readStoredTradition(config);
		if (!stored) {
			return;
		}
		document.querySelectorAll('[data-thw-tradition-select]').forEach(function (select) {
			if ([].some.call(select.options, function (opt) { return opt.value === stored; })) {
				select.value = stored;
			}
		});
	}

	function resolveLessonId(root, lessonWrap, trigger) {
		var lessonId = '';

		if (root && root.getAttribute('data-lesson-id')) {
			lessonId = root.getAttribute('data-lesson-id');
		} else if (trigger && trigger.getAttribute('data-lesson-id')) {
			lessonId = trigger.getAttribute('data-lesson-id');
		} else if (lessonWrap && lessonWrap.getAttribute('data-lesson-id')) {
			lessonId = lessonWrap.getAttribute('data-lesson-id');
		}

		return parseInt(lessonId, 10) || 0;
	}

	function formatErrorMessage(result, config) {
		if (result.status === 429) {
			return config.rateLimit;
		}

		var data = result.data || {};
		if (data.message) {
			return data.message;
		}

		if (data.data && data.data.params) {
			var details = Object.keys(data.data.params).map(function (key) {
				return key + ': ' + data.data.params[key];
			});
			if (details.length) {
				return details.join(' ');
			}
		}

		return config.error;
	}

	function ensurePanel(root, lessonWrap, lessonId) {
		var panel = lessonWrap.querySelector('.thw-ai-explain-panel');
		if (panel) {
			return panel;
		}

		panel = document.createElement('div');
		panel.id = 'thw-panel-ai-explain-' + lessonId;
		panel.className = 'thw-ai-explain-panel';
		panel.setAttribute('role', 'region');
		panel.hidden = true;
		panel.innerHTML =
			'<h3 class="thw-panel-title">AI Explanation</h3>' +
			'<div class="thw-ai-explain-output" aria-live="polite"></div>';

		if (root.nextSibling) {
			root.parentNode.insertBefore(panel, root.nextSibling);
		} else {
			root.parentNode.appendChild(panel);
		}

		return panel;
	}

	function handleExplainClick(trigger) {
		var config = getExplainConfig();
		var root = trigger.closest('.thw-ai-explain-controls');
		var lessonWrap = root ? root.closest('.thw-lesson') : null;
		var scopeSelect = root ? root.querySelector('.thw-ai-explain-scope') : null;
		var traditionSelect = root ? root.querySelector('.thw-ai-explain-tradition, [data-thw-tradition-select]') : null;
		var lessonId = resolveLessonId(root, lessonWrap, trigger);

		if (!root || !lessonWrap) {
			window.alert((config && config.noPanel) || '');
			return;
		}

		var panel = ensurePanel(root, lessonWrap, lessonId);
		var output = panel.querySelector('.thw-ai-explain-output');

		if (!output) {
			window.alert((config && config.noPanel) || '');
			return;
		}

		panel.hidden = false;
		panel.classList.add('is-active');
		panel.classList.remove('thw-tab-panel');
		panel.style.setProperty('display', 'block', 'important');
		panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

		if (!config || !config.restUrl) {
			output.innerHTML = '<p class="thw-notice thw-notice-info">AI explain script failed to load. Refresh the page and try again.</p>';
			return;
		}

		if (!lessonId) {
			output.innerHTML = '<p class="thw-notice thw-notice-info">Could not determine the lesson ID for this page.</p>';
			return;
		}

		output.innerHTML = '<p class="thw-ai-explain-loading">' + config.loading + '</p>';

		var body = new URLSearchParams();
		body.set('lesson_id', String(lessonId));
		body.set('scope', scopeSelect ? scopeSelect.value : 'all');
		if (traditionSelect && traditionSelect.value) {
			body.set('tradition', traditionSelect.value);
			writeStoredTradition(config, traditionSelect.value);
		}

		fetch(config.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-WP-Nonce': config.nonce
			},
			credentials: 'same-origin',
			body: body.toString()
		})
			.then(function (response) {
				return response.json().then(function (data) {
					return { ok: response.ok, status: response.status, data: data };
				});
			})
			.then(function (result) {
				var content = result.data && result.data.content ? String(result.data.content) : '';

				if (!result.ok) {
					output.innerHTML = '<p class="thw-notice thw-notice-info">' + formatErrorMessage(result, config) + '</p>';
					return;
				}

				if (content) {
					var flagged = !!(result.data && result.data.complianceFlagged);
					var html = content;
					if (flagged && config.complianceFlagged) {
						html = '<p class="thw-ai-explain-disclaimer thw-ai-compliance-flagged">' + config.complianceFlagged + '</p>' + html;
					}
					output.innerHTML = html;
				} else {
					output.innerHTML = '<p class="thw-notice thw-notice-info">' + config.error + '</p>';
				}
			})
			.catch(function () {
				output.innerHTML = '<p class="thw-notice thw-notice-info">' + config.error + '</p>';
			});
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('.thw-ai-explain-trigger');
		if (!trigger) {
			return;
		}
		event.preventDefault();
		handleExplainClick(trigger);
	});

	document.addEventListener('change', function (event) {
		var select = event.target.closest('[data-thw-tradition-select]');
		if (!select) {
			return;
		}
		var config = getExplainConfig() || window.thwStudyFinder || {};
		writeStoredTradition(config, select.value);
		document.querySelectorAll('[data-thw-tradition-select]').forEach(function (other) {
			if (other !== select && [].some.call(other.options, function (opt) { return opt.value === select.value; })) {
				other.value = select.value;
			}
		});
	});

	syncTraditionSelects(getExplainConfig());
})();
