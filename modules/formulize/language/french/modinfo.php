<?php

define("_MI_formulize_ADMENU0","Gestion de formulaires");
define("_MI_formulize_ADMENU1","Menu");
define("_MI_formulize_ALL_DONE_SINGLES","Est ce que le bouton 'Tout fini' doit apparaitre en bas du formulaire lorsqu'une entrée est éditée, ou créée pour les formulaires à une entrée par utilisateur?");
define("_MI_formulize_DELIMETER","Délimitation pour les cases à cocher et les boutons radio");
define("_MI_formulize_DELIMETER_BR","Coupure de ligne");
define("_MI_formulize_DELIMETER_SPACE","Espace blanc");
define("_MI_formulize_DESC","Pour créer des formulaires complexes, et analyser les données");
define("_MI_formulize_DOWNLOADDEFAULT", "Lors de l'export des données, utiliser une astuce de compatibilité pour des versions d'Excel par défaut?");
define("_MI_formulize_DOWNLOADDEFAULT_DESC", "Lors de l'export des données, ils peuvent cocher une case sur la page de téléchargement qui ajoute un code spécial au fichier qui est nécessaire pour faire apparaitre correctement les caractères accentués dan,s certaines versions de Microsoft Excel.  Ce réglage contrôle le fait que cette case soit cochée par défaut ou non. Faites un test d'export, pour voir s'il vaut mieux pour votre installation que cette option soit activée ou non.");
define("_MI_formulize_HEADING_HELP_LINK", "Voulez vous que le lien d'aide ([?]) apparaisse en haut de chaque colonne dans la liste des entrées?");
define("_MI_formulize_HEADING_HELP_LINK_DESC", "Ce lien engendre une fenêtre de type popup qui montre les détails à propos d'une question dans le formulaire, comme le texte complet de la question, le choix des options si la question est un bouton radio, etc.");
define("_MI_formulize_LOE_limit", "Quel est le nombre maximum d'entrées à afficher dans la liste des entrées, sans confirmation de l'utilisateur pour voir toutes les entrées?");
define("_MI_formulize_LOE_limit_DESC", "Lorsqu'une sélection est très large, l'affichage de la liste des entrées peut être fastidieuse, et durer au delà de plusieurs minutes. Définissez le nombre maximum d'entrées à afficher d'un coup.  Si une sélection contient plus d'entrée que la limite, il sera demandé à l'utilisateur s'il veut tout afficher ou non.");
define("_MI_formulize_LOGPROCEDURE", "Demander les identifiants pour surveiller Procédures et paramètres?");
define("_MI_formulize_LOGPROCEDUREDESC", "Par défaut, la vérification des identifiants est désactivée.");
define("_MI_formulize_NAME","Formulize");
define("_MI_formulize_NOTIFY_DELENTRY", "Effacement d'entrée de formulaire");
define("_MI_formulize_NOTIFY_DELENTRY_CAP", "Notifiez moi quand une entrée de formulaire est effacée");
define("_MI_formulize_NOTIFY_DELENTRY_DESC", "Cette option de notification alerte les utilisateurs quand une entrée de formulaire est effacée");
define("_MI_formulize_NOTIFY_DELENTRY_MAILSUB", "Entrée de formulaire effacée");
define("_MI_formulize_NOTIFY_FORM", "Notifications de Formulaires");
define("_MI_formulize_NOTIFY_FORM_DESC", "Notifications relative au formulaire en cours");
define("_MI_formulize_NOTIFY_NEWENTRY", "Nouvelle entrée de formulaire");
define("_MI_formulize_NOTIFY_NEWENTRY_CAP", "Notifiez moi toute nouvelle entrée de formulaire");
define("_MI_formulize_NOTIFY_NEWENTRY_DESC", "cette option de notification alerte les utilisateurs quand une nouvelle entrée de formulaire est saisie");
define("_MI_formulize_NOTIFY_NEWENTRY_MAILSUB", "Nouvelle entrée dans un formulaire");
define("_MI_formulize_NOTIFY_UPENTRY", "Mise à jour d'entrée de formulaire");
define("_MI_formulize_NOTIFY_UPENTRY_CAP", "Notifiez moi quand quelqu'un met à jour cette entrée de formulaire");
define("_MI_formulize_NOTIFY_UPENTRY_DESC", "Cette option de notification alerte les utilisateurs quand une personne met à jour une entrée dans ce formulaire");
define("_MI_formulize_NOTIFY_UPENTRY_MAILSUB", "Entrée de formulaire mise à jour");
define("_MI_formulize_NUMBER_DECIMALS", "Par défaut, combien de chiffre après la virgule, donc pour les décimales, doivent être allouées aux nombres?");
define("_MI_formulize_NUMBER_DECIMALS_DESC", "Normalement, laissez cela à 0, sauf si vous souhaitez que les nombres dans tous les formulaires aient un certain nombre de places pour les décimales.");
define("_MI_formulize_NUMBER_DECIMALSEP", "Par défaut, si les décimales sont utilisées, quelle ponctuation doit les séparer du reste des nombres?");
define("_MI_formulize_NUMBER_PREFIX", "Par défaut, est ce qu'un symbole doit être montré avant les nombres?");
define("_MI_formulize_NUMBER_PREFIX_DESC", "Par exemple, si tous votre site n'utilise que des dollars dans les formulaires, alors mettez '$' ici. Dans tous les autres cas laissez blanc.");
define("_MI_formulize_NUMBER_SEP", "Par défaut, quelle ponctuation doit séparer les milliers dans les nombres?");
define("_MI_formulize_PROFILEFORM","Quel formulaire doit être utilisé comme une étape d'inscription et lorsqu'on voit ou édite les comptes d'utilisateurs? (usage du module Registration Codes requise)");
define("_MI_formulize_SEND_ADMIN","Envoyer à l'administrateur du site uniquement");
define("_MI_formulize_SEND_ADMIN_DESC","Les réglages de \"Envoyer au groupe\" seront ignorés");
define("_MI_formulize_SEND_GROUP","Envoyer au groupe");
define("_MI_formulize_SEND_METHOD","Méthode d'envoi");
define("_MI_formulize_SEND_METHOD_DESC","Note: les formulaires remplis par des utilisateurs anonymes ne peuvent pas être envoyés en messages privés.");
define("_MI_formulize_SEND_METHOD_MAIL","Email");
define("_MI_formulize_SEND_METHOD_PM","Message privé");
define("_MI_formulize_SINGLESDESC","Le bouton 'Tout fait' est utilisé pour sortir d'un formulaire sans l'enregistrer.  Si une modification est faite et que vous cliquez le bouton 'Tout fini' sans cliquer préalablement sur 'Sauvegarder', vous aurez un avertissement comme quoi toutes les données n'ont pas été sauvegardée.  Si le bouton est affiché, il n'y a aucun moyen de sauvegarder et quitter en une seule opération.  Cela peut porter à confusion.  Mettez cette option sur 'Oui' pour enlever le bouton 'Tout fini' et faire que le bouton 'Sauvegarder' permette aussi de quitter en une seule procédure.  Cettte option n'a aucune conséquence sur les formulaires ou plusieurs entrées peuvent être faite en même temps (le formulaire recharge sous forme vierge à chaque fois que le bouton 'Sauvegarder' est utilisé).");
define("_MI_formulize_TAREA_COLS","Nombre de colonnes des aires de saisie de texte par défaut");
define("_MI_formulize_TAREA_ROWS","Nombre de lignes des aires de saisies de texte par défaut");
define("_MI_formulize_TEXT_MAX","Longueur maximum des boîtes texte par défaut");
define("_MI_formulize_TEXT_WIDTH","Largeur des boîtes de texte par défaut");
define("_MI_formulize_USECACHE", "Utiliser le cache pour accélérer les Procédures?");
define("_MI_formulize_USECACHEDESC", "Par défaut, le cache est activé.");
define("_MI_formulize_USETOKEN", "Utiliser la sécurité identifiant système pour valider les soumissions de formulaires?");
define("_MI_formulize_USETOKENDESC", "Par défaut, lors d'une soumission, aucune donnée n'est sauvegardée sauf si Formulize peut valider un identifiant unique soumis avec le formulaire.  C'est une défense partielle contre les attaques de scripts croisés, permettant de s'assurer que les personnes visitant actuellement votre site peuvent soumettre un formulaire.  Dans certaines circonstances, dépendantes de Firewall ou d'autres facteurs, l'identifiant ne peut être validé même si cela devrait se produire.  Si cela vous arrive avec répétition, vous pouvez désactiver cette sécurité ici.  <b>NOTE: vous pouvez passer au dessus de cette préférence globale sur une base Screen par Screen.</b>");
define("_MI_formulizeMENU_BNAME","Menu des Formulaires");
define("_MI_formulizeMENU_DESC","Montre un menu individuel configurable dans un bloc");
define("_MI_formulizeMENU_NAME","Mon Menu");

