/**
 * Translation switcher for premium multi-version support.
 */
(function () {
	'use strict';

	function applyGuestPreferredTranslation() {
		var prefs = window.hwblUserPreferences;
		if (!prefs || (prefs.isLoggedIn && prefs.isLoggedIn())) {
			return;
		}
		var stored = prefs.getPreferredTranslation ? prefs.getPreferredTranslation() : '';
		if (!stored) {
			return;
		}
		document.querySelectorAll('.thw-translation-select').forEach(function (select) {
			if (select.value === stored) {
				return;
			}
			if (prefs.applyTranslationToSelect && prefs.applyTranslationToSelect(select, stored)) {
				// Trigger verse refresh when guest storage differs from server-rendered default.
				select.dispatchEvent(new Event('change', { bubbles: true }));
			}
		});
	}

	document.addEventListener('change', function (e) {
		var select = e.target.closest('.thw-translation-select');
		if (!select) {
			return;
		}

		var lesson = select.closest('.hwbl-lesson, .thw-lesson');
		if (!lesson) {
			return;
		}

		var lessonId = lesson.getAttribute('data-lesson-id');
		var translation = select.value;

		if (!window.thwPremium || !lessonId) {
			return;
		}

		var prefs = window.hwblUserPreferences;
		if (prefs && prefs.saveTranslation) {
			prefs.saveTranslation(translation);
		}

		fetch(window.thwPremium.restUrl + 'verse?lesson_id=' + lessonId + '&translation=' + encodeURIComponent(translation), {
			headers: { 'X-WP-Nonce': window.thwPremium.nonce }
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data.text) {
					return;
				}
				var verseEl = lesson.querySelector('.hwbl-verse-text, .thw-verse-text');
				if (verseEl) {
					verseEl.textContent = data.text;
					verseEl.setAttribute('data-verse', data.text);
				}
				var memEl = lesson.querySelector('.hwbl-memorization, .thw-memorization');
				if (memEl) {
					memEl.setAttribute('data-verse', data.text);
					document.dispatchEvent(new CustomEvent('thw:translation-changed', {
						detail: { widget: memEl }
					}));
					document.dispatchEvent(new CustomEvent('hwbl:translation-changed', {
						detail: { widget: memEl }
					}));
				}
				var copyrightEl = lesson.querySelector('.hwbl-copyright, .thw-copyright');
				if (copyrightEl && data.copyright) {
					copyrightEl.outerHTML = data.copyright;
				}
			});
	});

	document.addEventListener('DOMContentLoaded', applyGuestPreferredTranslation);
})();
