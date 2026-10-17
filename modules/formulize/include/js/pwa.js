/*
 * The Install app item in the theme's menu, [data-formulize-install-app]. Only on the pages where the
 * site can be installed as an app (formulize_pwaFeaturesApply()), after formulize-app.js, and with
 * window.formulize.pwa set by formulize_addAppHeadTags().
 *
 * The item starts hidden, and is shown only where installing can actually be done from it, and never
 * in the installed app itself:
 *  - Chrome, Edge, Samsung Internet: when the browser says the site can be installed, and the item
 *    then installs it
 *  - Safari on iPhone and iPad (and every other browser there, which is Safari underneath), and
 *    Safari on a Mac: these have no way for a page to install itself, so the item opens a dialog
 *    with the steps to follow in the browser's own menus
 *  - Firefox: not offered. It installs sites through its own menus, where it can.
 *
 * No pop-ups and no reminders: the browser's own install prompt is held back too, and the item is the
 * only way in.
 */
(function () {
	'use strict';

	var formulize = window.formulize = window.formulize || {};
	var strings = (formulize.pwa && formulize.pwa.strings) || {};
	var installPrompt = null;

	// The one place the browser is identified by its user agent: the browsers that can't install a
	// site from a page, and only for showing the steps.
	function manualInstallPlatform() {
		var ua = window.navigator.userAgent;
		if (/iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && window.navigator.maxTouchPoints > 1)) {
			return 'ios';
		}
		// Add to Dock arrived in Safari 17
		var safari = /Macintosh/.test(ua) && !/Chrome\/|Chromium\/|Edg\/|OPR\/|Firefox\//.test(ua) && ua.match(/Version\/(\d+)[\d.]* .*Safari\//);
		if (safari && parseInt(safari[1], 10) >= 17) {
			return 'macSafari';
		}
		return '';
	}

	function items() {
		return document.querySelectorAll('[data-formulize-install-app]');
	}

	function showItems(show) {
		Array.prototype.forEach.call(items(), function (item) {
			if (show) {
				item.removeAttribute('hidden');
			} else {
				item.setAttribute('hidden', '');
			}
		});
	}

	function showSteps(platform) {
		var dialog = document.getElementById('formulize-install-app-dialog');
		if (!dialog) {
			dialog = document.createElement('dialog');
			dialog.id = 'formulize-install-app-dialog';
			dialog.className = 'fz-dialog';
			dialog.setAttribute('aria-labelledby', 'formulize-install-app-dialog-title');
			var title = document.createElement('h2');
			title.id = 'formulize-install-app-dialog-title';
			title.className = 'fz-dialog__title';
			title.textContent = strings.installTitle || 'Install this site as an app';
			var steps = document.createElement('p');
			steps.textContent = platform === 'ios'
				? (strings.installIOS || "Tap the Share button in Safari's toolbar, then choose Add to Home Screen.")
				: (strings.installMacSafari || "In Safari's File menu, choose Add to Dock.");
			var actions = document.createElement('form');
			actions.method = 'dialog';
			actions.className = 'fz-dialog__actions';
			var close = document.createElement('button');
			close.className = 'fz-btn fz-btn--primary';
			close.textContent = strings.close || 'OK';
			actions.appendChild(close);
			dialog.appendChild(title);
			dialog.appendChild(steps);
			dialog.appendChild(actions);
			document.body.appendChild(dialog);
		}
		dialog.showModal();
	}

	function install(event) {
		event.preventDefault();
		if (installPrompt) {
			var prompt = installPrompt;
			installPrompt = null;
			showItems(false); // a prompt can only be shown once
			prompt.prompt();
		} else if (manualInstallPlatform()) {
			showSteps(manualInstallPlatform());
		}
	}

	window.addEventListener('beforeinstallprompt', function (event) {
		event.preventDefault(); // no banner: the item is the way in
		installPrompt = event;
		if (!formulize.isInstalledApp()) {
			showItems(true);
		}
	});

	window.addEventListener('appinstalled', function () {
		installPrompt = null;
		showItems(false);
	});

	function setUp() {
		Array.prototype.forEach.call(items(), function (item) {
			item.addEventListener('click', install);
		});
		if (!formulize.isInstalledApp() && (installPrompt || manualInstallPlatform())) {
			showItems(true);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setUp);
	} else {
		setUp();
	}
})();
