<?php
// This code is not tested
function b_waiting_xdirectory() {
	$ret = array() ;

	// xdirectory links
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("xdir_links")." WHERE status=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xdirectory/admin/index.php?op=listNewLinks";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_LINKS ;
	}
	$ret[] = $block ;

	// xdirectory broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("xdir_broken"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xdirectory/admin/index.php?op=listBrokenLinks";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// xdirectory modreq
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("xdir_mod"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xdirectory/admin/index.php?op=listModReq";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}

	return $ret;
}


?>