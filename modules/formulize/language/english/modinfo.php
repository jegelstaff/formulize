<?php

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
$config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());

// Module Info

// The name of this module
define("_MI_formulize_NAME","Formulize");

// A brief description of this module
define("_MI_formulize_DESC","Easily collect and organize your data — no code required. With Formulize, you can create web-based forms, connect them together to make unique apps, and publish the data with interactive reports. Formulize is quickly configured, and reconfigured, so it adapts as your needs change and your data grows.");

// admin/menu.php
define("_MI_formulize_ADMIN_HOME","Administration");
define("_MI_formulize_ADMENU1","Menu");

// notifications
define("_MI_formulize_NOTIFY_FORM", "Form Notifications");
define("_MI_formulize_NOTIFY_FORM_DESC", "Notifications related to the current form");
define("_MI_formulize_NOTIFY_NEWENTRY", "New Entry in a Form");
define("_MI_formulize_NOTIFY_NEWENTRY_CAP", "Notify me when someone makes a new entry in this form");
define("_MI_formulize_NOTIFY_NEWENTRY_DESC", "A notification option that alerts users when new entries are made in a form");
define("_MI_formulize_NOTIFY_NEWENTRY_MAILSUB", "New Entry in a Form");

define("_MI_formulize_NOTIFY_UPENTRY", "Updated Entry in a Form");
define("_MI_formulize_NOTIFY_UPENTRY_CAP", "Notify me when someone updates an entry in this form");
define("_MI_formulize_NOTIFY_UPENTRY_DESC", "A notification option that alerts users when entries are updated in a form");
define("_MI_formulize_NOTIFY_UPENTRY_MAILSUB", "Updated Entry in a Form");

define("_MI_formulize_NOTIFY_DELENTRY", "Entry deleted from a Form");
define("_MI_formulize_NOTIFY_DELENTRY_CAP", "Notify me when someone deletes an entry from this form");
define("_MI_formulize_NOTIFY_DELENTRY_DESC", "A notification option that alerts users when entries are deleted from a form");
define("_MI_formulize_NOTIFY_DELENTRY_MAILSUB", "Entry deleted from a Form");


//	preferences
define("_MI_formulize_PREFHEADSTART", "</span><h1>");
define("_MI_formulize_PREFHEADEND", "</h1></td><td class='even'></td></tr><tr><td class='head'><div class='xoops-form-element-caption'><span class='caption-text'>");

define("_MI_formulize_TEXT_WIDTH",_MI_formulize_PREFHEADSTART."Form Element Defaults"._MI_formulize_PREFHEADEND."Default width of text boxes");
define("_MI_formulize_TEXT_MAX","Default maximum length of text boxes");
define("_MI_formulize_TAREA_ROWS","Default rows of text areas");
define("_MI_formulize_TAREA_COLS","Default columns of text areas");
define("_MI_formulize_DELIMETER","Default delimiter for check boxes and radio buttons");
if(!defined("_MI_formulize_DELIMETER_SPACE")) { define("_MI_formulize_DELIMETER_SPACE","White space"); }
if(!defined("_MI_formulize_DELIMETER_BR")) { define("_MI_formulize_DELIMETER_BR","Line break"); }
define("_MI_formulize_SEND_METHOD","Send method");
define("_MI_formulize_SEND_METHOD_DESC","Note: Form submitted by anonymous users cannot be sent by using private message.");
define("_MI_formulize_SEND_METHOD_MAIL","Email");
define("_MI_formulize_SEND_METHOD_PM","Private message");
define("_MI_formulize_SEND_GROUP","Send to group");
define("_MI_formulize_SEND_ADMIN","Send to site admin only");
define("_MI_formulize_SEND_ADMIN_DESC","Settings of \"Send to group\" will be ignored");
define("_MI_formulize_PROFILEFORM","Which form is to be used as part of the registration process and when viewing and editing accounts? (requires use of the Registration Codes module)");

