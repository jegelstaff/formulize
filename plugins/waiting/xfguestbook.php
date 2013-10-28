<?php
function b_waiting_xfguestbook() {
	$block = array();

	$result = icms::$xoopsDB->query("SELECT count(*) FROM ".icms::$xoopsDB->prefix("xfguestbook_msg")." WHERE moderate = 1");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xfguestbook/admin/index.php?action=waiting";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS;
	}

	return $block;
}
?>