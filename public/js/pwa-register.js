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
		}).catch(function () {
			// SW registration is best-effort.
		});
	});
})();
