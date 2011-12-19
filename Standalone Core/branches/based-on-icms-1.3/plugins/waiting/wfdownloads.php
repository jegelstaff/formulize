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
function b_waiting_wfdownloads() {
	$ret = array() ;

	// wfdownloads pending
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wfdownloads_downloads")." WHERE status=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wfdownloads/admin/newdownloads.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// wfdownloads broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wfdownloads_broken"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wfdownloads/admin/brokendown.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// wfdownloads modreq
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wfdownloads_mod"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wfdownloads/admin/modifications.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	// wfdownloads reviews
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wfdownloads_reviews")." WHERE submit=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wfdownloads/admin/index.php?op=reviews";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_REVIEWS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>