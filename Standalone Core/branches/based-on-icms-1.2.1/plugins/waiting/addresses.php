<?php
// This code is not tested
function b_waiting_addresses()
{
	$xoopsDB =& Database::getInstance();
	$ret = array() ;

	// addresses links
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("addresses_links")." WHERE status=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/addresses/admin/index.php?op=listNewLinks";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_LINKS ;
	}
	$ret[] = $block ;

	// addresses broken
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("addresses_broken"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/addresses/admin/index.php?op=listBrokenLinks";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// addresses modreq
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("addresses_mod"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/addresses/admin/index.php?op=listModReq";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>