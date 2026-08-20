/**
 * Shared Bible translation / faith tradition / reading-mode preference helpers.
 */
(function (window) {
	'use strict';

	var api = {
		getConfig: function () {
			return window.hwblUserPrefs || {};
		},

		translationStorageKey: function () {
			var cfg = api.getConfig();
			return cfg.translationStorageKey || 'hwbl_preferred_translation';
		},

		legacyTranslationStorageKey: function () {
			var cfg = api.getConfig();
			return cfg.legacyTranslationStorageKey || 'thw_votd_translation';
		},

		traditionStorageKey: function () {
			var cfg = api.getConfig();
			return cfg.traditionStorageKey || 'thw_ai_tradition_preset';
		},

		easyReadStorageKey: function () {
			var cfg = api.getConfig();
			return cfg.easyReadStorageKey || 'hwbl_easy_read';
		},

		kidsModeStorageKey: function () {
			var cfg = api.getConfig();
			return cfg.kidsModeStorageKey || 'hwbl_kids_mode';
		},

		isLoggedIn: function () {
			return !!(api.getConfig().loggedIn);
		},

		userTraditionAllowed: function () {
			return !!(api.getConfig().userTradition);
		},

		readStoredTranslation: function () {
			try {
				var key = api.translationStorageKey();
				var value = window.localStorage.getItem(key) || '';
				if (!value) {
					value = window.localStorage.getItem(api.legacyTranslationStorageKey()) || '';
				}
				return value;
			} catch (e) {
				return '';
			}
		},

		writeStoredTranslation: function (value) {
			if (!value) {
				return;
			}
			try {
				window.localStorage.setItem(api.translationStorageKey(), value);
				window.localStorage.setItem(api.legacyTranslationStorageKey(), value);
			} catch (e) {
				// Ignore.
			}
		},

		readStoredTradition: function () {
			try {
				return window.localStorage.getItem(api.traditionStorageKey()) || '';
			} catch (e) {
				return '';
			}
		},

		writeStoredTradition: function (value) {
			if (!value) {
				return;
			}
			try {
				window.localStorage.setItem(api.traditionStorageKey(), value);
			} catch (e) {
				// Ignore.
			}
		},

		readStoredBool: function (keyFn) {
			try {
				var v = window.localStorage.getItem(keyFn()) || '';
				return v === '1' || v === 'true';
			} catch (e) {
				return false;
			}
		},

		writeStoredBool: function (keyFn, value) {
			try {
				window.localStorage.setItem(keyFn(), value ? '1' : '0');
			} catch (e) {
				// Ignore.
			}
		},

		getPreferredTranslation: function () {
			var cfg = api.getConfig();
			if (api.isLoggedIn() && cfg.preferredTranslation) {
				return cfg.preferredTranslation;
			}
			return api.readStoredTranslation();
		},

		getEasyRead: function () {
			var cfg = api.getConfig();
			if (api.isLoggedIn() && typeof cfg.easyRead !== 'undefined') {
				return !!cfg.easyRead;
			}
			return api.readStoredBool(api.easyReadStorageKey);
		},

		getKidsMode: function () {
			var cfg = api.getConfig();
			if (api.isLoggedIn() && typeof cfg.kidsMode !== 'undefined') {
				return !!cfg.kidsMode;
			}
			return api.readStoredBool(api.kidsModeStorageKey);
		},

		applyReadingClasses: function () {
			var easy = api.getEasyRead();
			var kids = api.getKidsMode();
			var root = document.documentElement;
			root.classList.toggle('hwbl-easy-read', easy);
			root.classList.toggle('hwbl-kids-mode', kids);
			document.querySelectorAll('.hwbl-lesson, .hwbl-bible-reader').forEach(function (el) {
				el.classList.toggle('hwbl-easy-read', easy);
				el.classList.toggle('hwbl-kids-mode', kids);
			});
			document.querySelectorAll('[data-hwbl-easy-read-toggle]').forEach(function (btn) {
				btn.setAttribute('aria-pressed', easy ? 'true' : 'false');
				btn.classList.toggle('is-active', easy);
			});
			document.querySelectorAll('[data-hwbl-kids-mode-toggle]').forEach(function (btn) {
				btn.setAttribute('aria-pressed', kids ? 'true' : 'false');
				btn.classList.toggle('is-active', kids);
			});
		},

		postPreferences: function (payload) {
			var cfg = api.getConfig();
			if (!cfg.restUrl || !api.isLoggedIn()) {
				return Promise.resolve(null);
			}
			var body = payload || {};
			return fetch(cfg.restUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-WP-Nonce': cfg.nonce || '',
				},
				body: JSON.stringify(body),
			})
				.then(function (res) {
					return res.json().then(function (data) {
						if (!res.ok) {
							throw data;
						}
						return data;
					});
				})
				.then(function (data) {
					if (data && data.translation) {
						cfg.preferredTranslation = data.translation;
						api.writeStoredTranslation(data.translation);
					}
					if (data && data.tradition) {
						api.writeStoredTradition(data.tradition);
					}
					if (data && typeof data.easyRead !== 'undefined') {
						cfg.easyRead = !!data.easyRead;
						api.writeStoredBool(api.easyReadStorageKey, cfg.easyRead);
					}
					if (data && typeof data.kidsMode !== 'undefined') {
						cfg.kidsMode = !!data.kidsMode;
						api.writeStoredBool(api.kidsModeStorageKey, cfg.kidsMode);
					}
					window.hwblUserPrefs = cfg;
					api.applyReadingClasses();
					return data;
				})
				.catch(function () {
					return null;
				});
		},

		saveTranslation: function (slug) {
			if (!slug) {
				return Promise.resolve(null);
			}
			api.writeStoredTranslation(slug);
			if (!api.isLoggedIn()) {
				return Promise.resolve({ translation: slug });
			}
			return api.postPreferences({ translation: slug });
		},

		saveTradition: function (slug) {
			if (!slug || !api.userTraditionAllowed()) {
				return Promise.resolve(null);
			}
			api.writeStoredTradition(slug);
			if (!api.isLoggedIn()) {
				return Promise.resolve({ tradition: slug });
			}
			return api.postPreferences({ tradition: slug });
		},

		saveEasyRead: function (enabled) {
			var on = !!enabled;
			api.writeStoredBool(api.easyReadStorageKey, on);
			var cfg = api.getConfig();
			cfg.easyRead = on;
			window.hwblUserPrefs = cfg;
			api.applyReadingClasses();
			if (!api.isLoggedIn()) {
				return Promise.resolve({ easyRead: on });
			}
			return api.postPreferences({ easyRead: on });
		},

		saveKidsMode: function (enabled) {
			var on = !!enabled;
			api.writeStoredBool(api.kidsModeStorageKey, on);
			var cfg = api.getConfig();
			cfg.kidsMode = on;
			window.hwblUserPrefs = cfg;
			api.applyReadingClasses();
			if (!api.isLoggedIn()) {
				return Promise.resolve({ kidsMode: on });
			}
			return api.postPreferences({ kidsMode: on });
		},

		applyTranslationToSelect: function (select, slug) {
			if (!select || !slug) {
				return false;
			}
			var match = [].some.call(select.options || [], function (opt) {
				return opt.value === slug;
			});
			if (!match) {
				return false;
			}
			select.value = slug;
			return true;
		},
	};

	window.hwblUserPreferences = api;

	document.addEventListener(
		'change',
		function (event) {
			var traditionSelect = event.target.closest('[data-thw-tradition-select]');
			if (traditionSelect && traditionSelect.value) {
				api.saveTradition(traditionSelect.value);
				document.querySelectorAll('[data-thw-tradition-select]').forEach(function (other) {
					if (
						other !== traditionSelect &&
						[].some.call(other.options, function (opt) {
							return opt.value === traditionSelect.value;
						})
					) {
						other.value = traditionSelect.value;
					}
				});
			}

			var translationSelect = event.target.closest(
				'[data-thw-translation-select], .thw-translation-select, .hwbl-bible-reader__translation'
			);
			if (translationSelect && translationSelect.value) {
				api.saveTranslation(translationSelect.value);
			}
		},
		true
	);

	document.addEventListener('click', function (event) {
		var easyBtn = event.target.closest('[data-hwbl-easy-read-toggle]');
		if (easyBtn) {
			event.preventDefault();
			api.saveEasyRead(!api.getEasyRead());
			return;
		}
		var kidsBtn = event.target.closest('[data-hwbl-kids-mode-toggle]');
		if (kidsBtn) {
			event.preventDefault();
			api.saveKidsMode(!api.getKidsMode());
		}
	});

	document.addEventListener('DOMContentLoaded', function () {
		api.applyReadingClasses();
	});
})(window);
