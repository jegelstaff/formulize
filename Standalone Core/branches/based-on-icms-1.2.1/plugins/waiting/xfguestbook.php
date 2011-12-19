<?php
function b_waiting_xfguestbook(){
	$xoopsDB =& Database::getInstance();
	$block = array();

	$result = $xoopsDB->query("SELECT count(*) FROM ".$xoopsDB->prefix("xfguestbook_msg")." WHERE moderate = 1");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xfguestbook/admin/index.php?action=waiting";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS;
	}

	return $block;
}
?>