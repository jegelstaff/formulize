<?php
// $Id: preferences.php 22004 2011-07-09 20:22:27Z juancj $
//%%%%%%	Admin Module Name  AdminGroup 	%%%%%
// dont change
if (!defined('_AM_DBUPDATED')) {define("_AM_DBUPDATED","Database Updated Successfully!");}

define("_MD_AM_SITEPREF","Site Preferences");
define("_MD_AM_SITENAME","Site name");
define("_MD_AM_SLOGAN","Slogan for your site");
define("_MD_AM_ADMINML","Admin mail address");
define('_MD_AM_ADMINMLDSC','All informations will be send by this E-mail address. We recomend an address from your Web-Domain.');
define("_MD_AM_LANGUAGE","Default language");
define("_MD_AM_LANGUAGEDSC","Select your main language. If you activated the multilanguage, you can choice a language. And if you set in the multilanguage the language of your browser, than ImpressCMS will ignore this option.");
define("_MD_AM_STARTPAGE","Module or Page for your start page");
define("_MD_AM_NONE","None");
define("_MD_CONTENTMAN","Content Manager");
define("_MD_AM_SERVERTZ","Server timezone");
define("_MD_AM_DEFAULTTZ","Default timezone");
define("_MD_AM_DTHEME","Default theme");
define("_MD_AM_THEMESET","Theme Set");
define("_MD_AM_ANONNAME","Username for anonymous users");
define("_MD_AM_MINPASS","Minimum length of password required");
define("_MD_AM_NEWUNOTIFY","Notify by mail when a new user is registered?");
define("_MD_AM_SELFDELETE","Allow users to delete own account?");
define("_MD_AM_SELFDELETEDSC","If you select YES, your users can find out a new button in the account with which the account can be deleted.");
define("_MD_AM_LOADINGIMG","Display loading... image?");
define("_MD_AM_USEGZIP","Use gzip compression?");
define("_MD_AM_UNAMELVL","Select the level of strictness for username filtering");
define("_MD_AM_STRICT","Strict (only alphabets and numbers)");
define("_MD_AM_MEDIUM","Medium");
define("_MD_AM_LIGHT","Light (recommended for multi-byte chars)");
define("_MD_AM_USERCOOKIE","Name for user cookies.");
define("_MD_AM_USERCOOKIEDSC","This cookie contains only a user name and is saved in a user pc for a year (if the user wishes). If a user has this cookie, username will be automatically inserted in the login box.");
define("_MD_AM_USEMYSESS","Use custom session");
define("_MD_AM_USEMYSESSDSC","Select yes to customise session related values.");
define("_MD_AM_SESSNAME","Session name");
define("_MD_AM_SESSNAMEDSC","The name of session (Valid only when 'use custom session' is enabled)");
define("_MD_AM_SESSEXPIRE","Session expiration");
define("_MD_AM_SESSEXPIREDSC","Maximum duration of session idle time in minutes (Valid only when 'use custom session' is enabled. Works only when you are using PHP4.2.0 or later.)");
define("_MD_AM_BANNERS","Activate banner ads?");
define("_MD_AM_MYIP","Your IP address");
define("_MD_AM_MYIPDSC","This IP will not count as an impression for banners");
define("_MD_AM_ALWDHTML","HTML tags allowed in all posts.");
define("_MD_AM_INVLDMINPASS","Invalid value for minimum length of password.");
define("_MD_AM_INVLDUCOOK","Invalid value for usercookie name.");
define("_MD_AM_INVLDSCOOK","Invalid value for sessioncookie name.");
define("_MD_AM_INVLDSEXP","Invalid value for session expiration time.");
define("_MD_AM_ADMNOTSET","Admin mail is not set.");
define("_MD_AM_YES","Yes");
define("_MD_AM_NO","No");
define("_MD_AM_DONTCHNG","Don't change!");
define("_MD_AM_REMEMBER","Remember to chmod 666 this file in order to let the system write to it properly.");
define("_MD_AM_IFUCANT","If you can't change the permissions you can edit the rest of this file by hand.");

define("_MD_AM_COMMODE","Default Comment Display Mode");
define("_MD_AM_COMORDER","Default Comments Display Order");
define("_MD_AM_ALLOWHTML","Allow HTML tags in user comments?");
define("_MD_AM_DEBUGMODE","Developer Dashboard");
define("_MD_AM_DEBUGMODEDSC","Several debug options. A running website should have this turned off.");
define("_MD_AM_AVATARALLOW","Allow custom avatar upload?");
define("_MD_AM_AVATARALLOWDSC","If you allow this option, you can set more option for the avatars (with, height, size).");
define('_MD_AM_AVATARMP','Minimum posts required');
define('_MD_AM_AVATARMPDSC','Enter the minimum number of posts required to upload a custom avatar');
define("_MD_AM_AVATARW","Avatar image max width (pixel)");
define("_MD_AM_AVATARH","Avatar image max height (pixel)");
define("_MD_AM_AVATARMAX","Avatar image max filesize (byte)");
define("_MD_AM_AVATARCONF","Custom avatar settings");
define("_MD_AM_CHNGUTHEME","Change all users' theme");
define("_MD_AM_NOTIFYTO","Select group to which new user notification mail will be sent");
define("_MD_AM_ALLOWTHEME","Allow users to select theme?");
define("_MD_AM_ALLOWIMAGE","Allow users to display image files in posts?");

define("_MD_AM_USERACTV","Requires activation by user (recommended)");
define("_MD_AM_AUTOACTV","Activate automatically");
define("_MD_AM_ADMINACTV","Activation by administrators");
define("_MD_AM_REGINVITE","Registration by invitation");
define("_MD_AM_ACTVTYPE","Select activation type of newly registered users");
define("_MD_AM_ACTVGROUP","Select group to which activation mail will be sent");
define("_MD_AM_ACTVGROUPDSC","Valid only when 'Activation by administrators' is selected");
define('_MD_AM_USESSL', 'Use SSL for login?');
define('_MD_AM_USESSLDSC', 'Select YES only if you have a SSL certificate. If you like to use this option, please copy the right files from your downloaded ImpressCMS EXTRA folder in your root-path.');
define('_MD_AM_SSLPOST', 'SSL Post variable name');
define('_MD_AM_SSLPOSTDSC', 'The name of variable used to transfer session value via POST. If you are unsure, set any name that is hard to guess.');
define('_MD_AM_DEBUGMODE0','Off');
define('_MD_AM_DEBUGMODE1','Enable debug (inline mode)');
define('_MD_AM_DEBUGMODE2','Enable debug (popup mode)');
define('_MD_AM_DEBUGMODE3','Smarty Templates Debug');
define('_MD_AM_MINUNAME', 'Minimum length of username required');
define('_MD_AM_MAXUNAME', 'Maximum length of username');
define('_MD_AM_GENERAL', 'General Settings');
define('_MD_AM_USERSETTINGS', 'User Settings');
define('_MD_AM_ALLWCHGMAIL', 'Allow users to change email address?');
define('_MD_AM_ALLWCHGMAILDSC', '');
define('_MD_AM_IPBAN', 'IP Banning');
define('_MD_AM_BADEMAILS', 'Enter emails that should not be used in user profile');
define('_MD_AM_BADEMAILSDSC', 'Separate each with a <b>|</b>, case insensitive, regex enabled.');
define('_MD_AM_BADUNAMES', 'Enter names that should not be selected as username');
define('_MD_AM_BADUNAMESDSC', 'Separate each with a <b>|</b>, case insensitive, regex enabled.');
define('_MD_AM_DOBADIPS', 'Enable IP bans?');
define('_MD_AM_DOBADIPSDSC', 'Users from specified IP addresses will not be able to view your site');
define('_MD_AM_BADIPS', 'Enter IP addresses that should be banned from the site.<br />Separate each with a <b>|</b>, case insensitive, regex enabled.');
define('_MD_AM_BADIPSDSC', '^aaa.bbb.ccc will disallow visitors with an IP that starts with aaa.bbb.ccc<br />aaa.bbb.ccc$ will disallow visitors with an IP that ends with aaa.bbb.ccc<br />aaa.bbb.ccc will disallow visitors with an IP that contains aaa.bbb.ccc');
define('_MD_AM_PREFMAIN', 'Preferences Main');
define('_MD_AM_METAKEY', 'Meta Keywords');
define('_MD_AM_METAKEYDSC', 'The keywords meta tag is a series of keywords that represents the content of your site. Type in keywords with each separated by a comma or a space in between. (Ex. ImpressCMS, PHP, mySQL, portal system)');
define('_MD_AM_METARATING', 'Meta Rating');
define('_MD_AM_METARATINGDSC', 'The rating meta tag defines your site age and content rating');
define('_MD_AM_METAOGEN', 'General');
define('_MD_AM_METAO14YRS', '14 years');
define('_MD_AM_METAOREST', 'Restricted');
define('_MD_AM_METAOMAT', 'Mature');
define('_MD_AM_METAROBOTS', 'Meta Robots');
define('_MD_AM_METAROBOTSDSC', 'The Robots Tag declares to search engines what content to index and spider');
define('_MD_AM_INDEXFOLLOW', 'Index, Follow');
define('_MD_AM_NOINDEXFOLLOW', 'No Index, Follow');
define('_MD_AM_INDEXNOFOLLOW', 'Index, No Follow');
define('_MD_AM_NOINDEXNOFOLLOW', 'No Index, No Follow');
define('_MD_AM_METAAUTHOR', 'Meta Author');
define('_MD_AM_METAAUTHORDSC', 'The author meta tag defines the name of the author of the document being read. Supported data formats include the name, email address of the webmaster, company name or URL.');
define('_MD_AM_METACOPYR', 'Meta Copyright');
define('_MD_AM_METACOPYRDSC', 'The copyright meta tag defines any copyright statements you wish to disclose about your web page documents.');
define('_MD_AM_METADESC', 'Meta Description');
define('_MD_AM_METADESCDSC', 'The description meta tag is a general description of what is contained in your web page');
define('_MD_AM_METAFOOTER', 'Meta + Footer');
define('_MD_AM_FOOTER', 'Footer');
define('_MD_AM_FOOTERDSC', 'Be sure to type links in full path starting from http://, otherwise the links will not work correctly in modules pages.');
define('_MD_AM_CENSOR', 'Word Censoring');
define('_MD_AM_DOCENSOR', 'Enable censoring of unwanted words?');
define('_MD_AM_DOCENSORDSC', 'Words will be censored if this option is enabled. This option may be turned off for enhanced site speed.');
define('_MD_AM_CENSORWRD', 'Words to censor');
define('_MD_AM_CENSORWRDDSC', 'Enter words that should be censored in user posts.<br />Separate each with a <b>|</b>, case insensitive.');
define('_MD_AM_CENSORRPLC', 'Bad words will be replaced with:');
define('_MD_AM_CENSORRPLCDSC', 'Censored words will be replaced with the characters entered in this textbox');

