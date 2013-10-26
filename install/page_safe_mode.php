<?php
/**
 * Installer No PHP 5 information page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright    The XOOPS project http://www.xoops.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
 * @package		installer
 * @since        Xoops 2.3.0
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: page_safe_mode.php 19775 2010-07-11 18:54:25Z malanciault $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'safe_mode' );
$pageHasForm = false;

ob_start();
echo SAFE_MODE_CONTENT;
$content = ob_get_contents();
ob_end_clean();

include 'install_tpl.php';

?>