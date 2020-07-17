<?php
function b_waiting_mylinks()
{
	$ret = array() ;

	// mylinks links
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("mylinks_links")." WHERE status=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/mylinks/admin/index.php?op=listNewLinks";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// mylinks broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("mylinks_broken"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/mylinks/admin/index.php?op=listBrokenLinks";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// mylinks modreq
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("mylinks_mod"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/mylinks/admin/index.php?op=listModReq";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>