/*
 * The install item at the end of the Formulize menu, [data-formulize-install-app], which is only there
 * for people who are signed in (formulize_pwaInstallMenuSection()). Only on the pages where the site
 * can be installed as an app (formulize_pwaFeaturesApply()), after formulize-app.js, and with
 * window.formulize.pwa set by formulize_addAppHeadTags().
 *
 * The item starts hidden, and is shown only where installing can actually be done from it, and never
 * in the installed app itself. It says what it does in the words the browser uses: Add this site to
 * your home screen on phones and tablets, Add this site to your Dock in Safari on a Mac, and Install
 * this site as an app on other computers.
 *  - Chrome, Edge, Samsung Internet: when the browser says the site can be installed, and the item
 *    then installs it
 *  - Safari on iPhone and iPad (and every other browser there, which is Safari underneath), and
 *    Safari on a Mac: these have no way for a page to install itself, so the item opens a dialog
 *    with the steps to follow in the browser's own menus
 *  - Firefox: not offered. It installs sites through its own menus, where it can.
 *
 * The browser's own install prompt is left alone: Chrome on Android shows its banner as usual, and
 * the item is a way in that is always there, for when that has been dismissed.
 */
(function () {
	'use strict';

	var formulize = window.formulize = window.formulize || {};
	var strings = (formulize.pwa && formulize.pwa.strings) || {};
	var installPrompt = null;

	// The one place the browser is identified by its user agent, only to choose the item's words, and
	// the steps for the browsers that can't install a site from a page:
	// 'ios' and 'macSafari' show the steps, 'android' installs, and '' is any other browser
	function platform() {
		var ua = window.navigator.userAgent;
		if (/iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && window.navigator.maxTouchPoints > 1)) {
			return 'ios';
		}
		// Add to Dock arrived in Safari 17
		var safari = /Macintosh/.test(ua) && !/Chrome\/|Chromium\/|Edg\/|OPR\/|Firefox\//.test(ua) && ua.match(/Version\/(\d+)[\d.]* .*Safari\//);
		if (safari && parseInt(safari[1], 10) >= 17) {
			return 'macSafari';
		}
		if (/Android/.test(ua)) {
			return 'android';
		}
		return '';
	}

	function showsSteps() {
		return platform() === 'ios' || platform() === 'macSafari';
	}

	function label() {
		switch (platform()) {
			case 'ios':
			case 'android':
				return strings.installPhone || 'Add this site to your home screen';
			case 'macSafari':
				return strings.installDock || 'Add this site to your Dock';
			default:
				return strings.installComputer || 'Install this site as an app';
		}
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

	function element(tag, className, parent) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (parent) {
			parent.appendChild(node);
		}
		return node;
	}

	// The Formulize UI dialog: header, body and footer in a <form method="dialog">, so the button
	// closes it with no script. Opened from here rather than by a commandfor button, because the item
	// only shows the steps when the browser can't install the site itself.
	function showSteps() {
		var dialog = document.getElementById('formulize-install-app-dialog');
		if (!dialog) {
			dialog = element('dialog', 'fz-dialog', document.body);
			dialog.id = 'formulize-install-app-dialog';
			dialog.setAttribute('aria-labelledby', 'formulize-install-app-dialog-title');
			var form = element('form', '', dialog);
			form.method = 'dialog';
			var title = element('h2', 'fz-dialog__title', element('div', 'fz-dialog__header', form));
			title.id = 'formulize-install-app-dialog-title';
			title.textContent = label();
			element('p', '', element('div', 'fz-dialog__body', form)).textContent = platform() === 'ios'
				? (strings.installIOS || "Tap the Share button in Safari's toolbar, then choose Add to Home Screen.")
				: (strings.installMacSafari || "In Safari's File menu, choose Add to Dock.");
			var close = element('button', 'fz-btn fz-btn--primary', element('div', 'fz-dialog__footer', form));
			close.type = 'submit';
			close.textContent = strings.close || 'OK';
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
		} else if (showsSteps()) {
			showSteps();
		}
	}

	// Kept for the item, without preventDefault(), which would hold back Chrome's own banner
	window.addEventListener('beforeinstallprompt', function (event) {
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
			// the item is the link, or holds it, as in the menu
			var link = item.tagName === 'A' ? item : item.querySelector('a');
			if (link) {
				link.textContent = label();
			}
			item.addEventListener('click', install);
		});
		if (!formulize.isInstalledApp() && (installPrompt || showsSteps())) {
			showItems(true);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setUp);
	} else {
		setUp();
	}
})();
