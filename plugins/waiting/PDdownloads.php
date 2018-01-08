<?php
/*************************************************************************/
# Waiting Contents Extensible                                            #
# Plugin for module PDdownloads                                          #
#                                                                        #
# Author                                                                 #
# flying.tux     -   flying.tux@gmail.com                                #
#                                                                        #
# Last modified on 21.04.2005                                            #
/*************************************************************************/
function b_waiting_PDdownloads() {
	$ret = array() ;

	// PDdownloads waiting
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("PDdownloads_downloads")." WHERE status=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/PDdownloads/admin/newdownloads.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// PDdownloads broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("PDdownloads_broken"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/PDdownloads/admin/brokendown.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// PDdownloads modreq
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("PDdownloads_mod"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/PDdownloads/admin/modifications.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;
	
	return $ret;
}

?>