/*
 * The service worker of the site installed as an app. Served by sw.php, which puts the settings in
 * self.formulizeApp in front of this file.
 *
 * It does one thing: when a page can't be loaded because the device is offline, it shows a page
 * saying so, with a button to try again. Everything else goes to the network exactly as it would
 * without a service worker.
 *
 * Pages are never kept. That is a rule, not an optimisation to come back to:
 *  - one address can be drawn two ways, in a frame and on its own (the server says so with
 *    Vary: Sec-Fetch-Dest), and a kept page could be shown the wrong way
 *  - pages show people's entries, and a kept page would leave them readable on a shared device after
 *    the person has logged out
 *  - with only the offline page and its stylesheets kept, logging out has nothing to clear
 *
 * Only page loads are looked at, and only plain ones (GET): a form being submitted, and every request
 * a page makes for itself, are not touched. A page in a frame is passed straight through.
 */
(function () {
	'use strict';

	var config = self.formulizeApp;
	var cacheName = config.cachePrefix + config.version;

	self.addEventListener('install', function (event) {
		event.waitUntil(caches.open(cacheName).then(function (cache) {
			var offlinePage = new Response(config.offlineHtml, {
				headers: { 'Content-Type': 'text/html; charset=utf-8' }
			});
			return Promise.all([
				cache.put(config.offlineUrl, offlinePage),
				cache.addAll(config.stylesheets)
			]);
		}).then(function () {
			// An installed app can stay open for days, and the old worker would stay with it: take
			// over at once. Nothing about how pages behave depends on which version is in charge.
			return self.skipWaiting();
		}));
	});

	self.addEventListener('activate', function (event) {
		event.waitUntil(caches.keys().then(function (names) {
			return Promise.all(names.filter(function (name) {
				return name.indexOf(config.cachePrefix) === 0 && name !== cacheName;
			}).map(function (name) {
				return caches.delete(name);
			}));
		}).then(function () {
			// the browser starts loading the page while the worker is starting up, rather than after
			if (self.registration.navigationPreload) {
				return self.registration.navigationPreload.enable();
			}
		}).then(function () {
			return self.clients.claim();
		}));
	});

	function offlinePage() {
		return caches.open(cacheName).then(function (cache) {
			return cache.match(config.offlineUrl);
		});
	}

	self.addEventListener('fetch', function (event) {
		var request = event.request;
		if (request.method !== 'GET') {
			return;
		}
		if (request.mode === 'navigate') {
			// The browser has already asked for the page (navigation preload), with the request it
			// would have made itself, so that is the one to answer with: leaving it unused would make
			// the browser ask a second time.
			var page = Promise.resolve(event.preloadResponse).then(function (preloaded) {
				return preloaded || fetch(request);
			});
			// Only a failure to reach the server shows the offline page, and only for the whole
			// window: a page that loads with an error is shown as it is, and a page in a frame fails
			// the way it would without a worker.
			event.respondWith(request.destination === 'document' ? page.catch(offlinePage) : page);
			return;
		}
		// The offline page's own stylesheets, when it is being shown. Online they come from the
		// network as usual.
		var url = new URL(request.url);
		if (url.origin === self.location.origin && config.stylesheets.indexOf(url.pathname + url.search) !== -1) {
			event.respondWith(fetch(request).catch(function () {
				return caches.open(cacheName).then(function (cache) {
					return cache.match(request);
				});
			}));
		}
	});
})();
