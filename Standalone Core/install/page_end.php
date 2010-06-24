<?php
/**
* Installer final page
*
* See the enclosed file license.txt for licensing information.
* If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
*
* @copyright	The XOOPS project http://www.xoops.org/
* @license	  http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
* @package		installer
* @since		Xoops 2.3.0
* @author		Haruki Setoyama  <haruki@planewave.org>
* @author 		Kazumi Ono <webmaster@myweb.ne.jp>
* @author		Skalpa Keo <skalpa@xoops.org>
* @version		$Id: page_end.php 9296 2009-09-02 12:41:25Z pesianstranger $
*/
/**
 *
 */ 
require_once 'common.inc.php';
if ( !defined( 'XOOPS_INSTALL' ) )	exit();

$success = isset($_GET['success'])?trim($_GET['success']):false;
if ($success){
	if(is_dir(ICMS_ROOT_PATH.'/install')){
		unlinkRecursive(ICMS_ROOT_PATH.'/install', true);
		header('Location: '.ICMS_URL.'/index.php');
	}
}

	$wizard->setPage( 'end' );
	$pageHasForm = false;
	$content = "";
	include "./language/$wizard->language/finish.php";
	
	include 'install_tpl.php';
?>