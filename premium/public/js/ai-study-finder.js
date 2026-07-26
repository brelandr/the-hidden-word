(function () {
	'use strict';

	function getStudyConfig() {
		return window.thwStudyFinder || null;
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
		if (data.code === 'thw_study_login_required' && config.loginRequired) {
			return config.loginRequired;
		}
		if (data.code === 'thw_study_disabled' && config.disabled) {
			return config.disabled;
		}
		if (data.code === 'thw_ai_disabled' && config.aiUnavailable) {
			return config.aiUnavailable;
		}
		if ((data.code === 'thw_study_parse_failed' || data.code === 'thw_study_empty') && config.parseFailed) {
			return config.parseFailed;
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
		var html = '<div class="thw-study-finder__lessons">';
		html += '<h3 class="thw-study-finder__lessons-heading">' + escapeHtml(config.lessonsHeading || 'Related lessons') + '</h3>';
		html += '<ul class="thw-study-finder__lessons-list">';
		lessons.forEach(function (item) {
			html += '<li class="thw-study-finder__lesson">';
			var label = item.title || item.reference || '';
			if (item.url) {
				html += '<a href="' + escapeHtml(item.url) + '">' + escapeHtml(label) + '</a>';
			} else {
				html += escapeHtml(label);
			}
			if (item.reference && item.title && item.reference !== item.title) {
				html += ' <span class="thw-study-finder__lesson-ref">(' + escapeHtml(item.reference) + ')</span>';
			}
			html += '</li>';
		});
		html += '</ul></div>';
		return html;
	}

	function renderResults(list, config, flagged, lessons) {
		if (!list.length) {
			return '<p class="thw-notice thw-notice-info">' + escapeHtml(config.empty) + '</p>';
		}

		var html = '<p class="thw-study-finder__source">' + escapeHtml(config.aiNote) + '</p>';
		html += '<ol class="thw-study-finder__results-list">';
		var disclaimerHtml = '';
		if (flagged && config.complianceFlagged) {
			disclaimerHtml += '<p class="thw-study-finder__disclaimer thw-ai-compliance-flagged">' + escapeHtml(config.complianceFlagged) + '</p>';
		} else if (config.disclaimer) {
			disclaimerHtml += '<p class="thw-study-finder__disclaimer">' + escapeHtml(config.disclaimer) + '</p>';
		}

		list.forEach(function (item) {
			html += '<li class="thw-study-finder__result">';
			html += '<h3 class="thw-study-finder__reference">';
			if (item.url) {
				html += '<a href="' + escapeHtml(item.url) + '">' + escapeHtml(item.reference) + '</a>';
			} else {
				html += escapeHtml(item.reference);
			}
			html += '</h3>';
			if (item.summary) {
				html += '<p class="thw-study-finder__summary">' + escapeHtml(item.summary) + '</p>';
			}
			if (item.citationsHtml || item.citations) {
				html += '<p class="thw-study-finder__citations"><span class="thw-study-finder__citations-label">' + escapeHtml(config.citationsLabel || 'Faith tradition sources:') + '</span> ';
				html += item.citationsHtml ? item.citationsHtml : escapeHtml(item.citations);
				html += '</p>';
			}
			if (item.commentary) {
				html += '<div class="thw-study-finder__commentary">' + item.commentary + '</div>';
			}
			html += '</li>';
		});

		html += '</ol>';
		html += renderLessons(lessons, config);
		html += disclaimerHtml;
		return html;
	}

	function handleStudySearch(form) {
		var config = getStudyConfig();
		var input = form.querySelector('.thw-study-finder__input');
		var traditionSelect = form.querySelector('.thw-study-finder__tradition, [data-thw-tradition-select]');
		var root = form.closest('.thw-study-finder');
		var status = root ? root.querySelector('.thw-study-finder__status') : null;
		var results = root ? root.querySelector('.thw-study-finder__results') : null;

		if (!input || !results) {
			return;
		}

		var keywords = input.value.trim();
		if (!keywords) {
			return;
		}

		if (!config || !config.restUrl) {
			results.innerHTML = '<p class="thw-notice thw-notice-info">Study finder script failed to load. Refresh the page and try again.</p>';
			return;
		}

		if (config.audience === 'disabled') {
			results.innerHTML = '<p class="thw-notice thw-notice-info">' + escapeHtml(config.disabled) + '</p>';
			return;
		}

		if (config.audience === 'logged_in' && !config.loggedIn) {
			results.innerHTML = '<p class="thw-notice thw-notice-info">' + escapeHtml(config.loginRequired) + '</p>';
			return;
		}

		if (!config.aiEnabled) {
			results.innerHTML = '<p class="thw-notice thw-notice-info">' + escapeHtml(config.aiUnavailable) + '</p>';
			return;
		}

		if (status) {
			status.textContent = config.loading;
		}
		results.innerHTML = '';

		var body = new URLSearchParams();
		body.set('keywords', keywords);
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
					return { ok: response.ok, data: data };
				});
			})
			.then(function (result) {
				if (status) {
					status.textContent = '';
				}
				if (!result.ok || !result.data || !result.data.results) {
					results.innerHTML = '<p class="thw-notice thw-notice-info">' + escapeHtml(formatErrorMessage(result, config)) + '</p>';
					return;
				}

				results.innerHTML = renderResults(result.data.results, config, !!result.data.complianceFlagged, result.data.lessons || []);
			})
			.catch(function () {
				if (status) {
					status.textContent = '';
				}
				results.innerHTML = '<p class="thw-notice thw-notice-info">' + escapeHtml(config.error) + '</p>';
			});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('.thw-study-finder__form');
		if (!form) {
			return;
		}
		event.preventDefault();
		handleStudySearch(form);
	});

	syncTraditionSelects(getStudyConfig());
})();
