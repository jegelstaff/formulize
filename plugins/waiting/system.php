<?php
function b_waiting_system() {
	$ret = array() ;

	// comments
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("xoopscomments")." WHERE com_status=1");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/system/admin.php?module=0&amp;status=1&amp;fct=comments" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_COMMENTS ;
	}
	$ret[] = $block ;

	// Inactive Users
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("users")." WHERE level=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/system/admin.php?fct=findusers" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_INACTIVE_USERS ;
	}
	$ret[] = $block;

	return $ret ;
}
?>