define("_MI_formulize_ALL_DONE_SINGLES","Should the 'All Done' button appear at the bottom of the form when editing an entry, and creating a new entry in a 'one-entry-per-user' form? (Deprecated - use Form Screen settings)");
define("_MI_formulize_SINGLESDESC","This option is overriden by the settings in Form screens. The 'All Done' button (Leave button) is used to leave a form without saving the information in the form.  If you have made changes to the information in a form and then you click 'All Done' without first clicking 'Save', you get a warning that your data has not been saved.  Because of the way the 'Save' button and 'All Done' button work in tandem, there is normally no way to save information and leave a form all at once.  This bothers/confuses some users.  Set this option to 'Yes' to remove the 'All Done' button and turn the behaviour of the 'Save' button to 'save-and-leave-the-form-all-at-once'.  This option does not affect situations where the user is adding multiple entries to a form (where the form reloads blank every time you click 'Save').");

define("_MI_formulize_LOE_limit", _MI_formulize_PREFHEADSTART."List Settings"._MI_formulize_PREFHEADEND."What is the maximum number of entries that should be displayed in one page of a list of entries, without confirmation from the user that they want to see all entries?");
define("_MI_formulize_LOE_limit_DESC", "If a dataset is very large, displaying a list of entries screen can take a long time, several minutes even.  Use this preference to specify the maximum number of entries that your system should try to display at once.  If a dataset contains more entries than this limit, the user will be asked if they want to load the entire dataset or not.");

define("_MI_formulize_USETOKEN", _MI_formulize_PREFHEADSTART."The Basement (don't go here unless you have to)"._MI_formulize_PREFHEADEND."Use the security token system to validate form submissions?");
define("_MI_formulize_USETOKENDESC", "By default, when a form is submitted, no data is saved unless Formulize can validate a unique token that was submitted with the form.  This is a partial defence against cross site scripting attacks, meant to ensure only people actually visiting your website can submit forms.  In some circumstances, depending on firewalls or other factors, the token cannot be validated even when it should be.  If this is happening to you repeatedly, you can turn off the token system for Formulize here.  <b>NOTE: you can override this global setting on a screen by screen basis.</b>");

define("_MI_formulize_NUMBER_DECIMALS", "By default, how many decimal places should be displayed for numbers?");
define("_MI_formulize_NUMBER_DECIMALS_DESC", "Normally, leave this as 0, unless you want every number in all forms to have a certain number of decimal places.");
define("_MI_formulize_NUMBER_PREFIX", "By default, should any symbol be shown before numbers?");
define("_MI_formulize_NUMBER_PREFIX_DESC", "For example, if your entire site only uses dollar figures in forms, then put '$' here.  Otherwise, leave it blank.");
define("_MI_formulize_NUMBER_SUFFIX", "By default, should any symbol be shown after numbers?");
define("_MI_formulize_NUMBER_SUFFIX_DESC", "For example, if your entire site only uses percentage figures in forms, then put '%' here.  Otherwise, leave it blank.");
define("_MI_formulize_NUMBER_DECIMALSEP", "By default, if decimals are used, what punctuation should separate them from the rest of the number?");
define("_MI_formulize_NUMBER_SEP", "By default, what punctuation should be used to separate thousands in numbers?");

define('_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY', _MI_formulize_PREFHEADSTART."Form Settings"._MI_formulize_PREFHEADEND."Show empty form elements when displaying them as read-only?");
define('_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY_DESC', "When form elements are rendered in read-only mode, and there is no value to display, the element is skipped by default and not shown. If you want to show all elements even empty ones when users cannot edit the entry, turn this setting on.");

define('_MI_formulize_VALIDATECODE', 'Check code blocks for syntax errors?');
define('_MI_formulize_VALIDATECODE_DESC', 'When this is turned on, then Formulize will check most places where you can enter PHP code, to make sure the code has no syntax errors. This can be time consuming and if you are an experienced developer you may prefer to turn it off. This setting will have no effect if the shell_exec command is not available to PHP on your server.');

