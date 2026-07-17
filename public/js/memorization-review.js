(function () {
	'use strict';

	function cfg() {
		return window.hwblMemorization || {};
	}

	function i18n(key, fallback) {
		var strings = (cfg().i18n || {});
		return strings[key] || fallback;
	}

	function fetchQueue() {
		if (!cfg().loggedIn || !cfg().restUrl || !cfg().nonce) {
			return Promise.resolve(null);
		}
		return fetch(cfg().restUrl + 'memorize/review-queue', {
			headers: { 'X-WP-Nonce': cfg().nonce }
		}).then(function (res) {
			return res.ok ? res.json() : null;
		});
	}

	function renderQueueList(container, data) {
		if (!container || !data) {
			return;
		}

		var due = data.due || [];
		var newCards = data.new || [];
		var html = '';

		if (!due.length && !newCards.length) {
			html = '<p class="hwbl-memorize-reviews__empty">' + i18n('queueEmpty', 'No reviews due — great work! Open a lesson to add verses to your deck.') + '</p>';
			container.innerHTML = html;
			return;
		}

		if (due.length) {
			html += '<h3 class="hwbl-memorize-reviews__heading">' + i18n('dueHeading', 'Due today') + '</h3><ul class="hwbl-memorize-reviews__list">';
			due.forEach(function (item) {
				html += '<li><a class="hwbl-memorize-reviews__link" href="' + (item.url || '#') + '" data-lesson-id="' + item.lesson_id + '">' +
					(item.reference || ('Lesson ' + item.lesson_id)) + '</a></li>';
			});
			html += '</ul>';
		}

		if (newCards.length) {
			html += '<h3 class="hwbl-memorize-reviews__heading">' + i18n('newHeading', 'New cards') + '</h3><ul class="hwbl-memorize-reviews__list">';
			newCards.forEach(function (item) {
				html += '<li><a class="hwbl-memorize-reviews__link" href="' + (item.url || '#') + '" data-lesson-id="' + item.lesson_id + '">' +
					(item.reference || ('Lesson ' + item.lesson_id)) + '</a></li>';
			});
			html += '</ul>';
		}

		container.innerHTML = html;
	}

	function renderBanner(banner, data) {
		if (!banner || !data) {
			return;
		}

		var dueCount = (data.due || []).length;
		var stats = data.stats || {};
		if (typeof stats.due === 'number') {
			dueCount = stats.due;
		}

		if (dueCount < 1) {
			banner.hidden = true;
			banner.textContent = '';
			return;
		}

		banner.hidden = false;
		banner.textContent = dueCount === 1
			? i18n('dueBannerOne', '1 review due today — start with recall practice below.')
			: i18n('dueBannerMany', '%d reviews due today — start with recall practice below.').replace('%d', String(dueCount));
	}

	function initDashboard(root) {
		var queue = root.querySelector('.hwbl-memorize-reviews__queue');
		fetchQueue().then(function (data) {
			renderQueueList(queue, data);
		});
	}

	function initLessonBanner() {
		var banner = document.querySelector('.hwbl-memorization-review-banner');
		if (!banner) {
			return;
		}
		fetchQueue().then(function (data) {
			renderBanner(banner, data);
			if (!data || !cfg().loggedIn) {
				return;
			}
			var lessonId = parseInt(banner.getAttribute('data-lesson-id') || '0', 10);
			var isDue = (data.due || []).some(function (item) {
				return parseInt(item.lesson_id, 10) === lessonId;
			});
			if (isDue && typeof window.hwblInitMemorizationReviewMode === 'function') {
				document.querySelectorAll('.hwbl-memorization').forEach(function (widget) {
					if (parseInt(widget.getAttribute('data-lesson-id') || '0', 10) === lessonId) {
						window.hwblInitMemorizationReviewMode(widget);
					}
				});
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-hwbl-memorize-reviews]').forEach(initDashboard);
		initLessonBanner();
	});
})();
