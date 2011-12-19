<?php
/*************************************************************************/
# Waiting Contents Extensible                                            #
# Plugin for module WF-Links                                             #
#                                                                        #
# Author                                                                 #
# flying.tux     -   flying.tux@gmail.com                                #
#                                                                        #
# Last modified on 25.04.2005                                            #
/*************************************************************************/
function b_waiting_wflinks() {
	$ret = array() ;

	// wflinks waiting
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wflinks_links")." WHERE status=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wflinks/admin/newlinks.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// wflinks broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wflinks_broken"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wflinks/admin/brokenlink.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// wflinks modreq
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wflinks_mod"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wflinks/admin/modifications.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}


?>