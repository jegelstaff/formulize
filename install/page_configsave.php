<?php
/**
 * Installer mainfile creation page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright    The XOOPS project http://www.xoops.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
 * @package		installer
 * @since        Xoops 2.3.0
 * @author		Haruki Setoyama  <haruki@planewave.org>
 * @author 		Kazumi Ono <webmaster@myweb.ne.jp>
 * @author		Skalpa Keo <skalpa@xoops.org>
 * @version		$Id: page_configsave.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'configsave' );
$pageHasForm = true;
$pageHasHelp = false;

$vars =& $_SESSION['settings'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$error = '';
	// The site's settings go in settings.php, in the site's own folder, with everything else that is the site's own.
	// mainfile.php is never written. See include/site.php and ai-context/site-directory.md.
	$sitePath = FORMULIZE_SITE_PATH;
	if (!is_dir($sitePath) AND !@mkdir($sitePath, 0775, true)) {
		$error = sprintf(_INSTALL_ERR_SITE_PATH_NOT_MADE, $sitePath);
	} elseif (!$content = file_get_contents($vars['ROOT_PATH'] . '/install/templates/settings.dist.php')) {
		$error = ERR_READ_SDATA;
	} else {
		foreach (array('DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DB_PREFIX', 'DB_SALT') as $key) {
			$content = str_replace('{' . $key . '}', var_export((string) $vars[$key], true), $content);
		}
		if (file_put_contents($sitePath . '/settings.php', $content) === false) {
			$error = ERR_WRITE_SDATA;
		} else {
			// readable by the web server, and no one else
			@chmod($sitePath . '/settings.php', 0640);
		}
	}

	if (empty($error)) {
		// The site's folders. sitepaths.php makes the ones that are missing.
		include_once $vars['ROOT_PATH'] . '/include/sitepaths.php';
		// The web server refuses every request for the site's folder, but for its uploads, whose own .htaccess lets
		// them be served and keeps scripts from running. sites/.htaccess does the same for every folder in sites/.
		file_put_contents($sitePath . '/.htaccess', "# Nothing in a site's folder is served, but its uploads, which have a .htaccess of their own.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n");
		// The files that come with Formulize in uploads/, such as the smilies and the default logos, and the
		// .htaccess that keeps uploaded scripts from running
		if (ICMS_UPLOAD_PATH != $vars['ROOT_PATH'] . '/uploads') {
			formulize_installCopyFolder($vars['ROOT_PATH'] . '/uploads', ICMS_UPLOAD_PATH);
		}
		if (!is_writable(ICMS_UPLOAD_PATH) OR !is_writable(XOOPS_TRUST_PATH)) {
			$error = sprintf(_INSTALL_ERR_SITE_PATH_NOT_WRITABLE, $sitePath);
		}
	}

	if (empty( $error )) {
		$wizard->redirectToPage( '+1' );
		exit();
	}
	$content = '<p class="errorMsg">' . $error . '</p>';
	include 'install_tpl.php';
	exit();
}

/**
 * Copy what's in one folder into another, leaving alone any file that's already there
 *
 * @param string $from The folder to copy from
 * @param string $to The folder to copy into, which is made if it doesn't exist
 * @return void
 */
function formulize_installCopyFolder($from, $to) {
	if (!is_dir($to)) {
		@mkdir($to, 0775, true);
	}
	foreach (array_diff((array) scandir($from), array('.', '..')) as $name) {
		if (is_dir("$from/$name")) {
			formulize_installCopyFolder("$from/$name", "$to/$name");
		} elseif (!file_exists("$to/$name")) {
			@copy("$from/$name", "$to/$name");
		}
	}
}

ob_start();
?>
<p class="x2-note"><?php echo READY_SAVE_MAINFILE; ?></p>
<dl style="height: 200px; overflow: auto; border: 1px solid #D0D0D0">
<?php foreach ( $vars as $k => $v) {
	echo "<dt>XOOPS_$k</dt><dd>$v</dd>";
} ?>
</dl>

<?php
$content = ob_get_contents();
ob_end_clean();
include 'install_tpl.php';