define('_MD_AM_SEARCH', 'Search Options');
define('_MD_AM_DOSEARCH', 'Enable global searches?');
define('_MD_AM_DOSEARCHDSC', 'Allow searching for posts/items within your site.');
define('_MD_AM_MINSEARCH', 'Minimum keyword length');
define('_MD_AM_MINSEARCHDSC', 'Enter the minimum keyword length that users are required to enter to perform search');
define('_MD_AM_MODCONFIG', 'Module Config Options');
define('_MD_AM_DSPDSCLMR', 'Display disclaimer?');
define('_MD_AM_DSPDSCLMRDSC', 'Select yes to display disclaimer in registration page');
define('_MD_AM_REGDSCLMR', 'Registration disclaimer');
define('_MD_AM_REGDSCLMRDSC', 'Enter text to be displayed as registration disclaimer');
define('_MD_AM_ALLOWREG', 'Allow new user registration?');
define('_MD_AM_ALLOWREGDSC', 'Select yes to accept new user registration');
define('_MD_AM_THEMEFILE', 'Check templates for modifications ?');
define('_MD_AM_THEMEFILEDSC', 'If this option is enabled, modified templates will be automatically recompiled when they are displayed. You must turn this option off on a production site.');
define('_MD_AM_CLOSESITE', 'Turn your site off?');
define('_MD_AM_CLOSESITEDSC', 'Select yes to turn your site off so that only users in selected groups have access to the site. ');
define('_MD_AM_CLOSESITEOK', 'Select groups that are allowed to access while the site is turned off.');
define('_MD_AM_CLOSESITEOKDSC', 'Users in the default webmasters group are always granted access.');
define('_MD_AM_CLOSESITETXT', 'Reason for turning off the site');
define('_MD_AM_CLOSESITETXTDSC', 'The text that is presented when the site is closed.');
define('_MD_AM_SITECACHE', 'Site-wide Cache');
define('_MD_AM_SITECACHEDSC', 'Caches whole contents of the site for a specified amount of time to enhance performance. Setting site-wide cache will override module-level cache, block-level cache, and module item level cache if any.');
define('_MD_AM_MODCACHE', 'Module-wide Cache');
define('_MD_AM_MODCACHEDSC', 'Caches module contents for a specified amount of time to enhance performance. Setting module-wide cache will override module item level cache if any.');
define('_MD_AM_NOMODULE', 'There is no module that can be cached.');
define('_MD_AM_DTPLSET', 'Default template set');
define('_MD_AM_DTPLSETDSC', 'If you like to select an other Template-Set as a default, you must create first a new clone in your system. After them you can set this clone as default.');
define('_MD_AM_SSLLINK', 'URL where SSL login page is located');

// added for mailer
define("_MD_AM_MAILER","Mail Setup");
define("_MD_AM_MAILER_MAIL","");
define("_MD_AM_MAILER_SENDMAIL","");
define("_MD_AM_MAILER_","");
define("_MD_AM_MAILFROM","FROM address");
define("_MD_AM_MAILFROMDESC","");
define("_MD_AM_MAILFROMNAME","FROM name");
define("_MD_AM_MAILFROMNAMEDESC","");
// RMV-NOTIFY
define("_MD_AM_MAILFROMUID","FROM user");
define("_MD_AM_MAILFROMUIDDESC","When the system sends a private message, which user should appear to have sent it?");
define("_MD_AM_MAILERMETHOD","Mail delivery method");
define("_MD_AM_MAILERMETHODDESC","Method used to deliver mail. Default is \"mail\", use others only if that makes trouble.");
define("_MD_AM_SMTPHOST","SMTP host(s)");
define("_MD_AM_SMTPHOSTDESC","List of SMTP servers to try to connect to.");
define("_MD_AM_SMTPUSER","SMTPAuth username");
define("_MD_AM_SMTPUSERDESC","Username to connect to an SMTP host with SMTPAuth.");
define("_MD_AM_SMTPPASS","SMTPAuth password");
define("_MD_AM_SMTPPASSDESC","Password to connect to an SMTP host with SMTPAuth.");
define("_MD_AM_SENDMAILPATH","Path to sendmail");
define("_MD_AM_SENDMAILPATHDESC","Path to the sendmail program (or substitute) on the webserver.");
define("_MD_AM_THEMEOK","Selectable themes");
define("_MD_AM_THEMEOKDSC","Choose themes that users can select as the default theme");

// Xoops Authentication constants
define("_MD_AM_AUTH_CONFOPTION_XOOPS", "ImpressCMS Database");
define("_MD_AM_AUTH_CONFOPTION_LDAP", "Standard LDAP Directory");
define("_MD_AM_AUTH_CONFOPTION_AD", "Microsoft Active Directory &copy");
define("_MD_AM_AUTHENTICATION", "Authentication");
define("_MD_AM_AUTHMETHOD", "Authentication Method");
define("_MD_AM_AUTHMETHODDESC", "Which authentication method would you like to use for signing on users.");
define("_MD_AM_LDAP_MAIL_ATTR", "LDAP - Mail Field Name");
define("_MD_AM_LDAP_MAIL_ATTR_DESC","The name of the E-Mail attribute in your LDAP directory tree.");
define("_MD_AM_LDAP_NAME_ATTR","LDAP - Common Name Field Name");
define("_MD_AM_LDAP_NAME_ATTR_DESC","The name of the Common Name attribute in your LDAP directory.");
define("_MD_AM_LDAP_SURNAME_ATTR","LDAP - Surname Field Name");
define("_MD_AM_LDAP_SURNAME_ATTR_DESC","The name of the Surname attribute in your LDAP directory.");
define("_MD_AM_LDAP_GIVENNAME_ATTR","LDAP - Given Name Field Name");
define("_MD_AM_LDAP_GIVENNAME_ATTR_DSC","The name of the Given Name attribute in your LDAP directory.");
define("_MD_AM_LDAP_BASE_DN", "LDAP - Base DN");
define("_MD_AM_LDAP_BASE_DN_DESC", "The base DN (Distinguished Name) of your LDAP directory tree.");
define("_MD_AM_LDAP_PORT","LDAP - Port Number");
define("_MD_AM_LDAP_PORT_DESC","The port number needed to access your LDAP directory server.");
define("_MD_AM_LDAP_SERVER","LDAP - Server Name");
define("_MD_AM_LDAP_SERVER_DESC","The name of your LDAP directory server.");

define("_MD_AM_LDAP_MANAGER_DN", "DN of the LDAP manager");
define("_MD_AM_LDAP_MANAGER_DN_DESC", "The DN of the user allow to make search (eg manager)");
define("_MD_AM_LDAP_MANAGER_PASS", "Password of the LDAP manager");
define("_MD_AM_LDAP_MANAGER_PASS_DESC", "The password of the user allow to make search");
define("_MD_AM_LDAP_VERSION", "LDAP Version protocol");
define("_MD_AM_LDAP_VERSION_DESC", "The LDAP Version protocol : 2 or 3");
define("_MD_AM_LDAP_USERS_BYPASS", " ImpressCMS User(s) bypass LDAP Authentication");
define("_MD_AM_LDAP_USERS_BYPASS_DESC", "ImpressCMS User(s) allow to bypass the LDAP login. Login directly in ImpresssCMS<br />Separate each loginname with a |");

define("_MD_AM_LDAP_USETLS", " Use TLS connection");
define("_MD_AM_LDAP_USETLS_DESC", "Use a TLS (Transport Layer Security) connection. TLS use standard 389 port number<br />" .
								  " and the LDAP version must be set to 3.");

