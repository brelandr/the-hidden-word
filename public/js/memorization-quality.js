(function () {
	'use strict';

	function cfg() {
		return window.hwblMemorization || {};
	}

	function i18n(key, fallback) {
		return (cfg().i18n || {})[key] || fallback;
	}

	function showQualityPanel(widget, onSelect) {
		var panel = widget.querySelector('.hwbl-memorization-quality');
		if (!panel) {
			return;
		}

		panel.hidden = false;
		panel.querySelectorAll('.hwbl-quality-btn').forEach(function (btn) {
			btn.onclick = function () {
				panel.hidden = true;
				onSelect(parseInt(btn.getAttribute('data-quality') || '3', 10));
			};
		});
	}

	window.hwblShowReviewQuality = function (widget, callback) {
		showQualityPanel(widget, callback);
	};

	window.hwblSubmitReviewQuality = function (widget, quality, mode) {
		var lessonId = parseInt(widget.getAttribute('data-lesson-id') || '0', 10);
		if (!lessonId || !cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
			return Promise.resolve(null);
		}

		return fetch(cfg().restUrl + 'memorize/review', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg().nonce
			},
			body: JSON.stringify({
				lesson_id: lessonId,
				quality: quality,
				mode: mode || widget.dataset.mode || 'recall'
			})
		}).then(function (res) {
			return res.ok ? res.json() : null;
		}).then(function (data) {
			var feedback = widget.querySelector('.hwbl-memorization-review-feedback');
			if (feedback && data && data.card) {
				feedback.textContent = i18n('reviewSaved', 'Review saved — next due %s.').replace('%s', data.card.due_date || '');
				feedback.hidden = false;
			}
			return data;
		});
	};
})();
