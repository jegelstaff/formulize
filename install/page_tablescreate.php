<?php
/**
 * Installer tables creation page
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
 * @version		$Id: page_tablescreate.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */

clearstatcache(true, "../mainfile.php");
sleep(1);
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

icms_core_Filesystem::chmod("../mainfile.php", 0444);
if (defined('XOOPS_TRUST_PATH') && XOOPS_TRUST_PATH != '') {
	icms_core_Filesystem::chmod(XOOPS_TRUST_PATH, 0777);
	icms_core_Filesystem::chmod(XOOPS_ROOT_PATH.'/modules', 0777);
	icms_core_Filesystem::chmod("/modules/protector/root/modules/protector", 0777);
	icms_core_Filesystem::chmod("/modules/protector/trust_path/modules", 0777);
	if (!is_dir(XOOPS_ROOT_PATH.'/modules/protector')) {
		icms_core_Filesystem::copyRecursive(XOOPS_ROOT_PATH.'/install/modules/protector/root/modules/protector',XOOPS_ROOT_PATH.'/modules/protector');
	}
	if (!is_dir(XOOPS_TRUST_PATH.'/modules')) {
		icms_core_Filesystem::copyRecursive(XOOPS_ROOT_PATH.'/install/modules/protector/trust_path/modules',XOOPS_TRUST_PATH.'/modules');
	}
	if (!is_dir(XOOPS_TRUST_PATH.'/modules/protector')) {
		icms_core_Filesystem::copyRecursive(XOOPS_ROOT_PATH.'/install/modules/protector/trust_path/modules/protector',XOOPS_TRUST_PATH.'/modules/protector');
	}
	icms_core_Filesystem::chmod(XOOPS_ROOT_PATH.'/modules', 0755);
}
$wizard->setPage( 'tablescreate' );
$pageHasForm = true;
$pageHasHelp = false;

$vars =& $_SESSION['settings'];

include "../mainfile.php";
if (!defined("XOOPS_ROOT_PATH")) {
    sleep(1);
    // mainfile.php does not yet contain the saved changes written by the installer, so reload the page and try again
    header("Location: ".$_SERVER["REQUEST_URI"]);
    exit();
}

include_once './class/dbmanager.php';
$dbm = new db_manager();

if (!$dbm->isConnectable()) {
	$wizard->redirectToPage( '-3' );
	exit();
}
$process = '';
if (!$dbm->tableExists( 'users' )) {
	$process = 'create';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// If there's nothing to do: switch to next page
	if (empty( $process )) {
		$wizard->redirectToPage( '+1' );
		exit();
	}
	$tables = array();
	$result = $dbm->queryFromFile( './sql/' . XOOPS_DB_TYPE . '.structure.sql' );
	$content = $dbm->report();
	include 'install_tpl.php';
	exit();
}

ob_start();

if ($process == 'create') {
	?>
<p class="x2-note"><?php echo READY_CREATE_TABLES; ?></p>
	<?php
} else {
	$pageHasForm = false;
	?>
<p class="x2-note"><?php echo XOOPS_TABLES_FOUND; ?></p>
	<?php
}

$content = ob_get_contents();
ob_end_clean();
include 'install_tpl.php';
?>