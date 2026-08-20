(function () {
	'use strict';

	if (!window.hwblPwa || !('serviceWorker' in navigator)) {
		return;
	}

	window.addEventListener('load', function () {
		navigator.serviceWorker.register(hwblPwa.swUrl).then(function (registration) {
			if (window.hwblMemorization && window.hwblMemorization.loggedIn && registration.active) {
				registration.active.postMessage({
					type: 'hwbl-prefetch-offline',
					url: hwblMemorization.restUrl + 'memorize/offline-pack'
				});
			}
			var path = window.location.pathname || '';
			if (registration.active && (path.indexOf('/gospel/') === 0 || path.indexOf('/testimony/') === 0)) {
				registration.active.postMessage({
					type: 'hwbl-prefetch-offline',
					url: window.location.href
				});
			}
		}).catch(function () {
			// SW registration is best-effort.
		});
	});
})();
