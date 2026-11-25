<?php
/**
 * Where a site keeps the files that are its own, as opposed to the code that ships with Formulize.
 *
 * Every folder that Formulize or the core writes to at runtime, or that holds files a site's people
 * wrote, is named here once. Code that reads or writes one of these folders uses the constant, and never
 * builds the path from XOOPS_ROOT_PATH. See ai-context/site-directory.md.
 *
 * Each constant is guarded, so mainfile.php, or the site's settings.php, can set any of them first.
 *
 * When the site has a folder of its own, FORMULIZE_SITE_PATH (see include/site.php), the folders are in it:
 *   private/  what the site's people wrote, never served: code, screen templates, config, language, logs, queues
 *   uploads/  what's served by URL: uploaded files, appearance files, downloads
 *   var/      what Formulize generates and can make again, which a backup leaves out. FORMULIZE_VAR_PATH can put it
 *             somewhere else, such as a disk of the server's own.
 * Otherwise they're the folders inside the code tree that Formulize has always used.
 *
 * Paths and URLs have no trailing slash.
 *
 * Loaded by include/constants.php, after XOOPS_ROOT_PATH, XOOPS_TRUST_PATH and XOOPS_URL are known,
 * and before the rest of the core constants, which use some of these.
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

if (defined('FORMULIZE_SITE_PATH')) {
	if (!defined('FORMULIZE_VAR_PATH')) {
		define('FORMULIZE_VAR_PATH', FORMULIZE_SITE_PATH . '/var');
	}
	$formulizeSiteUploadsPath = defined('ICMS_UPLOAD_PATH') ? ICMS_UPLOAD_PATH : FORMULIZE_SITE_PATH . '/uploads';
	if (defined('ICMS_UPLOAD_URL')) {
		$formulizeSiteUploadsUrl = ICMS_UPLOAD_URL;
	} elseif (strpos(FORMULIZE_SITE_PATH . '/', rtrim(str_replace('\\', '/', XOOPS_ROOT_PATH), '/') . '/') === 0) {
		// The site's folder is in the web root, so its uploads are served from where they are, with no rewrite rule
		// needed. sites/.htaccess refuses everything else in the site's folder.
		$formulizeSiteUploadsUrl = XOOPS_URL . substr(FORMULIZE_SITE_PATH, strlen(rtrim(str_replace('\\', '/', XOOPS_ROOT_PATH), '/'))) . '/uploads';
	} else {
		// The site's folder is somewhere the web server doesn't serve, such as a volume mounted into a container, so
		// the web server serves /uploads from it, with an alias
		$formulizeSiteUploadsUrl = XOOPS_URL . '/uploads';
	}
	$formulizeSitePathDefaults = array(
		'ICMS_UPLOAD_PATH'     => $formulizeSiteUploadsPath,
		'ICMS_UPLOAD_URL'      => $formulizeSiteUploadsUrl,
		'ICMS_CACHE_PATH'      => FORMULIZE_VAR_PATH . '/cache',
		'ICMS_COMPILE_PATH'    => FORMULIZE_VAR_PATH . '/templates_c',
		'ICMS_TOKEN_PATH'      => FORMULIZE_VAR_PATH . '/tokens',
		'ICMS_PURIFIER_CACHE'  => FORMULIZE_VAR_PATH . '/htmlpurifier',

		'FORMULIZE_CODE_PATH'               => XOOPS_TRUST_PATH . '/code',
		'FORMULIZE_SCREEN_TEMPLATES_PATH'   => XOOPS_TRUST_PATH . '/screens',
		'FORMULIZE_CONFIG_PATH'             => XOOPS_TRUST_PATH . '/config',
		'FORMULIZE_LOG_PATH'                => XOOPS_TRUST_PATH . '/logs',
		'FORMULIZE_QUEUE_PATH'              => XOOPS_TRUST_PATH . '/queue',
		'FORMULIZE_NOTIFICATION_QUEUE_PATH' => XOOPS_TRUST_PATH . '/queue/notifications',
		'FORMULIZE_SITE_LANGUAGE_PATH'      => XOOPS_TRUST_PATH . '/language',

		'FORMULIZE_CACHE_PATH'  => FORMULIZE_VAR_PATH . '/formulize-cache',
		'FORMULIZE_TEMP_PATH'   => FORMULIZE_VAR_PATH . '/temp',
		'FORMULIZE_EXPORT_PATH' => FORMULIZE_VAR_PATH . '/export',

		'FORMULIZE_DOWNLOADS_PATH' => $formulizeSiteUploadsPath . '/downloads',
		'FORMULIZE_DOWNLOADS_URL'  => $formulizeSiteUploadsUrl . '/downloads',
	);
	unset($formulizeSiteUploadsPath, $formulizeSiteUploadsUrl);
} else {
	// The folders in the code tree that Formulize has always used
	$formulizeSitePathDefaults = array(
		// Core folders
		'ICMS_UPLOAD_PATH'     => XOOPS_ROOT_PATH . '/uploads',
		'ICMS_UPLOAD_URL'      => XOOPS_URL . '/uploads',
		'ICMS_CACHE_PATH'      => XOOPS_ROOT_PATH . '/cache',
		'ICMS_COMPILE_PATH'    => XOOPS_ROOT_PATH . '/templates_c',
		'ICMS_TOKEN_PATH'      => XOOPS_ROOT_PATH . '/tokens',
		'ICMS_PURIFIER_CACHE'  => XOOPS_TRUST_PATH . '/cache/htmlpurifier',

		// Formulize: files people wrote, which a backup must keep
		'FORMULIZE_CODE_PATH'               => XOOPS_ROOT_PATH . '/modules/formulize/code',
		'FORMULIZE_SCREEN_TEMPLATES_PATH'   => XOOPS_ROOT_PATH . '/modules/formulize/templates/screens',
		'FORMULIZE_CONFIG_PATH'             => XOOPS_ROOT_PATH . '/modules/formulize/config',
		'FORMULIZE_LOG_PATH'                => XOOPS_ROOT_PATH . '/logs',
		'FORMULIZE_QUEUE_PATH'              => XOOPS_ROOT_PATH . '/modules/formulize/queue',
		'FORMULIZE_NOTIFICATION_QUEUE_PATH' => XOOPS_ROOT_PATH . '/modules/formulize/cache',
		// A site's own language files, in folders named for each language, as in the module's language folder:
		// custom mail templates in <language>/mail_template/, and the date formats the preferences set.
		'FORMULIZE_SITE_LANGUAGE_PATH'      => XOOPS_ROOT_PATH . '/modules/formulize/language',

		// Formulize: generated or short-lived files, which can be deleted
		'FORMULIZE_CACHE_PATH'  => XOOPS_ROOT_PATH . '/modules/formulize/cache',
		'FORMULIZE_TEMP_PATH'   => XOOPS_ROOT_PATH . '/modules/formulize/temp',
		'FORMULIZE_EXPORT_PATH' => XOOPS_ROOT_PATH . '/modules/formulize/export',

		// Formulize: generated files that people download by URL
		'FORMULIZE_DOWNLOADS_PATH' => XOOPS_ROOT_PATH . '/cache',
		'FORMULIZE_DOWNLOADS_URL'  => XOOPS_URL . '/cache',
	);
}

foreach ($formulizeSitePathDefaults as $formulizeSitePathName => $formulizeSitePathValue) {
	if (!defined($formulizeSitePathName)) {
		define($formulizeSitePathName, $formulizeSitePathValue);
	}
}
unset($formulizeSitePathDefaults, $formulizeSitePathName, $formulizeSitePathValue);

// A site's folders are made when they're missing, so var/ can be emptied or put on a new disk, and a site's folder
// can start out empty. Only for a site with a folder of its own: the folders in the code tree ship with it.
if (defined('FORMULIZE_SITE_PATH') AND is_dir(FORMULIZE_SITE_PATH)) {
	foreach (array(ICMS_CACHE_PATH, ICMS_COMPILE_PATH, ICMS_TOKEN_PATH, ICMS_PURIFIER_CACHE, FORMULIZE_CODE_PATH,
		FORMULIZE_SCREEN_TEMPLATES_PATH, FORMULIZE_CONFIG_PATH, FORMULIZE_LOG_PATH, FORMULIZE_QUEUE_PATH,
		FORMULIZE_NOTIFICATION_QUEUE_PATH, FORMULIZE_SITE_LANGUAGE_PATH, FORMULIZE_CACHE_PATH, FORMULIZE_TEMP_PATH,
		FORMULIZE_EXPORT_PATH, ICMS_UPLOAD_PATH, FORMULIZE_DOWNLOADS_PATH) as $formulizeSiteFolder) {
		if (!is_dir($formulizeSiteFolder)) {
			@mkdir($formulizeSiteFolder, 0775, true);
		}
	}
	unset($formulizeSiteFolder);
}

/**
 * The shipped screen templates: default/ and <theme>/default/. They're part of the code, not the site,
 * so they always come from the module folder. A screen's own templates are in FORMULIZE_SCREEN_TEMPLATES_PATH.
 */
if (!defined('FORMULIZE_SCREEN_DEFAULTS_PATH')) {
	define('FORMULIZE_SCREEN_DEFAULTS_PATH', XOOPS_ROOT_PATH . '/modules/formulize/templates/screens');
}
