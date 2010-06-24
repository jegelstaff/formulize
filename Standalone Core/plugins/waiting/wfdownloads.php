<?php
/*************************************************************************/
# Waiting Contents Extensible                                            #
# Plugin for module WF-Downloads                                         #
#                                                                        #
# Author                                                                 #
# coldfire                                                               #
#																		 #
# Modified by                                                            #
# flying.tux     -   flying.tux@gmail.com                                #
#                                                                        #
# Last modified on 21.04.2005                                            #
/*************************************************************************/
function b_waiting_wfdownloads()
{
	$xoopsDB =& Database::getInstance();
	$ret = array() ;

	// wfdownloads pending
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("wfdownloads_downloads")." WHERE status=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/wfdownloads/admin/newdownloads.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// wfdownloads broken
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("wfdownloads_broken"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/wfdownloads/admin/brokendown.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// wfdownloads modreq
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("wfdownloads_mod"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/wfdownloads/admin/modifications.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	// wfdownloads reviews
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("wfdownloads_reviews")." WHERE submit=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/wfdownloads/admin/index.php?op=reviews";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_REVIEWS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>