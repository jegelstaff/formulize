<?php
/**
 * Installer introduction page
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
 * @version		$Id: page_start.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

// The installer writes to the site's own folder, not to the code (see include/site.php), apart from putting the
// Protector module in modules/
icms_core_Filesystem::chmod("../modules", 0775);
$wizard->setPage( 'start' );
$pageHasForm = false;

$content = "";
include "./language/$wizard->language/welcome.php";

include 'install_tpl.php';
