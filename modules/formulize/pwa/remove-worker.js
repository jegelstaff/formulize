/*
 * What sw.php serves once an administrator has turned the app off: a worker that removes itself.
 *
 * The browser installs it the next time the device opens any page of the site, because it differs
 * from the worker already installed. It then deletes what the worker kept and unregisters, and from
 * the next page on the site behaves as though there had never been a worker. It has no fetch handler,
 * so the pages are not touched in between.
 */
(function () {
	'use strict';

	var config = self.formulizeApp;

	self.addEventListener('install', function (event) {
		event.waitUntil(self.skipWaiting());
	});

	self.addEventListener('activate', function (event) {
		event.waitUntil(caches.keys().then(function (names) {
			return Promise.all(names.filter(function (name) {
				return name.indexOf(config.cachePrefix) === 0;
			}).map(function (name) {
				return caches.delete(name);
			}));
		}).then(function () {
			return self.registration.unregister();
		}));
	});
})();
