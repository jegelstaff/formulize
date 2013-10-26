<?php
function b_waiting_xcgal() {
	$block = array();

	$result = icms::$xoopsDB->query("SELECT count(*) FROM ".icms::$xoopsDB->prefix("xcgal_pictures")." WHERE approved = 'NO'");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xcgal/editpics.php?mode=upload_approval";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}

	return $block;
}
?>