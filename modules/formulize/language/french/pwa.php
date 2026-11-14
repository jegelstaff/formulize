<?php
// The site installed as an app on a phone or computer: the page shown when the device is offline,
// and the instructions for installing it on the browsers that have no install button of their own.
// See formulize_pwaLoadLanguage() in modules/formulize/include/functions/pwa.php.

define('_formulize_PWA_OFFLINE_TITLE', 'Vous êtes hors ligne');
// %s is the name of the site
define('_formulize_PWA_OFFLINE_TEXT', "%s est inaccessible pour le moment. Vérifiez que cet appareil est connecté à Internet, puis réessayez.");
define('_formulize_PWA_OFFLINE_RETRY', 'Réessayer');

// read aloud by screen readers while the next page is loading in the installed app
define('_formulize_PWA_LOADING', 'Chargement');

// the item at the end of the menu, in the words the browser itself uses, and the title of the steps
// shown on an iPhone, an iPad or a Mac
define('_formulize_PWA_INSTALL_PHONE', "Ajouter ce site à votre écran d'accueil");
define('_formulize_PWA_INSTALL_DOCK', 'Ajouter ce site à votre Dock');
define('_formulize_PWA_INSTALL_COMPUTER', 'Installer ce site comme application');
define('_formulize_PWA_INSTALL_IOS', "Touchez le bouton Partager dans la barre d'outils de Safari, puis choisissez Sur l'écran d'accueil. Si l'option n'est pas dans la liste, faites défiler vers le bas pour la trouver. L'icône de l'application apparaît alors sur votre écran d'accueil.");
define('_formulize_PWA_INSTALL_MAC_SAFARI', "Dans le menu Fichier de Safari, choisissez Ajouter au Dock. L'icône de l'application apparaît alors dans votre Dock.");
define('_formulize_PWA_INSTALL_CLOSE', 'OK');
