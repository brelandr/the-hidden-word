(function () {
	'use strict';

	function getAskConfig() {
		return window.thwAiAsk || null;
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
			// Ignore storage failures.
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

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text || '';
		return div.innerHTML;
	}

	function formatErrorMessage(result, config) {
		var data = result.data || {};
		if (data.code === 'thw_ask_login_required' && config.loginRequired) {
			return config.loginRequired;
		}
		if (data.code === 'thw_ask_disabled' && config.disabled) {
			return config.disabled;
		}
		if (data.code === 'thw_ai_disabled' && config.aiUnavailable) {
			return config.aiUnavailable;
		}
		if (data.code === 'thw_ai_rate_limit' && config.rateLimit) {
			return config.rateLimit;
		}
		if (data.message) {
			return data.message;
		}
		return config.error;
	}

	function renderLessons(lessons, config) {
		if (!lessons || !lessons.length) {
			return '';
		}
		var html = '<div class="thw-ask-question__lessons">';
		html += '<h3 class="thw-ask-question__lessons-heading">' + escapeHtml(config.lessonsHeading || 'Related lessons') + '</h3>';
		html += '<ul class="thw-ask-question__lessons-list">';
		lessons.forEach(function (item) {
			html += '<li class="thw-ask-question__lesson">';
			var label = item.title || item.reference || '';
			if (item.url) {
				html += '<a href="' + escapeHtml(item.url) + '">' + escapeHtml(label) + '</a>';
			} else {
				html += escapeHtml(label);
			}
			if (item.reference && item.title && item.reference !== item.title) {
				html += ' <span class="thw-ask-question__lesson-ref">(' + escapeHtml(item.reference) + ')</span>';
			}
			if (item.excerpt) {
				html += '<p class="thw-ask-question__lesson-excerpt">' + escapeHtml(item.excerpt) + '</p>';
			}
			html += '</li>';
		});
		html += '</ul></div>';
		return html;
	}

	function renderAnswer(payload, config) {
		var html = '<div class="thw-ask-question__answer-body">';
		html += '<p class="thw-ask-question__source">' + escapeHtml(config.aiNote) + '</p>';
		html += '<div class="thw-ask-question__content">' + (payload.content || '') + '</div>';
		if (payload.citationsHtml || payload.citations) {
			html += '<p class="thw-ask-question__citations"><span class="thw-ask-question__citations-label">' + escapeHtml(config.citationsLabel || 'Tradition sources:') + '</span> ';
			html += payload.citationsHtml ? payload.citationsHtml : escapeHtml(payload.citations);
			html += '</p>';
		}
		html += renderLessons(payload.lessons, config);
		if (payload.complianceFlagged && config.complianceFlagged) {
			html += '<p class="thw-ask-question__disclaimer thw-ai-compliance-flagged">' + escapeHtml(config.complianceFlagged) + '</p>';
		} else if (config.disclaimer) {
			html += '<p class="thw-ask-question__disclaimer">' + escapeHtml(config.disclaimer) + '</p>';
		}
		html += '</div>';
		return html;
	}

	function handleAsk(form) {
		var config = getAskConfig();
		var input = form.querySelector('.thw-ask-question__input');
		var traditionSelect = form.querySelector('.thw-ask-question__tradition, [data-thw-tradition-select]');
		var root = form.closest('.thw-ask-question');
		var status = root ? root.querySelector('.thw-ask-question__status') : null;
		var answer = root ? root.querySelector('.thw-ask-question__answer') : null;
		var submit = form.querySelector('.thw-ask-question__submit');

		if (!config || !input || !status || !answer) {
			return;
		}

		var question = (input.value || '').trim();
		if (!question) {
			return;
		}

		if (traditionSelect && traditionSelect.value) {
			writeStoredTradition(config, traditionSelect.value);
		}

		status.textContent = config.loading;
		answer.innerHTML = '';
		if (submit) {
			submit.disabled = true;
		}

		var body = {
			question: question,
			tradition: traditionSelect ? (traditionSelect.value || '') : ''
		};

		fetch(config.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce
			},
			body: JSON.stringify(body)
		})
			.then(function (res) {
				return res.json().then(function (data) {
					return { ok: res.ok, status: res.status, data: data };
				});
			})
			.then(function (result) {
				if (submit) {
					submit.disabled = false;
				}
				if (!result.ok) {
					status.textContent = formatErrorMessage(result, config);
					return;
				}
				status.textContent = '';
				if (!result.data || !result.data.content) {
					answer.innerHTML = '<p class="thw-notice thw-notice-info">' + escapeHtml(config.empty) + '</p>';
					return;
				}
				answer.innerHTML = renderAnswer(result.data, config);
			})
			.catch(function () {
				if (submit) {
					submit.disabled = false;
				}
				status.textContent = config.error;
			});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('.thw-ask-question__form');
		if (!form) {
			return;
		}
		event.preventDefault();
		handleAsk(form);
	});

	document.addEventListener('DOMContentLoaded', function () {
		syncTraditionSelects(getAskConfig());
	});
})();
