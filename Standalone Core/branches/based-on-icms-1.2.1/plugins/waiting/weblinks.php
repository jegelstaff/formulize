<?php
function b_waiting_weblinks()
{
	$xoopsDB =& Database::getInstance();
	$ret = array() ;

	// weblinks links
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("weblinks_modify")." WHERE mode=0");
	if ( $result ) {
//		$block['adminlink'] = XOOPS_URL."/modules/weblinks/admin/index.php?op=listNewLinks";
		$block['adminlink'] = XOOPS_URL."/modules/weblinks/admin/link_manage.php?op=listNewLinks";

		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// weblinks broken
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("weblinks_broken"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/weblinks/admin/index.php?op=listBrokenLinks";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// weblinks modreq
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("weblinks_modify")." WHERE mode=1");
	if ( $result ) {
//		$block['adminlink'] = XOOPS_URL."/modules/weblinks/admin/index.php?op=listModReq";
		$block['adminlink'] = XOOPS_URL."/modules/weblinks/admin/link_manage.php?op=listModReq";

		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>