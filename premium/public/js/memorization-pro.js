/**
 * Premium memorization: mark as memorized + difficulty controls.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.thw-mark-memorized');
		if (!btn || btn.classList.contains('is-memorized')) {
			return;
		}

		var lessonId = btn.getAttribute('data-lesson-id');
		if (!lessonId || !window.thwPremium || !window.thwPremium.loggedIn) {
			return;
		}

		btn.disabled = true;

		fetch(window.thwPremium.restUrl + 'memorize', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.thwPremium.nonce
			},
			body: JSON.stringify({ lesson_id: parseInt(lessonId, 10) })
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				btn.disabled = false;
				if (data.success) {
					btn.classList.add('is-memorized');
					btn.textContent = (window.thwPremium && window.thwPremium.memorizedLabel) || 'Memorized ✓';
				}
			})
			.catch(function () {
				btn.disabled = false;
			});
	});

	document.addEventListener('click', function (e) {
		var shareBtn = e.target.closest('.thw-share-to-activity');
		if (!shareBtn || shareBtn.classList.contains('is-shared')) {
			return;
		}

		var shareLessonId = shareBtn.getAttribute('data-lesson-id');
		if (!shareLessonId || !window.thwPremium || !window.thwPremium.loggedIn) {
			return;
		}

		shareBtn.disabled = true;

		fetch(window.thwPremium.restUrl + 'share-activity', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.thwPremium.nonce
			},
			body: JSON.stringify({ lesson_id: parseInt(shareLessonId, 10) })
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				shareBtn.disabled = false;
				if (data.success) {
					shareBtn.classList.add('is-shared');
					shareBtn.textContent = (window.thwPremium && window.thwPremium.sharedLabel) || 'Shared ✓';
				}
			})
			.catch(function () {
				shareBtn.disabled = false;
			});
	});

	// Premium hide-many supports both hwbl and legacy thw widget class names.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.thw-hide-random, .hwbl-hide-random');
		if (!btn) {
			return;
		}
		var widget = btn.closest('.thw-memorization, .hwbl-memorization');
		if (!widget) {
			return;
		}
		if (!widget.classList.contains('thw-memorization-pro')) {
			widget.classList.add('thw-memorization-pro');
			var extraBtn = document.createElement('button');
			extraBtn.type = 'button';
			extraBtn.className = 'thw-btn thw-hide-many';
			extraBtn.textContent = (window.thwPremium && window.thwPremium.hideFiveWords) || 'Hide 5 Words';
			btn.parentNode.insertBefore(extraBtn, btn.nextSibling);

			extraBtn.addEventListener('click', function () {
				for (var i = 0; i < 5; i++) {
					var hideBtn = widget.querySelector('.thw-hide-random, .hwbl-hide-random');
					if (hideBtn) {
						hideBtn.click();
					}
				}
			});
		}
	});
})();

/**
 * Offer to claim browser-local memorization streak after login.
 */
(function () {
	'use strict';

	var STREAK_KEY = 'hwbl_mem_streak';
	var LEGACY_STREAK_KEY = 'thw_mem_streak';
	var DISMISS_KEY = 'thw_streak_claim_dismissed';

	function loadLocalStreak() {
		try {
			var raw = localStorage.getItem(STREAK_KEY);
			if (!raw) {
				raw = localStorage.getItem(LEGACY_STREAK_KEY);
			}
			return raw ? JSON.parse(raw) : null;
		} catch (err) {
			return null;
		}
	}

	function maybeOfferStreakClaim() {
		if (!window.thwPremium || !window.thwPremium.loggedIn || window.thwPremium.streakClaimed) {
			return;
		}
		if (sessionStorage.getItem(DISMISS_KEY)) {
			return;
		}

		var local = loadLocalStreak();
		if (!local || !local.count || local.count < 1 || !local.lastDate) {
			return;
		}

		var prompt = (window.thwPremium.claimPrompt || 'Save your %d-day browser streak to your account?')
			.replace('%d', local.count);
		if (!window.confirm(prompt)) {
			sessionStorage.setItem(DISMISS_KEY, '1');
			return;
		}

		fetch(window.thwPremium.streakClaimUrl || (window.thwPremium.restUrl + 'claim-streak'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.thwPremium.nonce
			},
			body: JSON.stringify({ count: local.count, last_date: local.lastDate })
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.success) {
					window.alert(window.thwPremium.claimSuccess || 'Streak saved!');
					window.thwPremium.streakClaimed = true;
				}
			});
	}

	document.addEventListener('DOMContentLoaded', maybeOfferStreakClaim);
})();
