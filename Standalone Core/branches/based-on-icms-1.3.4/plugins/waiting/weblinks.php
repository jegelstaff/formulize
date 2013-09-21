<?php
function b_waiting_weblinks() {
	$ret = array() ;

	// weblinks links
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("weblinks_modify")." WHERE mode=0");
	if ($result) {
//		$block['adminlink'] = ICMS_URL."/modules/weblinks/admin/index.php?op=listNewLinks";
		$block['adminlink'] = ICMS_URL."/modules/weblinks/admin/link_manage.php?op=listNewLinks";

		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// weblinks broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("weblinks_broken"));
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/weblinks/admin/index.php?op=listBrokenLinks";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// weblinks modreq
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("weblinks_modify")." WHERE mode=1");
	if ($result) {
//		$block['adminlink'] = ICMS_URL."/modules/weblinks/admin/index.php?op=listModReq";
		$block['adminlink'] = ICMS_URL."/modules/weblinks/admin/link_manage.php?op=listModReq";

		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>