define("_MI_formulize_HEADING_HELP_LINK", "Should the help link ([?]) and lock icons appear at the top of each column in a list of entries?");
define("_MI_formulize_HEADING_HELP_LINK_DESC", "The help link provides a popup window that shows details about the question in the form, such as the full text of the question, the choice of options if the question is a radio button, etc. The lock icon allows the user to keep a column visible on screen as they scroll to the right, like 'Freeze Panes' in Excel.");

define("_MI_formulize_USECACHE", "Use caching to speed up Procedures?");
define("_MI_formulize_USECACHEDESC", "By default, caching is on.");

define("_MI_formulize_DOWNLOADDEFAULT", _MI_formulize_PREFHEADSTART."Exporting Data"._MI_formulize_PREFHEADEND."When users are exporting data, use a compatibility trick for some versions of Excel by default?");
define("_MI_formulize_DOWNLOADDEFAULT_DESC", "When users export data, they can check a box on the download page that adds a special code to the file which is necessary to make accented characters appear properly in some versions of Microsoft Excel.  This option controls whether that checkbox is checked by default or not.  You should experiment with your installation to see if exports work best with or without this option turned on.");

define("_MI_formulize_LOGPROCEDURE", "Use logging to monitor Procedures and parameters?");
define("_MI_formulize_LOGPROCEDUREDESC", "By default, logging is off.");

define("_MI_formulize_PRINTVIEWSTYLESHEETS", "What custom stylesheets, if any, should be used in the printable versions?");
define("_MI_formulize_PRINTVIEWSTYLESHEETSDESC", "Type the URL for each stylesheet, separated by a comma. If the URL starts with http, it will be used as is. If the URL does not start with http, it will be appended to the end of the base URL for the site.");

define("_MI_formulize_DEBUGDERIVEDVALUES", "Turn on debugging mode for working with derived values?");
define("_MI_formulize_DEBUGDERIVEDVALUESDESC", "When this is on, derived values will be re-computed every time they are displayed. When this is off, derived values are computed on first display only, or when data is saved.");

define("_MI_formulize_NOTIFYBYCRON", "Send notifications via a cron job?");
define("_MI_formulize_NOTIFYBYCRONDESC", "When this is on, create a cron job that triggers '/modules/formulize/notify.php' and notifications will be sent behind the scenes. When this is off, notifications are sent as part of the pageload that generated them.");

define("_MI_formulize_ISSAVELOCKED", "Lock system for synchronization");
define("_MI_formulize_ISSAVELOCKEDDESC", "When locked, you can only change the configuration of Formulize by synchronizing with another system. This is intended for use in a live production system that is being updated by periodic synchronization with a staging system.");

define("_MI_formulize_CUSTOMSCOPE", "Use custom code for determining the scope of queries");
define("_MI_formulize_CUSTOMSCOPEDESC", "Leave this blank, unless you specifically want to override the \$scope variable used in the data extraction layer. The contents of this box will be run as PHP code, and will receive the \$scope variable, which is typically an array of group ids. You can return a set of different ids, or a string in the format 'uid = X' or 'uid = X OR uid = Y...' This is useful if you can isolate certain groups using only one or a few user ids, since then the subquery to the Entry Owner Groups table is bypassed, dramatically improving query speed in large databases.");

define("_MI_formulize_F7MENUTEMPLATE", "Use the modern, mobile friendly menu layout - compatible with the Formulize 7 Theme \"Anari\"");
define("_MI_formulize_F7MENUTEMPLATEDESC", "If you have upgraded from an older version of Formulize, this will be set to \"No\" but if/when you update the theme of your website to \"Anari\" then you should switch this to \"Yes\".");

define("_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITING", "Use the old method of writing effects for custom buttons");
define("_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITINGDESC", "This should always be \"No\" unless this is an older installation that already has custom buttons that are dependent on the old method, which was based on the declaring human readable values, instead of the database values for elements.");

