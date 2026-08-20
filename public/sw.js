/* Hidden Word Bible Lessons — offline cache for memorize pack + evangelism pages */
var HWBL_CACHE = 'hwbl-offline-v2';

function hwblIsEvangelismPath(url) {
	try {
		var u = new URL(url);
		return u.pathname.indexOf('/gospel/') === 0 || u.pathname.indexOf('/testimony/') === 0;
	} catch (e) {
		return false;
	}
}

function hwblIsApiCacheable(url) {
	return url.indexOf('/wp-json/hwbl/v1/memorize/offline-pack') !== -1 ||
		url.indexOf('/wp-json/hwbl/v1/memorize/review-queue') !== -1;
}

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
	var evangelism = hwblIsEvangelismPath(url);
	var api = hwblIsApiCacheable(url);
	if (!evangelism && !api) {
		return;
	}
	if (event.request.method !== 'GET') {
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
