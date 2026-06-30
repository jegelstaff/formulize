<?php
/**
 * Chaînes de langue pour le registre des paramètres d'administration
 * (modules/formulize/include/configsettings_registry.php).
 *
 * Couvre : noms des onglets, noms des sous-vues, titres des sections,
 * et remplacements de légendes/descriptions utilisés dans l'interface des paramètres.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// --- Noms des onglets principaux ---
define('_AM_CFG_TAB_USERS', 'Utilisateurs');
define('_AM_CFG_TAB_SETTINGS', 'Paramètres');

// --- Noms des vues : onglet Utilisateurs ---
define('_AM_CFG_VIEW_USERS_SETTINGS', 'Paramètres');
define('_AM_CFG_VIEW_USERS_EMAIL', 'Courriel aux utilisateurs');
define('_AM_CFG_VIEW_USERS_APIKEYS', 'Clés API');
define('_AM_CFG_VIEW_USERS_TOKENS', 'Jetons de compte');

// --- Noms des vues : onglet Paramètres ---
define('_AM_CFG_VIEW_SETTINGS_ELEMENTS', 'Éléments');
define('_AM_CFG_VIEW_SETTINGS_FORMS', 'Formulaires');
define('_AM_CFG_VIEW_SETTINGS_MESSAGING', 'Messagerie');
define('_AM_CFG_VIEW_SETTINGS_AI', 'IA');
define('_AM_CFG_VIEW_SETTINGS_SYSTEM', 'Système');
define('_AM_CFG_VIEW_SETTINGS_ADVANCED', 'Avancé');
define('_AM_CFG_VIEW_SETTINGS_PERMISSIONS', 'Copier les permissions');

// --- Titres des sections : Utilisateurs > Paramètres ---
define('_AM_CFG_SEC_SIGNING_IN', 'Connexion');
define('_AM_CFG_SEC_NEW_USER_DEFAULTS', 'Paramètres par défaut des nouveaux utilisateurs');

// --- Titres des sections : Paramètres > Éléments ---
define('_AM_CFG_SEC_TEXTBOX_DEFAULTS', 'Paramètres par défaut des zones de texte');
define('_AM_CFG_SEC_NUMBER_BOX_DEFAULTS', 'Paramètres par défaut des zones numériques');
define('_AM_CFG_SEC_TIME_DEFAULTS', "Paramètres par défaut de l'heure");
define('_AM_CFG_SEC_CHECKBOX_RADIO_DEFAULTS', 'Paramètres par défaut des cases à cocher et boutons radio');

// --- Titres des sections : Paramètres > Formulaires ---
define('_AM_CFG_SEC_FORM_DISPLAY', 'Affichage du formulaire');
define('_AM_CFG_SEC_REVISION_HISTORY', 'Historique des révisions');
define('_AM_CFG_SEC_EXPORT', 'Exportation');
define('_AM_CFG_SEC_LISTS', 'Listes');

// --- Titres des sections : Paramètres > Messagerie ---
define('_AM_CFG_SEC_EMAIL_DELIVERY', 'Envoi des courriels');
define('_AM_CFG_SEC_TEXT_MESSAGES', 'Messages texte (SMS)');
define('_AM_CFG_SEC_NOTIFICATIONS', 'Notifications');

// --- Titres des sections : Paramètres > IA ---
define('_AM_CFG_SEC_AI', 'IA');

// --- Titres des sections : Paramètres > Système ---
define('_AM_CFG_SEC_IDENTITY', 'Identité');
define('_AM_CFG_SEC_CUSTOM_URLS', 'URL personnalisées');
define('_AM_CFG_SEC_LOGGING', 'Journalisation');
define('_AM_CFG_SEC_DATABASE', 'Base de données');
define('_AM_CFG_SEC_DATE_TIME_FORMATS', "Formats de date et d'heure");
define('_AM_CFG_SEC_SEO', 'Moteurs de recherche (SEO)');
define('_AM_CFG_SEC_APPEARANCE', 'Apparence');
define('_AM_CFG_SEC_AVAILABILITY', 'Disponibilité');

// --- Titres des sections : Paramètres > Avancé ---
define('_AM_CFG_SEC_PUBLIC_API', 'API publique');
define('_AM_CFG_SEC_SESSIONS_COOKIES', 'Sessions et cookies');
define('_AM_CFG_SEC_DEBUGGING', 'Débogage');
define('_AM_CFG_SEC_BASEMENT', 'Réglages avancés (ne touchez pas sauf nécessité absolue)');

// --- Remplacements de légendes ---
define('_AM_CFG_CAP_FROMUID', 'Expéditeur des messages privés');
define('_AM_CFG_CAP_SERVER_TZ', 'Fuseau horaire du serveur de base de données');

// --- Remplacements de descriptions ---
define('_AM_CFG_DESC_SERVER_TZ', 'Il s\'agit du fuseau horaire que renverrait <i>SELECT @@global.time_zone;</i> dans MariaDB');
define('_AM_CFG_DESC_DATESTRING', "Utilisé pour l'affichage de la date et de l'heure dans tout Formulize.");
define('_AM_CFG_DESC_SHORTDATESTRING', "Utilisé pour l'affichage abrégé de la date.");
define('_AM_CFG_DESC_SHORTTIMESTRING', "Utilisé pour l'affichage abrégé de l'heure.");
define('_AM_CFG_DESC_FOOTER', 'Contenu du pied de page de toutes les pages, si votre thème le supporte. Le HTML est autorisé.');
define('_AM_CFG_DESC_FOOTADM', "Contenu du pied de page de toutes les pages d'administration, si votre thème le supporte. Le HTML est autorisé.");
define('_AM_CFG_DESC_SESSION_NAME', 'Le nom du cookie de session');

// --- Aide de section HTML : référence des codes de format date/heure ---
define('_AM_CFG_HELP_DATE_TIME_FORMATS', "<details class='formulize-config-help'><summary>Afficher les codes de format de date/heure</summary><div class='formulize-config-help-codes'><b>Année :</b> Y=2026, y=26<br><b>Mois :</b> m=06, n=6, M=Jun, F=June<br><b>Jour :</b> d=05, j=5, D=Jeu, l=Jeudi<br><b>Heure :</b> H=14, G=14 (sans zéro initial), h=02, g=2 (12 heures)<br><b>Minutes :</b> i=05 &nbsp;&nbsp; <b>Secondes :</b> s=09<br><b>AM/PM :</b> a=pm, A=PM</div></details>");