define("_MI_formulize_FORMULIZELOGGINGONOFF", _MI_formulize_PREFHEADSTART."Logging"._MI_formulize_PREFHEADEND."Record Formulize activity in a log file");
define("_MI_formulize_FORMULIZELOGGINGONOFFDESC", "If you are recording logs, you can specify the location to store them below, and the duration of logs to keep. Logs will contain information about user activity in JSON format and can be ingested by Grafana or other tools.");
define("_MI_formulize_FORMULIZELOGFILELOCATION", "Location to store Formulize log files");
define("_MI_formulize_FORMULIZELOGFILELOCATIONDESC", "Formulize generates log files that contain the history of user actions, such as logging in and saving data. You can specify the full path to the folder where the log files are stored. Logging will not function if the path is empty or not valid.");
define("_MI_formulize_formulizeLogFileStorageDurationHours", "How long should Formulize log files be kept (in hours)");
define("_MI_formulize_formulizeLogFileStorageDurationHoursDESC", "After this many hours, the log files will be deleted from the server.");

$rewriteRuleInstructions = '';
foreach($formulizeConfig as $thisConfig=>$thisConfigValue) {
	if($thisConfig == 'formulizeRewriteRulesEnabled' AND $thisConfigValue == 0) {
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
define("_MI_formulize_rewriteRulesEnabled", _MI_formulize_PREFHEADSTART."Core Formulize Configuration"._MI_formulize_PREFHEADEND."Enable alternate URLs for screens".$rewriteRuleInstructions);
define("_MI_formulize_rewriteRulesEnabledDESC", "When this is enabled, you can specify alternate, clean URLs for accessing screens, instead of the default /modules/formulize/index.php?sid=1 style URLs.");

$publicAPIInstructions = '';
foreach($formulizeConfig as $thisConfig=>$thisConfigValue) {
	if($thisConfig == 'formulizePublicAPIEnabled' AND $thisConfigValue == 0) {
		$publicAPIInstructions = "<br><br>For the Public API to work, you will need to add code similar to this, to the .htaccess file at the root of your website. Make sure to put it above any rewrite rules that handle alternate URLs.
		<blockquote style=\"font-weight: normal; font-family: monospace; white-space: nowrap;\">
		RewriteEngine On<br>
		RewriteCond %{REQUEST_URI} ^/formulize-public-api/ [NC]
		RewriteCond %{REQUEST_FILENAME} !-f<br>
		RewriteCond %{REQUEST_FILENAME} !-d<br>
		RewriteCond %{REQUEST_FILENAME} !-l<br>
		RewriteRule ^(.*)$ /modules/formulize/public_api/index.php?apiPath=$1 [L]<br>
		</blockquote><i>If you enabled this option, but these instructions are still here, and the option is off again, then your server is not yet properly configured for the Public API.</i>";
		break;
	}
}
define("_MI_formulize_PUBLICAPIENABLED", "Enable the Public API".$publicAPIInstructions);
define("_MI_formulize_PUBLICAPIENABLED_DESC", "When this is enabled, you can use the Public API documented at https://formulize.org/developers/public-api/");

$mcpServerInstructions = '';
$mcpDocumentationLink = "Read more about Formulize and AI at <a href='https://formulize.org/ai' target='_blank'>https://formulize.org/ai</a>.";
$hideSystemSpecificInstructions = '';
foreach($formulizeConfig as $thisConfig=>$thisConfigValue) {
	if($thisConfig == 'formulizeMCPServerEnabled' AND $thisConfigValue == 0) {
		$mcpServerInstructions = "<br><br>To work with AI, your server needs to pass through an authorization header to PHP. On some servers, you will need to add this code to the .htaccess file at the root of your website. Make sure to put it after any other rewrite rules.
		<blockquote style=\"font-weight: normal; font-family: monospace; white-space: nowrap;\">
		# Necessary for HTTP Authorization header to be passed through to the MCP server<br>
		RewriteEngine On<br>
		RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]<br>
		</blockquote><i>If you enabled this option, but these instructions are still here, then your web server is not yet properly configured for MCP. Check your .htaccess file and try again.</i><br><br>";
		$hideSystemSpecificInstructions = "<script>jQuery(window).load(function() { jQuery(\"span:contains('System Specific Instructions for the AI Assistant')\").closest('tr').hide(); } );</script>";
		break;
	} else {
		$mcpExampleConfigFilename = FormulizeObject::sanitize_handle_name(str_replace('.', '_', $_SERVER['HTTP_HOST']))."_mcp_example_config.json";
		$mcpServerInstructions = "<br><br><style>
			#xo-canvas-content ul.mcp-bullets > li { margin-bottom: 0.6em; font-weight: normal; list-style: disc;}
			#xo-canvas-content ol.mcp-steps > li { margin-bottom: 0.6em; font-weight: normal; list-style: number; }
		</style>
		Next steps:
		<ol class='mcp-steps'>
		<li><b>Create an API Key</b> &mdash; Go to <a href='".XOOPS_URL."/modules/formulize/admin/ui.php?page=managekeys' target='_blank'>the <i>Manage API Keys</i> page</a>, and create an API key for the user(s) that will be using AI with Formulize.</li>
		<li><b>Share the API key <i>securely</i></b> &mdash; Use a secure communication channel to distribute the API keys, or meet in person. The API keys give access to Formulize in exactly the same way as logging in with someone's username and password, so <b>do not send them via e-mail</b> or other insecure means!</li>
		<li><b>Connect an AI assistant</b> &mdash; Use these files to connect an MCP-compatible AI assistant to Formulize:
			<ul class='mcp-bullets'>
				<li><b>DXT Extension</b> &mdash; <a href='https://github.com/jegelstaff/formulize-mcp/releases/download/v1.3.3/formulize-mcp.dxt' download='formulize-mcp.dxt'>formulize-mcp.dxt</a> &mdash; download this file and install it in an AI assistant that supports DXT extensions, such as <a href='https://claude.ai/download' target='_blank'>Claude Desktop</a>.</li>
				<li style='list-style: none;'><b>or</b></li>
				<li><b>Manual configuration</b> &mdash; <a href='".XOOPS_URL."/mcp/example_config.php' download='$mcpExampleConfigFilename'>$mcpExampleConfigFilename</a> &mdash; download this file and save it/modify it, in the location where your AI assistant looks for MCP configuration details.</li>
			</ul>
		</ol>";
	}
}
define("_MI_formulize_MCPSERVERENABLED", "Enable AI integration via MCP".$mcpServerInstructions.$mcpDocumentationLink);
define("_MI_formulize_MCPSERVERENABLED_DESC", "MCP (Model Context Protocol) is a way of connecting AI assistants, like Claude, Copilot, etc, to Formulize. With MCP, AI assistants can read information from Formulize and help you configure Formulize.");

define("_MI_formulize_SYSTEM_SPECIFIC_INSTRUCTIONS_DESC", "Examples:<ul class='mcp-bullets'><li><b>HR System:</b> This system manages employee records, time tracking, and performance reviews. Managers have access to see all their employees' records.</li><li><b>Research Lab:</b> Scientists use this system to track experiments, log results, and manage equipment reservations. Reports are automatically generated based on the logged data.</li><li><b>Event Management:</b> This system handles event registrations, venue bookings, and attendee communications. Regular users see only their own events, admins see all events.</li><li><b>Project Management:</b> Teams use this system to track project milestones, resource allocation, and client communications. Notifications go out regularly about deadlines, new tasks, etc.</li><li><b>Student Management:</b> This Formulize system is used for managing student registrations and course enrollments. Forms are used to collect student information, course preferences, and payment details. The system is integrated with a payment gateway for processing fees.</li></ul><b>Note:</b> You can use Markdown formatting in this field to make it easier to read.");
define("_MI_formulize_SYSTEM_SPECIFIC_INSTRUCTIONS", "System Specific Instructions for the AI Assistant<br><br>You can provide specific context to the AI assistant about what your Formulize system is used for and how it is configured. Basic concepts like forms, elements, screens, users, groups, etc, have already been explained to the AI assistant. This is your chance to provide more specific context about the purpose and workflows of your system, to help the AI assistant help you better.<br><br><a style='cursor: pointer;' 'href='' onclick='jQuery(\"#mcp-ssi-examples\").toggle(); return false;'>Show/Hide Examples</a><br><br><div id='mcp-ssi-examples' style='display:none;'>"._MI_formulize_SYSTEM_SPECIFIC_INSTRUCTIONS_DESC."</div>".$hideSystemSpecificInstructions);

define("_MI_formulize_REVISIONSFORALLFORMS", "Turn on revision history for all forms");
define("_MI_formulize_REVISIONSFORALLFORMS_DESC", "Normally, you can turn on revision history for each form as you see fit. If you want to turn it on for all forms always, turn this preference on, and the option will be disabled in each form's settings.");


// The name of this module
define("_MI_formulizeMENU_NAME","MyMenu");

// A brief description of this module
define("_MI_formulizeMENU_DESC","Displays an individually configurable menu in a block");

// Names of blocks for this module (Not all module has blocks)
define("_MI_formulizeMENU_BNAME","Form Menu");

define("_MI_formulize_EXPORTINTROCHAR","Prefix strings in .csv files with a TAB (for Excel) or an apostrophe (for Google Sheets)? Prefixing helps smooth the importing and appearance in Excel or Google Sheets.");
define("_MI_formulize_EXPORTINTROCHARDESC","Excel and Google Sheets try to be helpful and automatically interpret certain values when opening .csv files. This can damage your data. To force non-numeric values to be read as-is, Formulize can prefix them with certain characters that will trigger them to be read as plain strings by Excel and Google. However, this can cause havoc in other programs if you need plain .csv data. The default behaviour suits opening downloaded files in Excel, and using the IMPORTDATA function in Google Sheets to gather data via a makecsv.php reference.");
define("_MI_formulize_EIC_BASIC", "Normally, use TAB (Excel). With makecsv.php, use apostrophe (for Google Sheets)");
define("_MI_formulize_EIC_ALWAYSAPOS", "Always prefix with an apostrophe (for Google Sheets)");
define("_MI_formulize_EIC_ALWAYSTAB", "Always prefix with a TAB (for Excel)");
define("_MI_formulize_EIC_PLAIN", "Never prefix (for programs that need clean, raw data)");

define('_MI_formulize_SHOWPRINTABLEVIEWBUTTONS', _MI_formulize_PREFHEADSTART."Printable Version Buttons"._MI_formulize_PREFHEADEND.'Enable Printable Version buttons (then you can turn them on and off per screen)');
define('_MI_formulize_SHOWPRINTABLEVIEWBUTTONS_DESC', 'If this is on, then the Printable Version buttons are available on all form screens and can be turned on and off in the usual way through the screen settings. If this is off, Printable Version buttons will not show up on any screens.');

define('_MI_formulize_EMAIL_USERS', 'Email Users');
define('_MI_formulize_MANAGE_API_KEYS', 'Manage API keys');
define('_MI_formulize_IMPORT_EXPORT', 'Import/Export Forms and Apps');
define('_MI_formulize_COPY_GROUP_PERMS', 'Copy Group Permissions');
define('_MI_formulize_SYNCHRONIZE', 'Synchronize With Another System');
define('_MI_formulize_MANAGE_ACCOUNT_CREATION_TOKENS', 'Manage Account Creation Tokens');
define('_MI_formulize_MANAGE_FORM_ACCESS', 'Manage Access to Forms');
