/**
 * Debounced journal autosave under Discussion questions.
 */
(function () {
	'use strict';

	var cfg = window.hwblJournal || {};
	var timers = {};

	function save(index, value, statusEl) {
		if (!cfg.restUrl || !cfg.nonce) {
			return;
		}
		if (statusEl) {
			statusEl.textContent = 'Saving…';
		}
		fetch(cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify({
				question_index: index,
				response: value,
			}),
		})
			.then(function (res) {
				if (!res.ok) {
					throw new Error('save failed');
				}
				if (statusEl) {
					statusEl.textContent = 'Saved';
				}
			})
			.catch(function () {
				if (statusEl) {
					statusEl.textContent = 'Could not save';
				}
			});
	}

	function loadExisting(root) {
		if (!cfg.restUrl) {
			return;
		}
		fetch(cfg.restUrl, {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': cfg.nonce || '' },
		})
			.then(function (res) {
				return res.ok ? res.json() : null;
			})
			.then(function (body) {
				if (!body || !Array.isArray(body.entries)) {
					return;
				}
				body.entries.forEach(function (entry) {
					var ta = root.querySelector(
						'textarea[data-question-index="' + entry.question_index + '"]'
					);
					if (ta && !ta.value) {
						ta.value = entry.response || '';
					}
				});
			})
			.catch(function () {});
	}

	function bind(root) {
		root.querySelectorAll('.hwbl-journal-response').forEach(function (ta) {
			var index = parseInt(ta.getAttribute('data-question-index') || '0', 10);
			var statusEl = ta.parentNode.querySelector('.hwbl-journal-status');
			ta.addEventListener('input', function () {
				clearTimeout(timers[index]);
				timers[index] = setTimeout(function () {
					save(index, ta.value, statusEl);
				}, 600);
			});
			ta.addEventListener('blur', function () {
				clearTimeout(timers[index]);
				save(index, ta.value, statusEl);
			});
		});
		loadExisting(root);
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-lesson').forEach(bind);
	});
})();
