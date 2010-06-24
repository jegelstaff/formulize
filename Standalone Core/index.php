<?php
/**
 * Site index aka home page.
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: index.php 8768 2009-05-16 22:48:26Z pesianstranger $
 **/
/**
 * redirects to installation, if ImpressCMS is not installed yet
 **/
include "mainfile.php";

$member_handler = & xoops_gethandler ( 'member' );
$group = $member_handler->getUserBestGroup((@is_object($icmsUser)?$icmsUser->uid():0));
$icmsConfig ['startpage'] = $icmsConfig ['startpage'] [$group];

if (isset ( $icmsConfig ['startpage'] ) && $icmsConfig ['startpage'] != "" && $icmsConfig ['startpage'] != "--") {
	$arr = explode ( '-', $icmsConfig ['startpage'] );
	if (count ( $arr ) > 1) {
		$page_handler = & xoops_gethandler ( 'page' );
		$page = $page_handler->get ( $arr [1] );
		if (is_object ( $page )) {
			$url = (substr ( $page->getVar ( 'page_url' ), 0, 7 ) == 'http://') ? $page->getVar ( 'page_url' ) : ICMS_URL . '/' . $page->getVar ( 'page_url' );
			header ( 'Location: ' . $url );
		} else {
			$icmsConfig ['startpage'] = '--';
			$xoopsOption ['show_cblock'] = 1;
			/** Included to start page rendering */
			include "header.php";
			/** Included to complete page rendering */
			include "footer.php";
		}
	} else {
		header ( 'Location: ' . ICMS_URL . '/modules/' . $icmsConfig ['startpage'] . '/' );
	}
	exit ();
} else {
	$xoopsOption ['show_cblock'] = 1;
	/** Included to start page rendering */
	include "header.php";
	/** Included to complete page rendering */
	include "footer.php";
}
?>