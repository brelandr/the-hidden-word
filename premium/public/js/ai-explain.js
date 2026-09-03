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

	function findLessonWrap(root) {
		if (!root) {
			return null;
		}
		return root.closest('.hwbl-lesson, .thw-lesson');
	}

	function ensurePanel(root, lessonWrap, lessonId) {
		var host = lessonWrap || (root && root.parentNode) || null;
		if (!host) {
			return null;
		}

		var panel = host.querySelector
			? host.querySelector('.thw-ai-explain-panel')
			: null;
		if (panel) {
			return panel;
		}

		// Controls render the panel as a sibling; check that first.
		if (root && root.nextElementSibling && root.nextElementSibling.classList.contains('thw-ai-explain-panel')) {
			return root.nextElementSibling;
		}

		panel = document.createElement('div');
		panel.id = 'thw-panel-ai-explain-' + lessonId;
		panel.className = 'thw-ai-explain-panel';
		panel.setAttribute('role', 'region');
		panel.hidden = true;
		panel.innerHTML =
			'<h3 class="thw-panel-title">AI Explanation</h3>' +
			'<div class="thw-ai-explain-output" aria-live="polite"></div>' +
			'<div class="hwbl-journal-export-actions" hidden><span class="hwbl-journal-export-mount"></span></div>';

		if (root && root.parentNode) {
			if (root.nextSibling) {
				root.parentNode.insertBefore(panel, root.nextSibling);
			} else {
				root.parentNode.appendChild(panel);
			}
		} else {
			host.appendChild(panel);
		}

		return panel;
	}

	function renderFinalContent(output, data, config, panel, root) {
		var content = data && data.content ? String(data.content) : '';
		if (!content) {
			output.innerHTML = '<p class="thw-notice thw-notice-info">' + config.error + '</p>';
			return;
		}
		var flagged = !!(data && data.complianceFlagged);
		var html = content;
		if (flagged && config.complianceFlagged) {
			html = '<p class="thw-ai-explain-disclaimer thw-ai-compliance-flagged">' + config.complianceFlagged + '</p>' + html;
		}
		output.innerHTML = html;
		mountLessonExplainJournal(panel, output, root);
	}

	function mountLessonExplainJournal(panel, output, root) {
		if (!window.HWBLJournalExport || !panel || !output) {
			return;
		}
		var lessonWrap = findLessonWrap(root) || root;
		var titleEl = lessonWrap
			? lessonWrap.querySelector('.hwbl-lesson-title, .entry-title, h1')
			: null;
		var refEl = lessonWrap
			? lessonWrap.querySelector('.hwbl-lesson-reference')
			: null;
		var title = titleEl ? String(titleEl.textContent || '').trim() : 'Lesson explanation';
		var ref = refEl ? String(refEl.textContent || '').trim() : '';
		var statusEl = panel.querySelector('.hwbl-journal-export-status');
		window.HWBLJournalExport.ensureExplainActions(
			panel,
			function () {
				var parts = [];
				if (title) {
					parts.push(title);
				}
				if (ref && ref !== title) {
					parts.push(ref);
				}
				var explain = window.HWBLJournalExport.plainTextFromEl(output);
				if (explain) {
					parts.push(explain);
				}
				return parts.join('\n\n');
			},
			title,
			lessonWrap || root,
			function (msg) {
				if (!statusEl) {
					statusEl = document.createElement('p');
					statusEl.className = 'hwbl-journal-export-status description';
					statusEl.setAttribute('role', 'status');
					panel.appendChild(statusEl);
				}
				statusEl.textContent = msg || '';
			}
		);
	}

	function escapeHtml(text) {
		return String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function parseSseChunk(buffer, onEvent) {
		var parts = buffer.split('\n\n');
		var rest = parts.pop() || '';
		parts.forEach(function (block) {
			var eventName = 'message';
			var dataLines = [];
			block.split('\n').forEach(function (line) {
				if (line.indexOf('event:') === 0) {
					eventName = line.slice(6).trim();
				} else if (line.indexOf('data:') === 0) {
					dataLines.push(line.slice(5).trim());
				}
			});
			if (!dataLines.length) {
				return;
			}
			var raw = dataLines.join('\n');
			var payload = {};
			try {
				payload = JSON.parse(raw);
			} catch (e) {
				payload = { text: raw };
			}
			onEvent(eventName, payload);
		});
		return rest;
	}

	function requestExplainJson(config, body) {
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
			});
	}

	function requestExplainStream(config, body, output) {
		var url = config.restUrl + (config.restUrl.indexOf('?') >= 0 ? '&' : '?') + 'stream=1';
		body.set('stream', '1');

		return fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'Accept': 'text/event-stream',
				'X-WP-Nonce': config.nonce
			},
			credentials: 'same-origin',
			body: body.toString()
		}).then(function (response) {
			var contentType = (response.headers.get('content-type') || '').toLowerCase();
			if (!response.ok) {
				return response.json().then(function (data) {
					return { ok: false, status: response.status, data: data };
				});
			}

			// Server fell back to JSON (streaming unavailable).
			if (contentType.indexOf('text/event-stream') === -1) {
				return response.json().then(function (data) {
					return { ok: true, status: response.status, data: data, streamed: false };
				});
			}

			if (!response.body || !response.body.getReader) {
				return { ok: false, status: response.status, data: { message: config.error } };
			}

			var reader = response.body.getReader();
			var decoder = new TextDecoder();
			var buffer = '';
			var rawText = '';
			var streamDone = null;
			var streamError = null;

			output.innerHTML = '<div class="thw-ai-explain-stream"></div>';
			var streamEl = output.querySelector('.thw-ai-explain-stream');

			function onEvent(eventName, payload) {
				if (eventName === 'token' && payload && payload.text) {
					rawText += String(payload.text);
					if (streamEl) {
						streamEl.innerHTML = '<pre class="thw-ai-explain-stream-text">' + escapeHtml(rawText) + '</pre>';
					}
				} else if (eventName === 'done') {
					streamDone = payload || {};
				} else if (eventName === 'error') {
					streamError = payload || { message: config.error };
				}
			}

			function pump() {
				return reader.read().then(function (result) {
					if (result.done) {
						buffer = parseSseChunk(buffer + '\n\n', onEvent);
						if (streamError) {
							return {
								ok: false,
								status: 502,
								data: { message: streamError.message || config.error },
								streamed: true
							};
						}
						return {
							ok: true,
							status: 200,
							data: streamDone || { content: rawText },
							streamed: true
						};
					}
					buffer += decoder.decode(result.value, { stream: true });
					buffer = parseSseChunk(buffer, onEvent);
					return pump();
				});
			}

			return pump();
		});
	}

	function handleExplainClick(trigger) {
		var config = getExplainConfig();
		var root = trigger.closest('.thw-ai-explain-controls');
		var lessonWrap = findLessonWrap(root) || findLessonWrap(trigger);
		var scopeSelect = root ? root.querySelector('.thw-ai-explain-scope') : null;
		var traditionSelect = root ? root.querySelector('.thw-ai-explain-tradition, [data-thw-tradition-select]') : null;
		var lessonId = resolveLessonId(root, lessonWrap, trigger);

		if (!root) {
			window.alert((config && config.noPanel) || '');
			return;
		}

		var panel = ensurePanel(root, lessonWrap, lessonId);
		var output = panel ? panel.querySelector('.thw-ai-explain-output') : null;

		if (!panel || !output) {
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

		var useStream = !!config.stream && typeof ReadableStream !== 'undefined' && window.fetch;
		var request = useStream
			? requestExplainStream(config, body, output)
			: requestExplainJson(config, body);

		request
			.then(function (result) {
				if (!result.ok) {
					output.innerHTML = '<p class="thw-notice thw-notice-info">' + formatErrorMessage(result, config) + '</p>';
					return;
				}
				renderFinalContent(output, result.data, config, panel, root);
			})
			.catch(function () {
				// Streaming path failed — fall back to classic JSON.
				if (useStream) {
					body.delete('stream');
					return requestExplainJson(config, body).then(function (result) {
						if (!result.ok) {
							output.innerHTML = '<p class="thw-notice thw-notice-info">' + formatErrorMessage(result, config) + '</p>';
							return;
						}
						renderFinalContent(output, result.data, config, panel, root);
					});
				}
				output.innerHTML = '<p class="thw-notice thw-notice-info">' + config.error + '</p>';
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
