<?php
function b_waiting_AMS() {
	$block = array();

	// AMS articles
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("ams_article")." WHERE published=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/AMS/admin/index.php?op=newarticle";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}

	return $block;
}
?>