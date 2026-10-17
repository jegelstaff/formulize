<?php
// The site installed as an app on a phone or computer: the page shown when the device is offline,
// and the instructions for installing it on the browsers that have no install button of their own.
// See formulize_pwaLoadLanguage() in modules/formulize/include/functions.php.

define('_formulize_PWA_OFFLINE_TITLE', "You're offline");
// %s is the name of the site
define('_formulize_PWA_OFFLINE_TEXT', "%s can't be reached right now. Check that this device is connected to the internet, then try again.");
define('_formulize_PWA_OFFLINE_RETRY', 'Try again');

// read aloud by screen readers while the next page is loading in the installed app
define('_formulize_PWA_LOADING', 'Loading');

define('_formulize_PWA_INSTALL_TITLE', 'Install this site as an app');
define('_formulize_PWA_INSTALL_IOS', "Tap the Share button in Safari's toolbar, then choose Add to Home Screen. If it isn't in the list, scroll down to find it. The app's icon then appears on your Home Screen.");
define('_formulize_PWA_INSTALL_MAC_SAFARI', "In Safari's File menu, choose Add to Dock. The app's icon then appears in your Dock.");
define('_formulize_PWA_INSTALL_CLOSE', 'OK');
