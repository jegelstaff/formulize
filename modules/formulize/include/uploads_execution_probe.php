<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Shared, bootstrap-free check for whether the web server will EXECUTE scripts saved in the uploads folder.
 *
 * Files people upload to forms are stored in uploads, so a server that runs code from there is a serious
 * risk: someone could upload a program and then run it. A hardened uploads/.htaccess ships with Formulize
 * to prevent this, but it only takes effect on Apache with AllowOverride enabled - nginx and other servers
 * ignore it - so whether it is actually working has to be measured, not assumed.
 *
 * This file is included from two very different places - the update patch (admin/patches/002_always_run.php)
 * and the installer (install/page_pathsettings.php), which runs before Formulize is bootstrapped - so it
 * depends on nothing but PHP and, for the live check, cURL.
 * Both callers include this file with include_once, so the function definitions below are parsed once.
 */

/**
 * Find out whether PHP files in the uploads folder are executed by the web server, by writing a harmless
 * test script there and asking for it back over HTTP.
 *
 * Nothing is reported as unsafe unless execution is positively confirmed, so a site this cannot reach over
 * HTTP (egress blocked, behind a proxy, a certificate it cannot follow) returns 'unknown' and the callers
 * stay quiet, rather than raising a false alarm.
 *
 * @param string $uploadsDir Full filesystem path to the uploads folder
 * @param string $uploadsUrl The URL the uploads folder is served from (trailing slash optional)
 * @return string 'executable' (confirmed to run code), 'safe' (confirmed served as plain text), or 'unknown'
 */
function formulize_probeUploadsExecution($uploadsDir, $uploadsUrl) {
	if(!function_exists('curl_init') OR !function_exists('random_bytes')
		OR !is_dir($uploadsDir) OR !is_writable($uploadsDir) OR trim((string) $uploadsUrl) === '') {
		return 'unknown';
	}
	$token = 'fzX'.bin2hex(random_bytes(8)).'Xfz';
	$probeName = 'formulize_execution_check_'.bin2hex(random_bytes(8)).'.php';
	$probePath = rtrim($uploadsDir, '/\\').'/'.$probeName;
	// If the server runs this, the response is just the token. If it serves it as text, the raw <?php is in
	// the response. The string is split so this very file does not contain an executable copy of it.
	$rawContents = '<'.'?php echo "'.$token.'"; ?'.'>';
	if(@file_put_contents($probePath, $rawContents) === false) {
		return 'unknown';
	}
	$body = false;
	$status = 0;
	try {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => rtrim($uploadsUrl, '/').'/'.$probeName,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 10,
			// this is a request to our own server, so a certificate problem (common on local and staging
			// sites) should not stop the check; there is no secret in the request or the response
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_USERAGENT => 'Formulize uploads execution check',
		));
		$body = curl_exec($curl);
		$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		curl_close($curl);
	} catch (Exception $e) {
		$body = false;
	}
	@unlink($probePath);
	if($body === false OR $status < 200 OR $status >= 300) {
		return 'unknown';
	}
	if(strpos($body, '<'.'?php') !== false) {
		return 'safe'; // the server handed back the source, so it did not run it
	}
	if(strpos($body, $token) !== false) {
		return 'executable'; // the token is there but the raw php is not, so the script ran
	}
	return 'unknown';
}

/**
 * The hardening rules that belong in uploads/.htaccess. The copy that ships in the folder is preferred, so
 * the text shown to people always matches what Formulize actually installs; the built in copy is a fallback
 * for the case where that file has gone missing (which is one of the reasons the folder might be executable).
 *
 * @param string $uploadsDir Full filesystem path to the uploads folder
 * @return string The contents for uploads/.htaccess
 */
function formulize_uploadsHardeningHtaccess($uploadsDir) {
	$shipped = rtrim((string) $uploadsDir, '/\\').'/.htaccess';
	if(is_file($shipped) AND is_readable($shipped)) {
		$contents = (string) file_get_contents($shipped);
		if(strpos($contents, 'FilesMatch') !== false) {
			return $contents;
		}
	}
	return <<<'HTACCESS'
# Uploaded files are user content and must never be executed as server-side scripts.
# The access denial below works on any PHP SAPI (mod_php, CGI, FastCGI, PHP-FPM). The php_flag lines are
# wrapped in <IfModule> guards on purpose: those directives are only valid under mod_php, so the guards make
# them a no-op on FastCGI/FPM/CGI. Use php_flag, NOT php_admin_flag: the php_admin_* forms are rejected in
# .htaccess context and raise a fatal parse error that makes the whole folder return 500. Do not un-guard them.
<FilesMatch "(?i)\.(php|php3|php4|php5|php7|php8|phps|phtml|pht|phar|cgi|fcgi|pl|py|pyw|wsgi|rb|erb|rhtml|lua|sh|jsp|jspx|asp|aspx|ashx|asmx|ascx|asax|cshtml|vbhtml|config|cfm|cfc|cfml|shtml|shtm|stm|htaccess|htpasswd)($|\.)">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order allow,deny
		Deny from all
	</IfModule>
</FilesMatch>
<IfModule mod_php.c>
	php_flag engine off
</IfModule>
<IfModule mod_php7.c>
	php_flag engine off
</IfModule>
<IfModule mod_php8.c>
	php_flag engine off
</IfModule>
<IfModule mod_mime.c>
	RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phps .phtml .pht .phar .cgi .fcgi .pl .py .pyw .wsgi .rb .erb .rhtml .lua .sh .jsp .jspx .asp .aspx .ashx .asmx .ascx .asax .cshtml .vbhtml .cfm .cfc .cfml .shtml .shtm .stm
	RemoveType .php .php3 .php4 .php5 .php7 .php8 .phps .phtml .pht .phar .cgi .fcgi .pl .py .pyw .wsgi .rb .erb .rhtml .lua .sh .jsp .jspx .asp .aspx .ashx .asmx .ascx .asax .cshtml .vbhtml .cfm .cfc .cfml .shtml .shtm .stm
</IfModule>
HTACCESS;
}
