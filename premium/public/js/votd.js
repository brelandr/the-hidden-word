(function () {
	'use strict';

	function getConfig() {
		return window.thwVotd || null;
	}

	/**
	 * Refresh REST nonce + login state via admin-ajax (cookie auth, no REST nonce needed).
	 * Fixes cached HTML that embeds a logged-out wp_rest nonce for a logged-in visitor.
	 */
	function refreshAuth(config) {
		if (!config || !config.authUrl) {
			return Promise.resolve(config);
		}
		return fetch(config.authUrl, {
			method: 'GET',
			credentials: 'same-origin',
			cache: 'no-store',
			headers: {
				'Cache-Control': 'no-cache'
			}
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (json) {
				var data = json && json.success && json.data ? json.data : null;
				if (!data) {
					return config;
				}
				if (data.nonce) {
					config.nonce = data.nonce;
				}
				if (typeof data.loggedIn !== 'undefined') {
					config.loggedIn = !!data.loggedIn;
				}
				if (typeof data.canGenerate !== 'undefined') {
					config.canGenerate = !!data.canGenerate;
				}
				config.aiReason = data.aiReason ? String(data.aiReason) : '';
				window.thwVotd = config;
				return config;
			})
			.catch(function () {
				return config;
			});
	}

	function parseExistingMap(root) {
		try {
			var raw = root.getAttribute('data-existing-posts') || '{}';
			var parsed = JSON.parse(raw);
			return parsed && typeof parsed === 'object' ? parsed : {};
		} catch (e) {
			return {};
		}
	}

	function readStoredTranslation(config) {
		var prefs = window.hwblUserPreferences;
		if (prefs) {
			if (prefs.isLoggedIn && prefs.isLoggedIn()) {
				return (prefs.getPreferredTranslation && prefs.getPreferredTranslation()) || '';
			}
			return (prefs.readStoredTranslation && prefs.readStoredTranslation()) || '';
		}
		try {
			return window.localStorage.getItem((config && config.translationStorageKey) || 'hwbl_preferred_translation') ||
				window.localStorage.getItem('thw_votd_translation') ||
				'';
		} catch (e) {
			return '';
		}
	}

	function writeStoredTranslation(config, value) {
		if (!value) {
			return;
		}
		var prefs = window.hwblUserPreferences;
		if (prefs && prefs.saveTranslation) {
			prefs.saveTranslation(value);
			return;
		}
		try {
			window.localStorage.setItem((config && config.translationStorageKey) || 'hwbl_preferred_translation', value);
			window.localStorage.setItem('thw_votd_translation', value);
		} catch (e) {
			// Ignore.
		}
	}

	function syncTranslation(config) {
		var stored = readStoredTranslation(config);
		if (!stored) {
			return;
		}
		// Logged-in users already get server preference from PHP; only sync guests from storage.
		if (config && config.loggedIn) {
			return;
		}
		document.querySelectorAll('.thw-votd [data-thw-translation-select]').forEach(function (select) {
			if ([].some.call(select.options, function (opt) { return opt.value === stored; })) {
				select.value = stored;
			}
		});
	}

	function resolveTranslation(root, config) {
		var select = root ? root.querySelector('[data-thw-translation-select]') : null;
		if (select && select.value) {
			return select.value;
		}
		if (root && root.getAttribute('data-default-translation')) {
			return root.getAttribute('data-default-translation');
		}
		return (config && config.defaultTranslation) || '';
	}

	function existingUrlForTranslation(root, config) {
		var map = parseExistingMap(root);
		var translation = resolveTranslation(root, config);
		return translation && map[translation] ? map[translation] : '';
	}

	function updateExistingLink(root, config) {
		if (!root) {
			return;
		}
		var existing = root.querySelector('.thw-votd__existing-link');
		var trigger = root.querySelector('.thw-votd__explain-trigger');
		var loginHint = root.querySelector('.thw-votd__explain-login-hint');
		var aiHint = root.querySelector('.thw-votd__explain-ai-hint');
		var url = existingUrlForTranslation(root, config);
		var canGenerate = !!(config && config.canGenerate);
		var loggedIn = !!(config && config.loggedIn);

		if (existing) {
			if (url) {
				existing.href = url;
				existing.hidden = false;
			} else {
				existing.hidden = true;
			}
		}

		if (trigger) {
			if (url) {
				trigger.hidden = true;
			} else {
				trigger.hidden = !canGenerate;
			}
		}

		if (loginHint) {
			loginHint.hidden = !!(url || canGenerate || loggedIn);
		}

		if (aiHint) {
			if (!url && loggedIn && !canGenerate && config.aiReason) {
				aiHint.textContent = config.aiReason;
				aiHint.hidden = false;
			} else {
				aiHint.hidden = true;
			}
		}
	}

	function formatError(result, config) {
		if (result.status === 429) {
			return config.rateLimit;
		}
		var data = result.data || {};
		if (data.code === 'rest_cookie_invalid_nonce' || data.code === 'rest_forbidden') {
			return (config && config.sessionError) || config.error;
		}
		if (data.message) {
			return data.message;
		}
		return config.error;
	}

	function rememberExisting(root, translation, url) {
		if (!root || !translation || !url) {
			return;
		}
		var map = parseExistingMap(root);
		map[translation] = url;
		root.setAttribute('data-existing-posts', JSON.stringify(map));
		updateExistingLink(root, getConfig());
	}

	function goToPost(url) {
		if (!url) {
			return false;
		}
		window.location.href = url;
		return true;
	}

	function updateVerseDisplay(votdRoot, data) {
		if (!votdRoot || !data) {
			return;
		}
		var textEl = votdRoot.querySelector('.thw-votd__text');
		var refEl = votdRoot.querySelector('.thw-votd__ref');
		var transEl = votdRoot.querySelector('.thw-votd__translation');

		if (textEl && data.text) {
			textEl.textContent = data.text;
		}
		if (refEl && data.reference) {
			refEl.textContent = data.reference;
		}
		if (transEl) {
			if (data.translation_label) {
				transEl.textContent = data.translation_label;
				transEl.hidden = false;
			} else {
				transEl.hidden = true;
			}
		}
		if (data.reference) {
			votdRoot.setAttribute('data-reference', data.reference);
		}
	}

	function fetchPayload(config, translation, refresh) {
		if (!config || !config.payloadUrl) {
			return Promise.resolve(null);
		}
		var url = config.payloadUrl;
		var params = [];
		if (translation) {
			params.push('translation=' + encodeURIComponent(translation));
		}
		if (refresh) {
			params.push('refresh=1');
		}
		if (params.length) {
			url += (url.indexOf('?') >= 0 ? '&' : '?') + params.join('&');
		}
		return fetch(url, {
			method: 'GET',
			headers: {
				'X-WP-Nonce': config.nonce
			},
			credentials: 'same-origin'
		})
			.then(function (response) {
				return response.json().then(function (data) {
					return { ok: response.ok, data: data };
				});
			})
			.then(function (result) {
				if (!result.ok) {
					return null;
				}
				return result.data || null;
			})
			.catch(function () {
				return null;
			});
	}

	function onTranslationChange(event) {
		var select = event.target.closest('[data-thw-translation-select]');
		if (!select) {
			return;
		}
		var explainRoot = select.closest('.thw-votd__explain') || select.closest('.thw-votd');
		var votdRoot = select.closest('.thw-votd');
		var config = getConfig();

		if (select.value) {
			writeStoredTranslation(config, select.value);
		}

		if (!votdRoot || !config) {
			updateExistingLink(explainRoot, config);
			return;
		}

		fetchPayload(config, select.value).then(function (data) {
			if (data) {
				updateVerseDisplay(votdRoot, data);
				if (data.postUrl && explainRoot) {
					rememberExisting(explainRoot, select.value, data.postUrl);
				} else {
					updateExistingLink(explainRoot, config);
				}
			} else {
				updateExistingLink(explainRoot, config);
			}
		});
	}

	function mountVotdExplainJournal(root, output) {
		if (!window.HWBLJournalExport || !root || !output) {
			return;
		}
		var panel = root.querySelector('.thw-votd__explain-panel');
		if (!panel) {
			return;
		}
		var actions = panel.querySelector('.thw-votd__explain-actions');
		if (!actions) {
			return;
		}
		var refEl = root.querySelector('.thw-votd__ref');
		var refText = refEl ? String(refEl.textContent || '').trim() : '';
		var statusEl = panel.querySelector('.thw-votd__journal-status');
		var mount = actions.querySelector('.hwbl-journal-export-mount');
		if (!mount) {
			return;
		}
		actions.hidden = false;
		window.HWBLJournalExport.mountMenu(
			mount,
			function () {
				var parts = [];
				if (refText) {
					parts.push(refText);
				}
				var explain = window.HWBLJournalExport.plainTextFromEl(output);
				if (explain) {
					parts.push(explain);
				}
				return parts.join('\n\n');
			},
			refText || 'Verse explanation',
			root,
			function (msg) {
				if (statusEl) {
					statusEl.textContent = msg || '';
				}
			}
		);
	}

	function onExplainClick(event) {
		var trigger = event.target.closest('.thw-votd__explain-trigger');
		if (!trigger) {
			return;
		}

		event.preventDefault();

		var config = getConfig();
		var root = trigger.closest('.thw-votd');
		var explainRoot = root ? root.querySelector('.thw-votd__explain') : null;
		var panel = root ? root.querySelector('.thw-votd__explain-panel') : null;
		var output = root ? root.querySelector('.thw-votd__explain-output') : null;
		var translation = resolveTranslation(explainRoot || root, config);
		var delivery = (explainRoot && explainRoot.getAttribute('data-delivery')) || (config && config.delivery) || 'redirect';
		var knownUrl = existingUrlForTranslation(explainRoot || root, config);

		if (translation) {
			writeStoredTranslation(config, translation);
		}

		if (knownUrl && goToPost(knownUrl)) {
			return;
		}

		if (!panel || !output || !config || !config.restUrl) {
			return;
		}

		panel.hidden = false;
		output.innerHTML = '<p class="thw-votd__loading">' + config.loading + '</p>';
		trigger.setAttribute('aria-busy', 'true');
		trigger.classList.add('is-loading');

		refreshAuth(config).then(function (freshConfig) {
			config = freshConfig || getConfig() || config;
			updateExistingLink(explainRoot || root, config);

			if (!config.canGenerate) {
				var blocked = config.error;
				if (!config.loggedIn) {
					blocked = config.loginRequired || config.sessionError || blocked;
				} else if (config.aiReason) {
					blocked = config.aiReason;
				} else {
					blocked = config.sessionError || blocked;
				}
				output.innerHTML = '<p class="thw-votd__error">' + blocked + '</p>';
				trigger.removeAttribute('aria-busy');
				trigger.classList.remove('is-loading');
				return;
			}

			var body = new URLSearchParams();
			if (translation) {
				body.set('translation', translation);
			}

			return fetch(config.restUrl, {
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
					if (!result.ok) {
						output.innerHTML = '<p class="thw-votd__error">' + formatError(result, config) + '</p>';
						return;
					}

					var data = result.data || {};
					var postUrl = data.postUrl ? String(data.postUrl) : '';
					if (postUrl && translation) {
						rememberExisting(explainRoot || root, translation, postUrl);
					}

					if (postUrl && (data.redirect || delivery !== 'inline' || data.cached)) {
						goToPost(postUrl);
						return;
					}
					if (postUrl) {
						goToPost(postUrl);
						return;
					}

					var content = data.content ? String(data.content) : '';
					if (content) {
						var html = content;
						if (data.complianceFlagged && config.complianceFlagged) {
							html = '<p class="thw-votd__disclaimer">' + config.complianceFlagged + '</p>' + html;
						}
						output.innerHTML = html;
						mountVotdExplainJournal(root, output);
					} else {
						output.innerHTML = '<p class="thw-votd__error">' + config.error + '</p>';
					}
				});
		})
			.catch(function () {
				output.innerHTML = '<p class="thw-votd__error">' + config.error + '</p>';
			})
			.finally(function () {
				trigger.removeAttribute('aria-busy');
				trigger.classList.remove('is-loading');
			});
	}

	function onExistingClick(event) {
		var link = event.target.closest('.thw-votd__existing-link');
		if (!link || !link.href || link.href === '#' || link.getAttribute('href') === '#') {
			return;
		}
		var root = link.closest('.thw-votd__explain') || link.closest('.thw-votd');
		var config = getConfig();
		var translation = resolveTranslation(root, config);
		if (translation) {
			writeStoredTranslation(config, translation);
		}
	}

	function syncVotdFromServer(votdRoot, config, refresh) {
		if (!votdRoot || !config || !config.payloadUrl) {
			return;
		}
		var explainRoot = votdRoot.querySelector('.thw-votd__explain') || votdRoot;
		var translation = resolveTranslation(explainRoot, config);
		fetchPayload(config, translation, refresh).then(function (data) {
			if (!data || !data.text) {
				return;
			}
			updateVerseDisplay(votdRoot, data);
			if (data.day) {
				votdRoot.setAttribute('data-votd-day', data.day);
				if (explainRoot && explainRoot.setAttribute) {
					explainRoot.setAttribute('data-votd-day', data.day);
				}
			}
			if (data.postUrl && explainRoot) {
				rememberExisting(explainRoot, translation, data.postUrl);
			} else {
				updateExistingLink(explainRoot, config);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var config = getConfig();
		syncTranslation(config);

		refreshAuth(config).then(function (freshConfig) {
			config = freshConfig || getConfig() || config;
			document.querySelectorAll('.thw-votd__explain').forEach(function (root) {
				updateExistingLink(root, config);
			});

			// Always sync from REST so page-cache HTML cannot show yesterday's verse.
			document.querySelectorAll('.thw-votd[data-votd-day]').forEach(function (votdRoot) {
				var siteToday = (config && config.siteToday) || '';
				var renderedDay = votdRoot.getAttribute('data-votd-day') || '';
				var needsRefresh = siteToday && renderedDay && renderedDay !== siteToday;
				syncVotdFromServer(votdRoot, config, needsRefresh);
			});

			// Refresh verse text when a stored translation differs from the server default.
			var stored = readStoredTranslation(config);
			var defaultTrans = (config && config.defaultTranslation) || '';
			if (stored && stored !== defaultTrans) {
				document.querySelectorAll('[data-thw-translation-select]').forEach(function (select) {
					if (select.value === stored) {
						onTranslationChange({ target: select });
					}
				});
			}
		});
	});
	document.addEventListener('click', onExplainClick);
	document.addEventListener('click', onExistingClick);
	document.addEventListener('change', onTranslationChange);
})();
