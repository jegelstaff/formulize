<?php
/**
 * Which site this request is for, and that site's settings. mainfile.php runs this first, on every request, and the
 * installer runs it through mainfile.php too.
 *
 * Everything that is a site's own, rather than Formulize's, is in one folder: its settings.php, private/, uploads/
 * and var/. That folder is FORMULIZE_SITE_PATH. It's sites/default in the code, unless the web server or the
 * environment names another folder with a FORMULIZE_SITE_PATH variable, such as a volume mounted into a container.
 * See ai-context/site-directory.md.
 *
 * settings.php defines constants only: the database login, and optionally FORMULIZE_URL and any of the folders in
 * include/sitepaths.php. Each value in it can come from an environment variable instead.
 *
 * Nothing here can use the database or the rest of Formulize, which aren't loaded yet.
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

// Which site ------------------------------------------------------------------------------------------------------

if (!defined('FORMULIZE_SITE_PATH')) {
	$formulizeSitePath = '';
	// Apache's SetEnv puts it in $_SERVER, and a rewrite can give it a REDIRECT_ prefix. A container sets it in the environment.
	foreach (array('FORMULIZE_SITE_PATH', 'REDIRECT_FORMULIZE_SITE_PATH') as $formulizeSiteVariable) {
		if (!$formulizeSitePath AND !empty($_SERVER[$formulizeSiteVariable])) {
			$formulizeSitePath = $_SERVER[$formulizeSiteVariable];
		}
	}
	if (!$formulizeSitePath AND getenv('FORMULIZE_SITE_PATH')) {
		$formulizeSitePath = getenv('FORMULIZE_SITE_PATH');
	}
	define('FORMULIZE_SITE_PATH', $formulizeSitePath ? rtrim(str_replace('\\', '/', $formulizeSitePath), '/') : XOOPS_ROOT_PATH . '/sites/default');
	unset($formulizeSitePath, $formulizeSiteVariable);
}

// Its settings -----------------------------------------------------------------------------------------------------

if (file_exists(FORMULIZE_SITE_PATH . '/settings.php')) {
	include FORMULIZE_SITE_PATH . '/settings.php';
} elseif (!defined('XOOPS_INSTALL')) {
	if (file_exists(XOOPS_ROOT_PATH . '/install.lock')) {
		// Installed before sites kept their files in a folder of their own. Sending this to the installer would
		// install a second site over the first one's database, so say what's wrong instead.
		http_response_code(503);
		header('Content-Type: text/plain; charset=utf-8');
		echo "This site was installed with an older version of Formulize, which kept its settings in mainfile.php.\n"
			. "Put back the site's own mainfile.php, or move the site's settings into " . FORMULIZE_SITE_PATH . "/settings.php.\n";
		exit();
	}
	header('Location: install/index.php');
	exit();
}

// The rest of what mainfile.php used to say ------------------------------------------------------------------------

// Which errors go to the error log: this severity and higher. 0 logs nothing. Errors are still logged, but not
// shown, when the Developer Dashboard is off.
if (!defined('ICMS_ERROR_LOG_SEVERITY')) {
	define('ICMS_ERROR_LOG_SEVERITY', E_WARNING);
}

// UTC, unless a timezone has been set some other way
if ('UTC' == @date_default_timezone_get()) {
	date_default_timezone_set('UTC');
}

// The core's "trust path" is the site's private folder: the files a site keeps that must never be served
if (!defined('XOOPS_TRUST_PATH')) {
	define('XOOPS_TRUST_PATH', FORMULIZE_SITE_PATH . '/private');
}

// The site's address. FORMULIZE_URL, from settings.php or the environment, says it outright. Otherwise it's worked out
// from the request, and from the X-Forwarded headers a proxy in front of the site adds, if settings.php says to trust
// them with FORMULIZE_TRUST_PROXY_HEADERS. X-Forwarded-Proto is always used, as it always has been.
if (!defined('XOOPS_URL')) {
	if (!defined('FORMULIZE_URL') AND getenv('FORMULIZE_URL')) {
		define('FORMULIZE_URL', getenv('FORMULIZE_URL'));
	}
	if (defined('FORMULIZE_URL') AND FORMULIZE_URL) {
		define('XOOPS_URL', rtrim(FORMULIZE_URL, '/'));
	} else {
		if (!defined('SITE_BASE_URL')) {
			// if this code is in a subfolder of the website, the subfolder is the part of the root path that isn't in the document root
			$formulizeBaseUrl = '';
			$formulizeDocumentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
			$formulizeRootPath = str_replace('\\', '/', XOOPS_ROOT_PATH);
			if ($formulizeDocumentRoot AND rtrim($formulizeRootPath, '/') != rtrim($formulizeDocumentRoot, '/')) {
				if (strpos($formulizeRootPath, rtrim($formulizeDocumentRoot, '/') . '/') === 0) {
					$formulizeBaseUrl = substr($formulizeRootPath, strlen(rtrim($formulizeDocumentRoot, '/')));
				} else {
					error_log('Formulize: could not detect base url automatically. If your website is not located in the root of the domain, set FORMULIZE_URL in settings.php.');
				}
			}
			define('SITE_BASE_URL', $formulizeBaseUrl);
			unset($formulizeBaseUrl, $formulizeDocumentRoot, $formulizeRootPath);
		}
		$formulizeTrustProxy = (defined('FORMULIZE_TRUST_PROXY_HEADERS') AND FORMULIZE_TRUST_PROXY_HEADERS) OR getenv('FORMULIZE_TRUST_PROXY_HEADERS');
		$formulizeHost = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
		$formulizePort = isset($_SERVER['SERVER_PORT']) ? intval($_SERVER['SERVER_PORT']) : 80;
		$formulizeHttps = (443 == $formulizePort OR (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) AND $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') OR (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] != 'off'));
		if ($formulizeTrustProxy) {
			if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
				// a list when there's more than one proxy: the first is the one the visitor asked for
				$formulizeHost = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
				$formulizePort = $formulizeHttps ? 443 : 80;
				if (preg_match('/^(.+):(\d+)$/', $formulizeHost, $formulizeHostAndPort)) {
					$formulizeHost = $formulizeHostAndPort[1];
					$formulizePort = intval($formulizeHostAndPort[2]);
				}
			}
			if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
				$formulizePort = intval(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PORT'])[0]));
			}
		}
		$formulizePortInUrl = (80 == $formulizePort OR 443 == $formulizePort) ? '' : ':' . $formulizePort;
		define('XOOPS_URL', ($formulizeHttps ? 'https://' : 'http://') . $formulizeHost . $formulizePortInUrl . SITE_BASE_URL);
		unset($formulizeTrustProxy, $formulizeHost, $formulizePort, $formulizeHttps, $formulizePortInUrl, $formulizeHostAndPort);
	}
}

if (!defined('XOOPS_CHECK_PATH')) {
	define('XOOPS_CHECK_PATH', 0);
}

// The database, from the SDATA_ constants settings.php defines
if (!defined('XOOPS_DB_TYPE')) {
	define('XOOPS_DB_TYPE', 'pdo.mysql');
}
if (!defined('XOOPS_DB_CHARSET')) {
	define('XOOPS_DB_CHARSET', 'utf8mb4');
}
if (defined('SDATA_DB_NAME')) {
	define('XOOPS_DB_DSN', 'host=' . SDATA_DB_HOST . ';dbname=' . SDATA_DB_NAME . ';charset=' . XOOPS_DB_CHARSET);
	define('XOOPS_DB_PREFIX', SDATA_DB_PREFIX);
	define('XOOPS_DB_HOST', SDATA_DB_HOST);
	define('XOOPS_DB_USER', SDATA_DB_USER);
	define('XOOPS_DB_PASS', SDATA_DB_PASS);
	define('XOOPS_DB_NAME', SDATA_DB_NAME);
	// Never change the salt once the site is live: passwords and encrypted values depend on it
	define('XOOPS_DB_SALT', SDATA_DB_SALT);
	define('XOOPS_DB_OPTIONS', (defined('SDATA_DB_OPTIONS') AND is_array(SDATA_DB_OPTIONS) AND !empty(SDATA_DB_OPTIONS)) ? SDATA_DB_OPTIONS : NULL);
}
if (!defined('XOOPS_DB_PCONNECT')) {
	define('XOOPS_DB_PCONNECT', 0);
}
// (optional) the full path of a script that logs database queries
if (!defined('ICMS_LOGGING_HOOK')) {
	define('ICMS_LOGGING_HOOK', '');
}

define('XOOPS_GROUP_ADMIN', '1');
define('XOOPS_GROUP_USERS', '2');
define('XOOPS_GROUP_ANONYMOUS', '3');

foreach (array('GLOBALS', '_SESSION', 'HTTP_SESSION_VARS', '_GET', 'HTTP_GET_VARS', '_POST', 'HTTP_POST_VARS', '_COOKIE', 'HTTP_COOKIE_VARS', '_REQUEST', '_SERVER', 'HTTP_SERVER_VARS', '_ENV', 'HTTP_ENV_VARS', '_FILES', 'HTTP_POST_FILES', 'xoopsDB', 'xoopsUser', 'xoopsUserId', 'xoopsUserGroups', 'xoopsUserIsAdmin', 'icmsConfig', 'xoopsOption', 'xoopsModule', 'xoopsModuleConfig', 'xoopsRequestUri') as $bad_global) {
	if (isset($_REQUEST[$bad_global])) {
		header('Location: ' . XOOPS_URL . '/');
		exit();
	}
}

define('ICMS_GROUP_ADMIN', XOOPS_GROUP_ADMIN);
define('ICMS_GROUP_USERS', XOOPS_GROUP_USERS);
define('ICMS_GROUP_ANONYMOUS', XOOPS_GROUP_ANONYMOUS);
define('ICMS_URL', XOOPS_URL);
define('ICMS_TRUST_PATH', XOOPS_TRUST_PATH);
define('ICMS_ROOT_PATH', XOOPS_ROOT_PATH);