define("_MD_AM_LDAP_LOGINLDAP_ATTR","LDAP Attribute use to search the user");
define("_MD_AM_LDAP_LOGINLDAP_ATTR_D","When Login name use in the DN option is set to yes, must correspond to the login name ImpressCMS");
define("_MD_AM_LDAP_LOGINNAME_ASDN", "Login name use in the DN");
define("_MD_AM_LDAP_LOGINNAME_ASDN_D", "The ImpressCMS login name is used in the LDAP DN (eg : uid=<loginname>,dc=impresscms,dc=org)<br />The entry is directly read in the LDAP Server without search");

define("_MD_AM_LDAP_FILTER_PERSON", "The search filter LDAP query to find user");
define("_MD_AM_LDAP_FILTER_PERSON_DESC", "Special LDAP Filter to find user. @@loginname@@ is replaced by the users's login name<br /> MUST BE BLANK IF YOU DON'T KNOW WHAT YOU DO' !" .
		"<br />Ex : (&(objectclass=person)(samaccountname=@@loginname@@)) for AD" .
		"<br />Ex : (&(objectclass=inetOrgPerson)(uid=@@loginname@@)) for LDAP");

define("_MD_AM_LDAP_DOMAIN_NAME", "The domain name");
define("_MD_AM_LDAP_DOMAIN_NAME_DESC", "Windows domain name. for ADS and NT Server only");

define("_MD_AM_LDAP_PROVIS", "Automatic ImpressCMS account provisionning");
define("_MD_AM_LDAP_PROVIS_DESC", "Create ImpressCMS user database if not exists");

define("_MD_AM_LDAP_PROVIS_GROUP", "Default affect group");
define("_MD_AM_LDAP_PROVIS_GROUP_DSC", "The new user is assigned to these groups");

define("_MD_AM_LDAP_FIELD_MAPPING_ATTR", "ImpressCMS-Auth server fields mapping");
define("_MD_AM_LDAP_FIELD_MAPPING_DESC", "Describe here the mapping between the ImpressCMS database field and the LDAP Authentication system field." .
		"<br /><br />Format [ImpressCMS Database field]=[Auth system LDAP attribute]" .
		"<br />for example : email=mail" .
		"<br />Separate each with a |" .
		"<br /><br />!! For advanced users !!");

define("_MD_AM_LDAP_PROVIS_UPD", "Maintain ImpressCMS account provisioning");
define("_MD_AM_LDAP_PROVIS_UPD_DESC", "The ImpressCMS User account is always synchronized with the Authentication Server");

//lang constants for secure password
define("_MD_AM_PASSLEVEL","Minimum security level");
define("_MD_AM_PASSLEVEL_DESC","Define which level of security you want for the user's password. It's recommeded not to set it too low or too strong, be reasonable.");
define("_MD_AM_PASSLEVEL1","Off(Insecure)");
define("_MD_AM_PASSLEVEL2","Weak");
define("_MD_AM_PASSLEVEL3","Reasonable");
define("_MD_AM_PASSLEVEL4","Strong");
define("_MD_AM_PASSLEVEL5","Secure");
define("_MD_AM_PASSLEVEL6","No classification");

define("_MD_AM_RANKW","Rank image max width (pixel)");
define("_MD_AM_RANKH","Rank image max height (pixel)");
define("_MD_AM_RANKMAX","Rank image max filesize (byte)");

define("_MD_AM_MULTILANGUAGE","Multilanguage");
define("_MD_AM_ML_ENABLE","Enable Multilanguage");
define("_MD_AM_ML_ENABLEDSC","Set to Yes in order to enable multilanguage throughout the site.");
define("_MD_AM_ML_TAGS","Multilanguage tags");
define("_MD_AM_ML_TAGSDSC","Enter the tags to be used on this site, separated by a comma. For example, this would be used to define the tages to be used for english and french : en,fr");
define("_MD_AM_ML_NAMES","Language names");
define("_MD_AM_ML_NAMESDSC","Enter the names of the language to use, separated by a comma");
define("_MD_AM_ML_CAPTIONS","Language captions");
define("_MD_AM_ML_CAPTIONSDSC","Enter the captions you would like to use for these languages");
define("_MD_AM_ML_CHARSET","Charsets");
define("_MD_AM_ML_CHARSETDSC","Enter the charsets of these languages");

define("_MD_AM_REMEMBERME","Enable the 'Remember Me' feature in the login.");
define("_MD_AM_REMEMBERMEDSC","The 'Remember Me' feature can represent a security issue. Use it under your own risk.");

define("_MD_AM_PRIVDPOLICY","Enable the sites 'Privacy Policy'.");
define("_MD_AM_PRIVDPOLICYDSC","The 'Privacy Policy' should be tailored to your site & active whenever you are allowing registrations to your site.");
define("_MD_AM_PRIVPOLICY","Enter your site 'Privacy Policy'.");
define("_MD_AM_PRIVPOLICYDSC","");

define("_MD_AM_WELCOMEMSG","Send a welcome message to newly registered user");
define("_MD_AM_WELCOMEMSGDSC","Send a welcome message to new user when their account gets activated. The content of this message can be configured in the following option.");
define("_MD_AM_WELCOMEMSG_CONTENT","Content of the welcome message");
define("_MD_AM_WELCOMEMSG_CONTENTDSC","You can edit the message that is sent to the new user. Note that you can use the following tags: <br /><br />- {UNAME} = username of the user<br />- {X_UEMAIL} = email of the user<br />- {X_ADMINMAIL} = admin email address<br />- {X_SITENAME} = name of the site<br />- {X_SITEURL} = URL of the site");

define("_MD_AM_SEARCH_USERDATE","Show user and date in search results");
define("_MD_AM_SEARCH_USERDATEDSC","");
define("_MD_AM_SEARCH_NO_RES_MOD","Show modules with no match in search results");
define("_MD_AM_SEARCH_NO_RES_MODDSC","");
define("_MD_AM_SEARCH_PER_PAGE","Item per page in search results");
define("_MD_AM_SEARCH_PER_PAGEDSC","");

define("_MD_AM_EXT_DATE","Do you want to use an extended/local date function?");
define("_MD_AM_EXT_DATEDSC","Note: by activating this option, ImpressCMS will use an extended calendar script <b>ONLY</b> if you have this script running on your site.<br />Please visit <a href='http://wiki.impresscms.org/index.php?title=Extended_date_function'>extended date function</a> for more info.");

define("_MD_AM_EDITOR_DEFAULT","Default Editor");
define("_MD_AM_EDITOR_DEFAULT_DESC","Select the default Editor for all the site.");

define("_MD_AM_EDITOR_ENABLED_LIST","Enabled Editors");
define("_MD_AM_EDITOR_ENABLED_LIST_DESC","Select the selectable editors by the modules (If the module has a configuration to select the editor.)");

define("_MD_AM_ML_AUTOSELECT_ENABLED","Autoselect the language depending the browser configuration");

define("_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE","Allow anonymous users to see user profiles.");
define("_MD_AM_ALLOW_ANONYMOUS_VIEW_PROFILE_DESC","If you select YES, all visitors can see the profiles from your homepage. This is very usefull for a community, but maybe for the privacy not the best option.");

define("_MD_AM_ENC_TYPE","Change Password Encryption (default is SHA256)");
define("_MD_AM_ENC_TYPEDSC","Changes the Algorithm used for encrypting user passwords.<br />Changing this will render all passwords invalid! all users will need to reset their passwords after changing this preference");
define("_MD_AM_ENC_MD5","MD5 (not recommended)");
define("_MD_AM_ENC_SHA256","SHA 256 (recommended)");
define("_MD_AM_ENC_SHA384","SHA 384");
define("_MD_AM_ENC_SHA512","SHA 512");
define("_MD_AM_ENC_RIPEMD128","RIPEMD 128");
define("_MD_AM_ENC_RIPEMD160","RIPEMD 160");
define("_MD_AM_ENC_WHIRLPOOL","WHIRLPOOL");
define("_MD_AM_ENC_HAVAL1284","HAVAL 128,4");
define("_MD_AM_ENC_HAVAL1604","HAVAL 160,4");
define("_MD_AM_ENC_HAVAL1924","HAVAL 192,4");
define("_MD_AM_ENC_HAVAL2244","HAVAL 224,4");
define("_MD_AM_ENC_HAVAL2564","HAVAL 256,4");
define("_MD_AM_ENC_HAVAL1285","HAVAL 128,5");
define("_MD_AM_ENC_HAVAL1605","HAVAL 160,5");
define("_MD_AM_ENC_HAVAL1925","HAVAL 192,5");
define("_MD_AM_ENC_HAVAL2245","HAVAL 224,5");
define("_MD_AM_ENC_HAVAL2565","HAVAL 256,5");

