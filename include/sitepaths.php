<?php
/**
 * Where a site keeps the files that are its own, as opposed to the code that ships with Formulize.
 *
 * Every folder that Formulize or the core writes to at runtime, or that holds files a site's people
 * wrote, is named here once. Code that reads or writes one of these folders uses the constant, and never
 * builds the path from XOOPS_ROOT_PATH. See ai-context/site-directory.md.
 *
 * Each constant is guarded, so mainfile.php (or anything it includes) can set any of them first.
 * The defaults are the folders inside the code tree that Formulize has always used.
 *
 * Paths and URLs have no trailing slash.
 *
 * Loaded by include/constants.php, after XOOPS_ROOT_PATH, XOOPS_TRUST_PATH and XOOPS_URL are known,
 * and before the rest of the core constants, which use some of these.
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

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

foreach ($formulizeSitePathDefaults as $formulizeSitePathName => $formulizeSitePathValue) {
	if (!defined($formulizeSitePathName)) {
		define($formulizeSitePathName, $formulizeSitePathValue);
	}
}
unset($formulizeSitePathDefaults, $formulizeSitePathName, $formulizeSitePathValue);

/**
 * The shipped screen templates: default/ and <theme>/default/. They're part of the code, not the site,
 * so they always come from the module folder. A screen's own templates are in FORMULIZE_SCREEN_TEMPLATES_PATH.
 */
if (!defined('FORMULIZE_SCREEN_DEFAULTS_PATH')) {
	define('FORMULIZE_SCREEN_DEFAULTS_PATH', XOOPS_ROOT_PATH . '/modules/formulize/templates/screens');
}
