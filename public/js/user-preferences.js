/**
 * Shared Bible translation / faith tradition preference helpers.
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
				// Keep VOTD legacy key in sync for older cached scripts.
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

		/**
		 * Preferred translation for UI restore: server meta when logged in, else localStorage.
		 */
		getPreferredTranslation: function () {
			var cfg = api.getConfig();
			if (api.isLoggedIn() && cfg.preferredTranslation) {
				return cfg.preferredTranslation;
			}
			return api.readStoredTranslation();
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
						window.hwblUserPrefs = cfg;
						api.writeStoredTranslation(data.translation);
					}
					if (data && data.tradition) {
						api.writeStoredTradition(data.tradition);
					}
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
})(window);