define("_MI_formulize_EXPORTINTROCHAR","Prefix strings in .csv files with a character to smooth importing and appearance in Excel and Google?");
define("_MI_formulize_EXPORTINTROCHARDESC","Excel and Google Sheets try to be helpful and automatically interpret certain values when opening .csv files. This can damage your data. To force non-numeric values to be read as-is, Formulize can prefix them with certain characters that will trigger them to be read as plain strings by Excel and Google. However, this can cause havoc in other programs if you need plain .csv data. The default behaviour suits opening downloaded files in Excel, and using the IMPORTDATA function in Google Sheets to gather data via a makecsv.php reference.");
define("_MI_formulize_EIC_BASIC", "Prefix strings with a TAB character (for Excel), unless makecsv.php is generating the file, then use an apostrophe (for Google Sheets)");
define("_MI_formulize_EIC_ALWAYSAPOS", "Always prefix strings with an apostrophe (for Google Sheets)");
define("_MI_formulize_EIC_ALWAYSTAB", "Always prefix strings with a TAB (for Excel)");
define("_MI_formulize_EIC_PLAIN", "Never prefix strings (for programs that need clean, raw data)");

define("_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITING", "Use the old method of writing effects for custom buttons");
define("_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITINGDESC", "This should always be \"No\" unless this is an older installation that already has custom buttons that are dependent on the old method, which was based on the declaring human readable values, instead of the database values for elements.");

