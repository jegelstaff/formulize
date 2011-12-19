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
* @version		$Id: page_start.php 8770 2009-05-18 17:52:21Z pesianstranger $
*/
/**
 *
 */ 
require_once 'common.inc.php';
if ( !defined( 'XOOPS_INSTALL' ) )	exit();

    imcms_install_chmod("../modules", 0777);
    imcms_install_chmod("../mainfile.php", 0777);
    imcms_install_chmod("../uploads", 0777);
    imcms_install_chmod("../templates_c", 0777);
    imcms_install_chmod("../cache", 0777);
	$wizard->setPage( 'start' );
	$pageHasForm = false;

    $content = "";
    include "./language/$wizard->language/welcome.php";
    
    include 'install_tpl.php';
?>