//Content Manager
define("_MD_AM_CONTMANAGER","Content Manager");
define("_MD_AM_DEFAULT_CONTPAGE","Default Page");
define("_MD_AM_DEFAULT_CONTPAGEDSC","Select the default page to be displayed to the user in Content Manager. Leave blank to have Content Manager default to the most recently created page.");
define("_MD_AM_CONT_SHOWNAV","Display navigation menu on user side?");
define("_MD_AM_CONT_SHOWNAVDSC","Select yes to display the Content Manager navigation menu.");
define("_MD_AM_CONT_SHOWSUBS","Display Related Pages?");
define("_MD_AM_CONT_SHOWSUBSDSC","Select yes to display related pages links on Content Manager pages.");
define("_MD_AM_CONT_SHOWPINFO","Show poster and published info?");
define("_MD_AM_CONT_SHOWPINFODSC","Select yes to show in the page informations about the poster and publish of the page.");
define("_MD_AM_CONT_ACTSEO","Use menu title instead the id in the url (improve seo)?");
define("_MD_AM_CONT_ACTSEODSC","Select yes to the value of menu title instead of the id in the url of the page.");
//Captcha (Security image)
define('_MD_AM_USECAPTCHA', 'Do you want to use CAPTCHA on registration form?');
define('_MD_AM_USECAPTCHADSC', 'Select yes to CAPTCHA (anti-spam) up on registration form.');
define('_MD_AM_USECAPTCHAFORM', 'Do you want to use CAPTCHA on comment forms?');
define('_MD_AM_USECAPTCHAFORMDSC', 'Select yes to add CAPTCHA (anti-spam) to the comments form, in order to avoid spamming.');
define('_MD_AM_ALLWHTSIG', 'Allow to dipslay external images and HTML in the signature?');
define('_MD_AM_ALLWHTSIGDSC', 'If some attackers post an external image using [img], he can know IPs or User-Agents of users visited your site.<br />Allowing HTML can cause Script Insertion vulnerability if malicious user change his/her signature.');
define('_MD_AM_ALLWSHOWSIG', 'Do you want to allow your users to use a signature on their profile/posts, in your site?');
define('_MD_AM_ALLWSHOWSIGDSC', 'By enabling this option, users will be able to use a personal signature which will be added (on their own choice) after their posts.');
// < personalizações > fabio - Sat Apr 28 11:55:00 BRT 2007 11:55:00
define("_MD_AM_PERSON","Personalization");
define("_MD_AM_GOOGLE_ANA","Google Analytics");
define("_MD_AM_GOOGLE_ANA_DESC","Write down the Google Analytics id-code, like: <small>_uacct = \"UA-<font color=#FF0000><b>xxxxxx-x</b></font>\"</small><br />OR<small><br />var pageTracker = _gat._getTracker( UA-\"<font color=#FF0000><b>xxxxxx-x</b></font>\");</small> (you need to write the red bold id-code).");
define("_MD_AM_LLOGOADM","Admin left logo");
define("_MD_AM_LLOGOADM_DESC"," Select an image to use in the top left corner of the admin panel. <br /><i>To select or send an image, at least one image category must be present in system > images</i> ");
define("_MD_AM_LLOGOADM_URL","Admin left logo link URL");
define("_MD_AM_LLOGOADM_ALT","Admin left logo link title");
define("_MD_AM_RLOGOADM","Admin right logo");
define("_MD_AM_RLOGOADM_DESC"," Select an image to use in the top right corner of the admin panel. <br /><i>To select or send an image, at least one image category must be present in system > images</i> ");
define("_MD_AM_RLOGOADM_URL","Admin right logo link URL");
define("_MD_AM_RLOGOADM_ALT","Admin right logo link title");
define("_MD_AM_METAGOOGLE","Google Meta Tag");
define("_MD_AM_METAGOOGLE_DESC",'Code generated by Google to confirm ownership about a site so you can see the complete error page stats. Write down the id-code, like: <small>meta name="verify-v1" content="<font color=#FF0000><b>xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</b></font>" </small><br />(you need to write the red bold id-code).<br />
Further information at <a href="http://www.google.com/webmasters/" target="_blank">http://www.google.com/webmasters</a>.');
define("_MD_AM_RSSLOCAL","Admin News feed URL");
define("_MD_AM_RSSLOCAL_DESC","URL of an RSS feed to be displayed under The ImpressCMS Project > News.");
define("_MD_AM_FOOTADM","Admin Footer");
define("_MD_AM_FOOTADM_DESC","Content to be shown at footer at the admin pages.");
define("_MD_AM_EMAILTTF","Font used in email address protection");
define("_MD_AM_EMAILTTF_DESC","Select which font will be used to generate the email address protection.<br /><i>This option only applies if 'Protect email addresses against SPAM?' is set to Yes.</i>");
define("_MD_AM_EMAILLEN","Font size used in email address protection");
define("_MD_AM_EMAILLEN_DESC","<i>This option only applies if 'Protect email addresses against SPAM?' is set to Yes.</i>");
define("_MD_AM_EMAILCOLOR","Font color used in email address protection");
define("_MD_AM_EMAILCOLOR_DESC","<i>This option only applies if 'Protect email addresses against SPAM?' is set to Yes.</i>");
define("_MD_AM_EMAILSHADOW","Shadow color used in email address protection");
define("_MD_AM_EMAILSHADOW_DESC","Choose a color for the shadow of the email address protection.Leave it blank if you don't wish to use any.<br /><i>This option only applies if 'Protect email addresses against SPAM?' is set to Yes.</i>");
define("_MD_AM_SHADOWX","X offset of shadow used in email address protection");
define("_MD_AM_SHADOWX_DESC","Type in a value (in px)) that will represent the horizontal offset of the shadow in the email protection.<br /><i>This option only applies if 'Shadow color used in email address protection' is not empty.</i>");
define("_MD_AM_SHADOWY","Y offset for shadow used in email address protection");
define("_MD_AM_SHADOWY_DESC","Type in a value (in px) that will represent the vertical offset of the shadow in the email protection.<br /><i>This option only applies if 'Shadow color used in email address protection' is not empty.</i>");
define("_MD_AM_EDITREMOVEBLOCK","Edit and Remove blocks from user side?");
define("_MD_AM_EDITREMOVEBLOCKDSC","By enabling this option, you'll see two icons on block titles with a direct access to remove or edit your block.");

define("_MD_AM_EMAILPROTECT","Protect email addresses against SPAM?");
define("_MD_AM_EMAILPROTECTDSC","Enabling this option will ensure everytime an email address is dislpayed on the site, it will be protected agains SPAM robots.<br /><i>To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.</i>");
define("_MD_AM_MULTLOGINPREVENT","Prevent multiple login from same user?");
define("_MD_AM_MULTLOGINPREVENTDSC","With this option enabled, if a user is already logged in on your site, the same username will not be  able to log another time until the first session is closed.");
define("_MD_AM_MULTLOGINMSG","Multilogin redirection message:");
define("_MD_AM_MULTLOGINMSG_DESC","Message that will be displayed to a user who tries to login with a username already loged in on the site. <br><i>This option only applies if 'Prevent multiple login from same user?' is set to Yes.</i>");
define("_MD_AM_GRAVATARALLOW","Allow using GRAVATAR?");
define("_MD_AM_GRAVATARALWDSC","Show account images from members are hosted by <a href='http://www.gravatar.com/' target='_blank'>Gravatar</a>, a free avatar service. ImpressCMS will automatically display any Gravatar-hosted image linked to the email address from the members.");

define("_MD_AM_SHOW_ICMSMENU","Show ImpressCMS Project drop down menu?");
define("_MD_AM_SHOW_ICMSMENU_DESC","Select NO to not show the drop down menu and YES to show it.");

define("_MD_AM_SHORTURL","Truncate long URLs ?");
define("_MD_AM_SHORTURLDSC","Set this option to Yes if you want all URL posted on your site to be automatically truncated to a certain number of characters. Long URLs, in a forum post for example, can often break the design...");
define("_MD_AM_URLLEN","URL maximum length");
define("_MD_AM_URLLEN_DESC","The maximum amount of characters of an URL. Extra characters will be truncated automatically.<br /><i>This option only applies if 'Truncate long URLs ?' is set to Yes.</i>");
define("_MD_AM_PRECHARS","Amount of starting characters");
define("_MD_AM_PRECHARS_DESC","How many characters should be displayed at the begining of an URL ?<br /><i>This option only applies if 'Truncate long URLs ?' is set to Yes.</i>");
define("_MD_AM_LASTCHARS","Amount of ending characters");
define("_MD_AM_LASTCHARS_DESC","How many characters should be displayed at the end of an URL ?<br /><i>This option only applies if 'Truncate long URLs ?' is set to Yes.</i>");
define("_MD_AM_SIGMAXLENGTH","Maximum amount of characters in users signatures?");
define("_MD_AM_SIGMAXLENGTHDSC","Here you can choose the length of your users signatures.<br /> any character longer than this amount will be ignored.<br /><i>Be careful, long signatures can often break the design...</i>");

define("_MD_AM_AUTHOPENID","Enable OpenID authentication");
define("_MD_AM_AUTHOPENIDDSC","Select Yes to enable OpenID authentication. This will allow users to login on the site using their OpenID account. For complete information about the OpenID Integration in ImpressCMS, please visit <a href='http://wiki.impresscms.org/index.php?title=ImpressCMS_OpenID'>our wiki</a>.");
define("_MD_AM_USE_GOOGLE_ANA"," Enable Google Analytics?");
define("_MD_AM_USE_GOOGLE_ANA_DESC","");

// added in 1.1.2
define("_MD_AM_UNABLEENCCLOSED","Database Update Failed, You can't change password encryption whilst the site is closed");

######################## Added in 1.2 ###################################
define("_MD_AM_CAPTCHA","Captcha Settings");
define("_MD_AM_CAPTCHA_MODE","Captcha mode");
define("_MD_AM_CAPTCHA_MODEDSC","Please select a type of Captcha for your website");
define("_MD_AM_CAPTCHA_SKIPMEMBER","Captcha Free Groups");
define("_MD_AM_CAPTCHA_SKIPMEMBERDSC","Select groups which are not requiring a captcha. These groups will never see the captcha field.");
define("_MD_AM_CAPTCHA_CASESENS","Case sensitive");
define("_MD_AM_CAPTCHA_CASESENSDSC","Characters in image mode are case-sensitive");
define("_MD_AM_CAPTCHA_MAXATTEMP","Maximum attempts");
define("_MD_AM_CAPTCHA_MAXATTEMPDSC","Maximum attempts for each session");
define("_MD_AM_CAPTCHA_NUMCHARS","Maximum characters?");
define("_MD_AM_CAPTCHA_NUMCHARSDSC","Maximum number of characters to be generated");
define("_MD_AM_CAPTCHA_FONTMIN","Minimum font-size");
define("_MD_AM_CAPTCHA_FONTMINDSC","");
define("_MD_AM_CAPTCHA_FONTMAX","Maximum font-size");
define("_MD_AM_CAPTCHA_FONTMAXDSC","");
define("_MD_AM_CAPTCHA_BGTYPE","Background type");
define("_MD_AM_CAPTCHA_BGTYPEDSC","Background type in image mode");
define("_MD_AM_CAPTCHA_BGNUM","Background Images");
define("_MD_AM_CAPTCHA_BGNUMDSC","Number of background images to generate");
define("_MD_AM_CAPTCHA_POLPNT","Polygon points");
define("_MD_AM_CAPTCHA_POLPNTDSC","Number of polygon points to generate");
define("_MD_AM_BAR","Bar");
define("_MD_AM_CIRCLE","Circle");
define("_MD_AM_LINE","Line");
define("_MD_AM_RECTANGLE","Rectangle");
define("_MD_AM_ELLIPSE","Ellipse");
define("_MD_AM_POLYGON","Polygon");
define("_MD_AM_RANDOM","Random");
define("_MD_AM_CAPTCHA_IMG","Image");
define("_MD_AM_CAPTCHA_TXT","Text");
define("_MD_AM_CAPTCHA_OFF","Disabled");
define("_MD_AM_CAPTCHA_SKIPCHAR","Skip characters");
define("_MD_AM_CAPTCHA_SKIPCHARDSC","This option will skip the entered characters when generating Captcha");
define('_MD_AM_PAGISTYLE','Style of the paginations links:');
define('_MD_AM_PAGISTYLE_DESC','Select the style of the paginations links.');
define('_MD_AM_ALLWCHGUNAME', 'Allow users to change Display Name?');
define('_MD_AM_ALLWCHGUNAMEDSC', '');
define("_MD_AM_JALALICAL","Use Extended Calendar with Jalali?");
define("_MD_AM_JALALICALDSC","By selecting this, you`ll have an extended calendar on forms.<br />Please be aware, this calendar may not work in some Browsers.");
define("_MD_AM_NOMAILPROTECT","None");
define("_MD_AM_GDMAILPROTECT","GD protection");
define("_MD_AM_REMAILPROTECT","re-Captcha");
define("_MD_AM_RECPRVKEY","reCaptcha private api code");
define("_MD_AM_RECPRVKEY_DESC","");
define("_MD_AM_RECPUBKEY","reCaptcha public api code");
define("_MD_AM_RECPUBKEY_DESC","");
define("_MD_AM_CONT_NUMPAGES","Number of pages on list by tag mode");
define("_MD_AM_CONT_NUMPAGESDSC","Define the number of pages to show in user side on list by tag mode.");
define("_MD_AM_CONT_TEASERLENGTH","Teaser Length");
define("_MD_AM_CONT_TEASERLENGTHDSC","Number of characters of the page teaser in list by tag mode.<br />Set to 0 to not limit.");
define("_MD_AM_STARTPAGEDSC","Select the desired Module or Page for your start page by each group.");
define("_MD_AM_DELUSRES","Removing inactive users");
define("_MD_AM_DELUSRESDSC","This Option will remove users who have registered but have not activated their accounts for X days.<br />Please enter an amount of days.");
define("_MD_AM_PLUGINS","Plugins Manager");
define("_MD_AM_SELECTSPLUGINS","Select allowed plugins to be used");
define("_MD_AM_SELECTSPLUGINS_DESC","You can hereby select which plugins are used to sanitize your texts.");
define("_MD_AM_GESHI_DEFAULT","Select plugin to be used for geshi");
define("_MD_AM_GESHI_DEFAULT_DESC","GeSHi (Generic Syntax Hilighter) is a syntax highlighter for your codes.");
define("_MD_AM_SELECTSHIGHLIGHT","Select type of highlighter for the codes");
define("_MD_AM_SELECTSHIGHLIGHT_DESC","You can hereby select which highlighter is used to highlight your codes.");
define("_MD_AM_HIGHLIGHTER_GESHI","GeSHi highlighter");
define("_MD_AM_HIGHLIGHTER_PHP","php highlighter");
define("_MD_AM_HIGHLIGHTER_OFF","Disabled");
define('_MD_AM_DODEEPSEARCH', "Enable 'deep' searching?");
define('_MD_AM_DODEEPSEARCHDSC', "Would you like your initial search results page to indicate how many hits were found in each module?  Note: turning this on can slow down the search process!");
define('_MD_AM_NUMINITSRCHRSLTS', "Number of initial search results: (for 'shallow' searching)");
define('_MD_AM_NUMINITSRCHRSLTSDSC', "'Shallow' searches are made quicker by limiting the results that are returned for each module on the initial search page.");
define('_MD_AM_NUMMDLSRCHRESULTS', "Number of search results per page:");
define('_MD_AM_NUMMDLSRCHRESULTSDSC', "This determines how many hits per page are shown after drilling down into a particular module's search results.");
define('_MD_AM_ADMIN_DTHEME', 'Admin Theme');
define('_MD_AM_ADMIN_DTHEME_DESC', '');
define('_MD_AM_CUSTOMRED', 'Use Ajaxed redirection method?');
define('_MD_AM_CUSTOMREDDSC', '');
define('_MD_AM_DTHEMEDSC','Default theme used to show your site.');

// Added in 1.2

// HTML Purifier preferences
define("_MD_AM_PURIFIER","HTMLPurifier Settings");
define("_MD_AM_PURIFIER_ENABLE","Enable HTML Purifier");
define("_MD_AM_PURIFIER_ENABLEDSC","Select 'yes' to enable the HTML Purifier filters, disabling this could leave your site vulnerable to attack if you allow your HTML content");
//HTML section
define("_MD_AM_PURIFIER_HTML_TIDYLEVEL","HTML Tidy Level");
define("_MD_AM_PURIFIER_HTML_TIDYLEVELDSC","General level of cleanliness the Tidy module should enforce.<br /><br />
None = No extra tidying should be done,<br />Light = Only fix elements that would be discarded otherwise due to lack of support in doctype,<br />
Medium = Enforce best practices,<br />Heavy = Transform all deprecated elements and attributes to standards compliant equivalents.");
define("_MD_AM_PURIFIER_NONE","None");
define("_MD_AM_PURIFIER_LIGHT","Light");
define("_MD_AM_PURIFIER_MEDIUM","Medium (recommended)");
define("_MD_AM_PURIFIER_HEAVY","Heavy");
define("_MD_AM_PURIFIER_HTML_DEFID","HTML Definition ID");
define("_MD_AM_PURIFIER_HTML_DEFIDDSC","Sets the default ID name of the purifier configuration (leave as is, unless you are creating custom configurations & that you know what you are doing");
define("_MD_AM_PURIFIER_HTML_DEFREV","HTML Definition Revision Number");
define("_MD_AM_PURIFIER_HTML_DEFREVDSC","Example: revision 3 is more up-to-date than revision 2. Thus, when this gets incremented, the cache handling is smart enough to clean up any older revisions of your definition as well as flush the cache.<br />You can leave this as is unless you know what you are doing & are editing the purifier files directly");
define("_MD_AM_PURIFIER_HTML_DOCTYPE","HTML DocType");
define("_MD_AM_PURIFIER_HTML_DOCTYPEDSC","Doctype to use during filtering. Technically speaking this is not actually a doctype (as it does not identify a corresponding DTD), but we are using this name for sake of simplicity. When non-blank, this will override any older directives like XHTML or HTML (Strict).");
define("_MD_AM_PURIFIER_HTML_ALLOWELE","Allowed Elements");
define("_MD_AM_PURIFIER_HTML_ALLOWELEDSC","Whitelist of HTML Elements that are allowed to be posted. Any elements entered here will not be filtered out of user posts. You should only allow safe html elements.");
define("_MD_AM_PURIFIER_HTML_ALLOWATTR","Allowed Attributes");
define("_MD_AM_PURIFIER_HTML_ALLOWATTRDSC","Whitelist of HTML Attributes that are allowed to be posted. Any attributes entered here will not be filtered out of user posts. You should only allow safe html attirbutes.<br /><br />Format your attributes as follows:<br />element.attribute (example: div.class) will allow you to use the class attribute with div tags. you can also use wildcards: *.class for example will allow the class attribute in all allowed elements.");
define("_MD_AM_PURIFIER_HTML_FORBIDELE","Forbidden Elements");
define("_MD_AM_PURIFIER_HTML_FORBIDELEDSC","This is the logical inverse of  HTML.Allowed Elements, and it will override that directive, or any other directive.");
define("_MD_AM_PURIFIER_HTML_FORBIDATTR","Forbidden Attributes");
define("_MD_AM_PURIFIER_HTML_FORBIDATTRDSC"," While this directive is similar to  HTML Allowed Attributes, for forwards-compatibility with XML, this attribute has a different syntax.<br />Instead of tag.attr, use tag@attr. To disallow href attributes in a tags, set this directive to a@href.<br />You can also disallow an attribute globally with attr or *@attr (either syntax is fine; the latter is provided for consistency with HTML Allowed Attributes).<br /><br />Warning: This directive complements  HTML Forbidden Elements, accordingly, check out that directive for a discussion of why you should think twice before using this directive.");
define("_MD_AM_PURIFIER_HTML_MAXIMGLENGTH","Max Image Length");
define("_MD_AM_PURIFIER_HTML_MAXIMGLENGTHDSC","This directive controls the maximum number of pixels in the width and height attributes in img tags. This is in place to prevent imagecrash attacks, disable with 0 at your own risk. ");
define("_MD_AM_PURIFIER_HTML_SAFEEMBED","Enable Safe Embed");
define("_MD_AM_PURIFIER_HTML_SAFEEMBEDDSC","Whether or not to permit embed tags in documents, with a number of extra security features added to prevent script execution. This is similar to what websites like MySpace do to embed tags. Embed is a proprietary element and will cause your website to stop validating. You probably want to enable this with HTML Safe Object. Highly experimental.");
define("_MD_AM_PURIFIER_HTML_SAFEOBJECT","Enable Safe Object");
define("_MD_AM_PURIFIER_HTML_SAFEOBJECTDSC","Whether or not to permit object tags in documents, with a number of extra security features added to prevent script execution. This is similar to what websites like MySpace do to object tags. You may also want to enable  HTML Safe Embed for maximum interoperability with Internet Explorer, although embed tags will cause your website to stop validating. Highly experimental.");
define("_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATA","Relax DTD Name Attribute Parsing");
define("_MD_AM_PURIFIER_HTML_ATTRNAMEUSECDATADSC","The W3C specification DTD defines the name attribute to be CDATA, not ID, due to limitations of DTD. In certain documents, this relaxed behavior is desired, whether it is to specify duplicate names, or to specify names that would be illegal IDs (for example, names that begin with a digit.) Set this configuration directive to yes to use the relaxed parsing rules.");
// URI Section
define("_MD_AM_PURIFIER_URI_DEFID","URI Definition ID");
define("_MD_AM_PURIFIER_URI_DEFIDDSC","Unique identifier for a custom-built URI definition. If you want to add custom URIFilters, you must specify this value. (leave as is unless you know what you are doing)");
define("_MD_AM_PURIFIER_URI_DEFREV","URI Definition Revision Number");
define("_MD_AM_PURIFIER_URI_DEFREVDSC","Example: revision 3 is more up-to-date than revision 2. Thus, when this gets incremented, the cache handling is smart enough to clean up any older revisions of your definition as well as flush the cache.<br />You can leave this as is unless you know what you are doing & are editing the purifier files directly");
define("_MD_AM_PURIFIER_URI_DISABLE","Disable all URI in user posts");
define("_MD_AM_PURIFIER_URI_DISABLEDSC","Disabling URI will prevent users from posting any links whatsoever, it is not recommended to enable this except for test purposes.<br />Default is 'No'");
define("_MD_AM_PURIFIER_URI_BLACKLIST","URI Blacklist");
define("_MD_AM_PURIFIER_URI_BLACKLISTDSC","Enter Domain names that should be filtered (removed) from user posts.");
define("_MD_AM_PURIFIER_URI_ALLOWSCHEME","Allowed URI Schemes");
define("_MD_AM_PURIFIER_URI_ALLOWSCHEMEDSC","Whitelist that defines the schemes that a URI is allowed to have. This prevents XSS attacks from using pseudo-schemes like javascript or mocha.<br />Accepted values (http, https, ftp, mailto, nntp, news)");
define("_MD_AM_PURIFIER_URI_HOST","URI Host Domain");
define("_MD_AM_PURIFIER_URI_HOSTDSC","Enter URI Host. Leave blank to disable!");
define("_MD_AM_PURIFIER_URI_BASE","URI Base Domain");
define("_MD_AM_PURIFIER_URI_BASEDSC","Enter URI Base. Leave blank to disable!");
define("_MD_AM_PURIFIER_URI_DISABLEEXT","Disable External Links");
define("_MD_AM_PURIFIER_URI_DISABLEEXTDSC","Disables links to external websites. This is a highly effective anti-spam and anti-pagerank-leech measure, but comes at a hefty price: nolinks or images outside of your domain will be allowed.<br />Non-linkified URIs will still be preserved. If you want to be able to link to subdomains or use absolute URIs, enable URI Host for your website.");
define("_MD_AM_PURIFIER_URI_DISABLEEXTRES","Disable External Resources");
define("_MD_AM_PURIFIER_URI_DISABLEEXTRESDSC","Disables the embedding of external resources, preventing users from embedding things like images from other hosts. This prevents access tracking (good for email viewers), bandwidth leeching, cross-site request forging, goatse.cx posting, and other nasties, but also results in a loss of end-user functionality (they can't directly post a pic they posted from Flickr anymore). Use it if you don't have a robust user-content moderation team. ");
define("_MD_AM_PURIFIER_URI_DISABLERES","Disable Resources");
define("_MD_AM_PURIFIER_URI_DISABLERESDSC","Disables embedding resources, essentially meaning no pictures. You can still link to them though. See  URI Disable External Resources for why this might be a good idea.");
define("_MD_AM_PURIFIER_URI_MAKEABS","URI Make Absolute");
define("_MD_AM_PURIFIER_URI_MAKEABSDSC","Converts all URIs into absolute forms. This is useful when the HTML being filtered assumes a specific base path, but will actually be viewed in a different context (and setting an alternate base URI is not possible).<br /><br />URI Base must be enabled for this directive to work.");
// Filter Section
define("_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESC","Escape Dangerous Characters in StyleBlocks");
define("_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEESCDSC","Whether or not to escape the dangerous characters <, > and &  as \3C, \3E and \26, respectively. This can be safely set to false if the contents of StyleBlocks will be placed in an external stylesheet, where there is no risk of it being interpreted as HTML.");
define("_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPE","Enter StyleBlocks Scope");
define("_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKSCOPEDSC","If you would like users to be able to define external stylesheets, but only allow them to specify CSS declarations for a specific node and prevent them from fiddling with other elements, use this directive.<br />It accepts any valid CSS selector, and will prepend this to any CSS declaration extracted from the document.<br /><br />For example, if this directive is set to #user-content and a user uses the selector a:hover, the final selector will be #user-content a:hover.<br /><br />The comma shorthand may be used; consider the above example, with #user-content, #user-content2, the final selector will be #user-content a:hover, #user-content2 a:hover.");
define("_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBE","Allowed Embedding YouTube Video");
define("_MD_AM_PURIFIER_FILTER_ENABLEYOUTUBEDSC","This directive enables YouTube video embedding in HTML Purifier. Check <a href='http://htmlpurifier.org/docs/enduser-youtube.html'>this</a> document on embedding videos for more information on what this filter does.");
define("_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLK","Extract Style Blocks?");
define("_MD_AM_PURIFIER_FILTER_EXTRACTSTYLEBLKDSC","Requires CSSTidy Plugin to be installed).<br /><br />This directive turns on the style block extraction filter, which removes style blocks from input HTML, cleans them up with CSSTidy, and places them in the StyleBlocks context variable, for further use by you, usually to be placed in an external stylesheet, or a style block in the head of your document.<br /><br />Warning: It is possible for a user to mount an imagecrash attack using this CSS. Counter-measures are difficult; it is not simply enough to limit the range of CSS lengths (using relative lengths with many nesting levels allows for large values to be attained without actually specifying them in the stylesheet), and the flexible nature of selectors makes it difficult to selectively disable lengths on image tags (HTML Purifier, however, does disable CSS width and height in inline styling). There are probably two effective counter measures: an explicit width and height set to auto in all images in your document (unlikely) or the disabling of width and height (somewhat reasonable). Whether or not these measures should be used is left to the reader.");
// Core Section
define("_MD_AM_PURIFIER_CORE_ESCINVALIDTAGS","Escape Invalid Tags");
define("_MD_AM_PURIFIER_CORE_ESCINVALIDTAGSDSC","When enabled, invalid tags will be written back to the document as plain text. Otherwise, they are silently dropped.");
define("_MD_AM_PURIFIER_CORE_ESCNONASCIICHARS","Escape Non ASCII Characters");
define("_MD_AM_PURIFIER_CORE_ESCNONASCIICHARSDSC","This directive overcomes a deficiency in %Core.Encoding by blindly converting all non-ASCII characters into decimal numeric entities before converting it to its native encoding. This means that even characters that can be expressed in the non-UTF-8 encoding will be entity-ized, which can be a real downer for encodings like Big5. It also assumes that the ASCII repetoire is available, although this is the case for almost all encodings. Anyway, use UTF-8!");
define("_MD_AM_PURIFIER_CORE_HIDDENELE","Enable HTML Hidden Elements");
define("_MD_AM_PURIFIER_CORE_HIDDENELEDSC","This directive is a lookup array of elements which should have their contents removed when they are not allowed by the HTML definition. For example, the contents of a script tag are not normally shown in a document, so if script tags are to be removed, their contents should be removed to. This is opposed to a b  tag, which defines some presentational changes but does not hide its contents.");
define("_MD_AM_PURIFIER_CORE_COLORKEYS","Colour Keywords");
define("_MD_AM_PURIFIER_CORE_COLORKEYSDSC","Lookup array of color names to six digit hexadecimal number corresponding to color, with preceding hash mark. Used when parsing colors.");
define("_MD_AM_PURIFIER_CORE_REMINVIMG","Remove Invalid Image");
define("_MD_AM_PURIFIER_CORE_REMINVIMGDSC","This directive enables pre-emptive URI checking in img tags, as the attribute validation strategy is not authorized to remove elements from the document. Default = yes");
// AutoFormat Section
define("_MD_AM_PURIFIER_AUTO_AUTOPARA","Enable Paragraph Auto Format");
define("_MD_AM_PURIFIER_AUTO_AUTOPARADSC","This directive turns on auto-paragraphing, where double newlines are converted in to paragraphs whenever possible.<br /> Auto-paragraphing:<br /><br />* Always applies to inline elements or text in the root node,<br />* Applies to inline elements or text with double newlines in nodes that allow paragraph tags,<br />* Applies to double newlines in paragraph tags.<br /></br>p tags must be allowed for this directive to take effect. We do not use br tags for paragraphing, as that is semantically incorrect.<br />To prevent auto-paragraphing as a content-producer, refrain from using double-newlines except to specify a new paragraph or in contexts where it has special meaning (whitespace usually has no meaning except in tags like pre, so this should not be difficult.) To prevent the paragraphing of inline text adjacent to block elements, wrap them in div tags (the behavior is slightly different outside of the root node.)");
define("_MD_AM_PURIFIER_AUTO_DISPLINKURI","Enable Link Display");
define("_MD_AM_PURIFIER_AUTO_DISPLINKURIDSC","This directive turns on the in-text display of URIs in <a> tags, and disables those links. For example, <a href=\"http://example.com\">example</a> becomes example (http://example.com).");
define("_MD_AM_PURIFIER_AUTO_LINKIFY","Enable Auto Linkify");
define("_MD_AM_PURIFIER_AUTO_LINKIFYDSC","This directive turns on linkification, auto-linking http, ftp and https URLs. a tags with the href attribute must be allowed. ");
define("_MD_AM_PURIFIER_AUTO_PURILINKIFY","Enable Purifier Internal Linkify");
define("_MD_AM_PURIFIER_AUTO_PURILINKIFYDSC","Internal auto-formatter that converts configuration directives in syntax %Namespace.Directive to links. a tags with the href attribute must be allowed. (Leave this as is if you are not having any problems)");
define("_MD_AM_PURIFIER_AUTO_CUSTOM","Allowed Customised AutoFormatting");
define("_MD_AM_PURIFIER_AUTO_CUSTOMDSC","This directive can be used to add custom auto-format injectors. Specify an array of injector names (class name minus the prefix) or concrete implementations. Injector class must exist. please visit <a href='www.htmlpurifier.org'>HTML Purifier Homepage</a> for more info.");
define("_MD_AM_PURIFIER_AUTO_REMOVEEMPTY","Remove Empty Elements");
define("_MD_AM_PURIFIER_AUTO_REMOVEEMPTYDSC"," When enabled, HTML Purifier will attempt to remove empty elements that contribute no semantic information to the document. The following types of nodes will be removed:<br /><br />
 * Tags with no attributes and no content, and that are not empty elements (remove \<a\>\</a\> but not \<br /\>), and<br />
 * Tags with no content, except for:<br />
   o The colgroup element, or<br />
   o Elements with the id or name attribute, when those attributes are permitted on those elements.<br /><br />
Please be very careful when using this functionality; while it may not seem that empty elements contain useful information, they can alter the layout of a document given appropriate styling. This directive is most useful when you are processing machine-generated HTML, please avoid using it on regular user HTML.<br /><br />
Elements that contain only whitespace will be treated as empty. Non-breaking spaces, however, do not count as whitespace. See 'Remove Empty Spaces' for alternate behavior.");
define("_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSP","Remove Non-Breaking Spaces");
define("_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPDSC","When enabled, HTML Purifier will treat any elements that contain only non-breaking spaces as well as regular whitespace as empty, and remove them when 'Remove Empty Elements' is enabled.<br /><br />
See 'Remove Empty Nbsp Override' for a list of elements that don't have this behavior applied to them.");
define("_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPT","Remove empty Nbsp Override");
define("_MD_AM_PURIFIER_AUTO_REMOVEEMPTYNBSPEXCEPTDSC","When enabled, this directive defines what HTML elements should not be removed if they have only a non-breaking space in them.");
// Attribute Section
define("_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGET","Allowed Frame Targets");
define("_MD_AM_PURIFIER_ATTR_ALLOWFRAMETARGETDSC","Lookup table of all allowed link frame targets. Some commonly used link targets include _blank, _self, _parent and _top. Values should be lowercase, as validation will be done in a case-sensitive manner despite W3C's recommendation. XHTML 1.0 Strict does not permit the target attribute so this directive will have no effect in that doctype. XHTML 1.1 does not enable the Target module by default, you will have to manually enable it (see the module documentation for more details.)");
define("_MD_AM_PURIFIER_ATTR_ALLOWREL","Allowed Document Relationships");
define("_MD_AM_PURIFIER_ATTR_ALLOWRELDSC","List of allowed forward document relationships in the rel attribute. Common values may be nofollow or print.<br /><br />Default = external, nofollow, external nofollow & lightbox.");
define("_MD_AM_PURIFIER_ATTR_ALLOWCLASSES","Allowed Class Values");
define("_MD_AM_PURIFIER_ATTR_ALLOWCLASSESDSC","List of allowed class values in the class attribute. Leave This empty to allow all values in the Class Attribute.");
define("_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSES","Forbidden Class Values");
define("_MD_AM_PURIFIER_ATTR_FORBIDDENCLASSESDSC","List of Forbidden class values in the class attribute. Leave This empty to allow all values in the Class Attribute.");
define("_MD_AM_PURIFIER_ATTR_DEFINVIMG","Default Invalid Image");
define("_MD_AM_PURIFIER_ATTR_DEFINVIMGDSC","This is the default image an img tag will be pointed to if it does not have a valid src attribute. In future versions, we may allow the image tag to be removed completely, but due to design issues, this is not possible right now.");
define("_MD_AM_PURIFIER_ATTR_DEFINVIMGALT","Default Invalid Image Alt Tag");
define("_MD_AM_PURIFIER_ATTR_DEFINVIMGALTDSC","This is the content of the alt tag of an invalid image if the user had not previously specified an alt attribute. It has no effect when the image is valid but there was no alt attribute present.");
define("_MD_AM_PURIFIER_ATTR_DEFIMGALT","Default Image Alt Tag");
define("_MD_AM_PURIFIER_ATTR_DEFIMGALTDSC","This is the content of the alt tag of an image if the user had not previously specified an alt attribute.<br />This applies to all images without a valid alt attribute, as opposed to Default Invalid Alt Tag, which only applies to invalid images, and overrides in the case of an invalid image.<br />Default behavior with null is to use the basename of the src tag for the alt.");
define("_MD_AM_PURIFIER_ATTR_CLASSUSECDATA","Use NMTokens or CDATA specifications");
define("_MD_AM_PURIFIER_ATTR_CLASSUSECDATADSC","If null, class will auto-detect the doctype and, if matching XHTML 1.1 or XHTML 2.0, will use the restrictive NMTOKENS specification of class. Otherwise, it will use a relaxed CDATA definition. If true, the relaxed CDATA definition is forced; if false, the NMTOKENS definition is forced. To get behavior of HTML Purifier prior to 4.0.0, set this directive to false. Some rational behind the auto-detection: in previous versions of HTML Purifier, it was assumed that the form of class was NMTOKENS, as specified by the XHTML Modularization (representing XHTML 1.1 and XHTML 2.0). The DTDs for HTML 4.01 and XHTML 1.0, however specify class as CDATA. HTML 5 effectively defines it as CDATA, but with the additional constraint that each name should be unique (this is not explicitly outlined in previous specifications).");
define("_MD_AM_PURIFIER_ATTR_ENABLEID","Allow ID Attribute?");
define("_MD_AM_PURIFIER_ATTR_ENABLEIDDSC","Allows the ID attribute in HTML. This is disabled by default due to the fact that without proper configuration user input can easily break the validation of a webpage by specifying an ID that is already on the surrounding HTML. If you don't mind throwing caution to the wind, enable this directive, but I strongly recommend you also consider blacklisting IDs you use (ID Blacklist) or prefixing all user supplied IDs (ID Prefix).");
define("_MD_AM_PURIFIER_ATTR_IDPREFIX","Set Attribute ID Prefix");
define("_MD_AM_PURIFIER_ATTR_IDPREFIXDSC","String to prefix to IDs. If you have no idea what IDs your pages may use, you may opt to simply add a prefix to all user-submitted ID attributes so that they are still usable, but will not conflict with core page IDs. Example: setting the directive to 'user_' will result in a user submitted 'foo' to become 'user_foo' Be sure to set 'Allow ID Attribute' to yes before using this.");
define("_MD_AM_PURIFIER_ATTR_IDPREFIXLOCAL","Allowed Customised AutoFormatting");
define("_MD_AM_PURIFIER_ATTR_IDPREFIXLOCALDSC","Temporary prefix for IDs used in conjunction with Attribute ID Prefix. If you need to allow multiple sets of user content on web page, you may need to have a seperate prefix that changes with each iteration. This way, seperately submitted user content displayed on the same page doesn't clobber each other. Ideal values are unique identifiers for the content it represents (i.e. the id of the row in the database). Be sure to add a seperator (like an underscore) at the end. Warning: this directive will not work unless Attribute ID Prefix is set to a non-empty value!");
define("_MD_AM_PURIFIER_ATTR_IDBLACKLIST","Attribute ID Blacklist");
define("_MD_AM_PURIFIER_ATTR_IDBLACKLISTDSC","Array of IDs not allowed in the document.");
// CSS Section
define("_MD_AM_PURIFIER_CSS_ALLOWIMPORTANT","Allow !important in CSS Styles");
define("_MD_AM_PURIFIER_CSS_ALLOWIMPORTANTDSC","This parameter determines whether or not !important cascade modifiers should be allowed in user CSS. If no, !important will stripped.");
define("_MD_AM_PURIFIER_CSS_ALLOWTRICKY","Allow Tricky CSS Styles");
define("_MD_AM_PURIFIER_CSS_ALLOWTRICKYDSC","This parameter determines whether or not to allow \"tricky\" CSS properties and values. Tricky CSS properties/values can drastically modify page layout or be used for deceptive practices but do not directly constitute a security risk. For example, display:none; is considered a tricky property that will only be allowed if this directive is set to no.");
define("_MD_AM_PURIFIER_CSS_ALLOWPROP","Allowed CSS Properties");
define("_MD_AM_PURIFIER_CSS_ALLOWPROPDSC","If HTML Purifier's style attributes set is unsatisfactory for your needs, you can overload it with your own list of tags to allow. Note that this method is subtractive: it does its job by taking away from HTML Purifier usual feature set, so you cannot add an attribute that HTML Purifier never supported in the first place.<br /><br />Warning: If another preference conflicts with the elements here, that preference will win and override.");
define("_MD_AM_PURIFIER_CSS_DEFREV","CSS Definition Revision");
define("_MD_AM_PURIFIER_CSS_DEFREVDSC","Revision identifier for your custom definition. See HTML Definition Revision for details.");
define("_MD_AM_PURIFIER_CSS_MAXIMGLEN","CSS Max Image Length");
define("_MD_AM_PURIFIER_CSS_MAXIMGLENDSC","This parameter sets the maximum allowed length on img tags, effectively the width and height properties. Only absolute units of measurement (in, pt, pc, mm, cm) and pixels (px) are allowed. This is in place to prevent imagecrash attacks, disable with null at your own risk. This directive is similar to HTML Max Image Length, and both should be concurrently edited, although there are subtle differences in the input format (the CSS max is a number with a unit).");
define("_MD_AM_PURIFIER_CSS_PROPRIETARY","Allow Safe Proprietary CSS");
define("_MD_AM_PURIFIER_CSS_PROPRIETARYDSC","Whether or not to allow safe, proprietary CSS values.");
// purifier config options
define("_MD_AM_PURIFIER_401T","HTML 4.01 Transitional");
define("_MD_AM_PURIFIER_401S","HTML 4.01 Strict");
define("_MD_AM_PURIFIER_X10T","XHTML 1.0 Transitional");
define("_MD_AM_PURIFIER_X10S","XHTML 1.0 Strict");
define("_MD_AM_PURIFIER_X11","XHTML 1.1");
define("_MD_AM_PURIFIER_WEGAME","WEGAME Movies");
define("_MD_AM_PURIFIER_VIMEO","Vimeo Movies");
define("_MD_AM_PURIFIER_LOCALMOVIE","Local Movies");
define("_MD_AM_PURIFIER_GOOGLEVID","Google Video");
define("_MD_AM_PURIFIER_LIVELEAK","LiveLeak Movies");

define("_MD_AM_UNABLECSSTIDY", "CSSTidy Plugin is not found, Please copy the make sure you have CSSTidy located in your plugins folder.");

// Autotasks
if (!defined('_MD_AM_AUTOTASKS')) {define('_MD_AM_AUTOTASKS', 'Auto Tasks');}
define("_MD_AM_AUTOTASKS_SYSTEM", "Processing system");
define("_MD_AM_AUTOTASKS_HELPER", "Helper application");
define("_MD_AM_AUTOTASKS_HELPER_PATH", "Path for helper application");

define("_MD_AM_AUTOTASKS_SYSTEMDSC", "Which task system should be used to execute tasks?");
define("_MD_AM_AUTOTASKS_HELPERDSC", "For any processing system other than 'internal', please specify a helper application. However only one application will be used, so choose carefully.");
define("_MD_AM_AUTOTASKS_HELPER_PATHDSC", "If your helper application is not located in system default path, you have to specify the path to your helper application.");
define("_MD_AM_AUTOTASKS_USER", "System user");
define("_MD_AM_AUTOTASKS_USERDSC", "System user to be used for task execution.");

//source editedit
define("_MD_AM_SRCEDITOR_DEFAULT","Default Source Code Editor");
define("_MD_AM_SRCEDITOR_DEFAULT_DESC","Select the default Editor for editing source codes.");

// added in 1.2.1
define("_MD_AM_SMTPSECURE","SMTP Secure Method");
define("_MD_AM_SMTPSECUREDESC","Authentication Method used for SMTPAuthentication. (default is ssl)");
define("_MD_AM_SMTPAUTHPORT","SMTP Port");
define("_MD_AM_SMTPAUTHPORTDESC","The Port use by your SMTP Mail server (default is 465)");

// added in 1.3
define("_MD_AM_PURIFIER_OUTPUT_FLASHCOMPAT","Enable IE Flash Compatibility");
define("_MD_AM_PURIFIER_OUTPUT_FLASHCOMPATDSC","If true, HTML Purifier will generate Internet Explorer compatibility code for all object code. This is highly recommended if you enable HTML.SafeObject.");
define("_MD_AM_PURIFIER_HTML_FLASHFULLSCRN","Allow FullScreen in Flash objects");
define("_MD_AM_PURIFIER_HTML_FLASHFULLSCRNDSC","If true, HTML Purifier will allow use of 'allowFullScreen' in embedded flash content when using HTML.SafeObject.");
define("_MD_AM_PURIFIER_CORE_NORMALNEWLINES","Normalize Newlines");
define("_MD_AM_PURIFIER_CORE_NORMALNEWLINESDSC","Whether or not to normalize newlines to the operating system default. When false, HTML Purifier will attempt to preserve mixed newline files.");
define('_MD_AM_AUTHENTICATION_DSC', 'Manage security settings related to accessibility. Settings that will effect how users accounts are handled.');
define('_MD_AM_AUTOTASKS_PREF_DSC', 'Preferences for the Auto Tasks system.');
define('_MD_AM_CAPTCHA_DSC', 'Manage the settings used by captcha throughout your site.');
define('_MD_AM_GENERAL_DSC', 'The primary settings page for basic information needed by the system.');
define('_MD_AM_PURIFIER_DSC', 'HTMLPurifier is used to protect your site against common attack methods.');
define('_MD_AM_MAILER_DSC', 'Configure how your site will handle mail.');
define('_MD_AM_METAFOOTER_DSC', 'Manage your meta information and site footer as well as your crawler options.');
define('_MD_AM_MULTILANGUAGE_DSC', 'Manage your sites Multi-language settings. Enable, and configure what languages are available and how they are triggered.');
define('_MD_AM_PERSON_DSC', 'Personalize the system with custom logos and other settings.');
define('_MD_AM_PLUGINS_DSC', 'Select which plugins are used and available to be used throughout your site.');
define('_MD_AM_SEARCH_DSC', 'Manage how the search function operates for your users.');
define('_MD_AM_USERSETTINGS_DSC', 'Manage how users register for your site. ser names length, formatting and password options.');
define('_MD_AM_CENSOR_DSC', 'Manage the language that is not permitted on your site.');
define("_MD_AM_PURIFIER_FILTER_ALLOWCUSTOM","Allow Custom Filters");
define("_MD_AM_PURIFIER_FILTER_ALLOWCUSTOMDSC","Allow Custom Filters?<br /><br />if enabled this will allow you to use custom filters located in;<br />'libraries/htmlpurifier/standalone/HTMLPurifier/Filter'");

?>