define("_MI_formulize_FORMULIZELOGGINGONOFF", "Record Formulize activity in a log file");
define("_MI_formulize_FORMULIZELOGGINGONOFFDESC", "If you are recording logs, you can specify the location to store them below, and the duration of logs to keep. Logs will contain information about user activity in JSON format and can be ingested by Grafana or other tools.");
define("_MI_formulize_FORMULIZELOGFILELOCATION", "Location to store Formulize log files");
define("_MI_formulize_FORMULIZELOGFILELOCATIONDESC", "Formulize generates log files that contain the history of user actions, such as logging in and saving data. You can specify the full path to the folder where the log files are stored. Logging will not function if the path is empty or not valid.");
define("_MI_formulize_formulizeLogFileStorageDurationHours", "How long should Formulize log files be kept (in hours)");
define("_MI_formulize_formulizeLogFileStorageDurationHoursDESC", "After this many hours, the log files will be deleted from the server.");

$rewriteRuleInstructions = '';
if(isset($GLOBALS['config'])) {
	global $config;
	foreach($config as $thisConfig) {
		if(is_object($thisConfig) AND $thisConfig->getVar('conf_name') == 'formulizeRewriteRulesEnabled' AND $thisConfig->getVar('conf_value') == 0) {
			$rewriteRuleInstructions = "<br><br>For alternate URLs to work, you will need to add code similar to this, to the .htaccess file at the root of your website:
			<blockquote style=\"font-weight: normal; font-family: monospace; white-space: nowrap;\">
			RewriteEngine On<br>
			RewriteCond %{REQUEST_FILENAME} !-f<br>
			RewriteCond %{REQUEST_FILENAME} !-d<br>
			RewriteCond %{REQUEST_FILENAME} !-l<br>
			RewriteRule ^(.*)$ /modules/formulize/index.php?formulizeRewriteRuleAddress=$1 [L]<br>
			</blockquote><i>If you enabled this option, but these instructions are still here, and the option is off again, then your server is not yet properly configured for alternate URLs.</i>";
			break;
		}
	}
}
define("_MI_formulize_rewriteRulesEnabled", "Enable alternate URLs for screens".$rewriteRuleInstructions);
define("_MI_formulize_rewriteRulesEnabledDESC", "When this is enabled, you can specify alternate, clean URLs for accessing screens, instead of the default /modules/formulize/index.php?sid=1 style URLs.");

define('_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY', "Show empty form elements when displaying forms in read-only mode");
define('_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY_DESC', "When form elements are rendered in read-only mode, and there is no value to display, the element is skipped by default and not shown. If you want to show all elements even empty ones when users cannot edit the entry, turn this setting on.");

define('_MI_formulize_VALIDATECODE', 'Check code blocks for syntax errors?');
define('_MI_formulize_VALIDATECODE_DESC', 'When this is turned on, then Formulize will check most places where you can enter PHP code, to make sure the code has no syntax errors. This can be time consuming and if you are an experienced developer you may prefer to turn it off. This setting will have no effect if the shell_exec command is not available to PHP on your server.');
