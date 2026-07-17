/* Hidden Word Bible Lessons — offline cache for memorization pack */
var HWBL_CACHE = 'hwbl-offline-v1';
var HWBL_OFFLINE_URLS = [
	'/wp-json/hwbl/v1/memorize/offline-pack'
];

self.addEventListener('install', function (event) {
	event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches.keys().then(function (keys) {
			return Promise.all(keys.filter(function (key) {
				return key.indexOf('hwbl-offline-') === 0 && key !== HWBL_CACHE;
			}).map(function (key) {
				return caches.delete(key);
			}));
		}).then(function () {
			return self.clients.claim();
		})
	);
});

self.addEventListener('fetch', function (event) {
	var url = event.request.url;
	if (url.indexOf('/wp-json/hwbl/v1/memorize/offline-pack') === -1 &&
		url.indexOf('/wp-json/hwbl/v1/memorize/review-queue') === -1) {
		return;
	}

	event.respondWith(
		fetch(event.request)
			.then(function (response) {
				if (response && response.ok) {
					var copy = response.clone();
					caches.open(HWBL_CACHE).then(function (cache) {
						cache.put(event.request, copy);
					});
				}
				return response;
			})
			.catch(function () {
				return caches.match(event.request);
			})
	);
});

self.addEventListener('message', function (event) {
	if (!event.data || event.data.type !== 'hwbl-prefetch-offline') {
		return;
	}
	event.waitUntil(
		caches.open(HWBL_CACHE).then(function (cache) {
			return cache.add(event.data.url);
		})
	);
});
