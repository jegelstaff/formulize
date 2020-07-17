<?php

function b_waiting_wordbook() {
	$ret = array() ;

	// Waiting
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wbentries")." WHERE submit=1 AND categoryID>0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wordbook/admin/index.php#esp." ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// Request
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wbentries")." WHERE submit=1 AND categoryID=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wordbook/admin/index.php#sol." ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_REQUESTS ;
	}
	$ret[] = $block ;

	return $ret ;
}

?>